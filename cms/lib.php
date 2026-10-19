<?php
/**
 * Zoha mini-CMS — shared functions.
 * Content is stored in data/content.json. Saving publishes static files
 * (index.html, privacy.html, robots.txt, sitemap.xml), so the public site needs no PHP to load.
 */
declare(strict_types=1);

// config.php is not in git (it holds the setup code); fall back to the example settings.
require_once is_file(__DIR__ . '/config.php') ? __DIR__ . '/config.php' : __DIR__ . '/config.example.php';
if (is_file(__DIR__ . '/setup-hash.php')) require_once __DIR__ . '/setup-hash.php';

const SITE_ROOT = __DIR__ . '/..';
const DATA_DIR  = SITE_ROOT . '/data';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function default_content(): array
{
    return require __DIR__ . '/defaults.php';
}

function load_content(): array
{
    $file = DATA_DIR . '/content.json';
    if (is_file($file)) {
        $saved = json_decode((string)file_get_contents($file), true);
        if (is_array($saved)) {
            return array_replace_recursive(default_content(), $saved);
        }
    }
    return default_content();
}

/** Write a file in one step, so visitors never see a half-written page. */
function write_file(string $path, string $data): bool
{
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $data, LOCK_EX) === false) {
        return false;
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function ensure_data_dir(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0750, true);
    }
}

function read_json(string $name): array
{
    $file = DATA_DIR . '/' . $name;
    $data = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
    return is_array($data) ? $data : [];
}

function write_json(string $name, array $data): bool
{
    ensure_data_dir();
    return write_file(DATA_DIR . '/' . $name, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Merge posted form values into the current content, accepting only fields that already exist.
 * Fields not in the form keep their current value. Every value is plain text;
 * HTML is escaped when the pages are built.
 */
function clean_content(array $posted, array $current): array
{
    $out = [];
    foreach ($current as $key => $existing) {
        $value = $posted[$key] ?? null;
        if (is_array($existing)) {
            $out[$key] = clean_content(is_array($value) ? $value : [], $existing);
        } else {
            $text = is_string($value) ? $value : (string)$existing;
            $text = str_replace("\r\n", "\n", $text);
            $text = preg_replace('/[^\P{C}\n\t]/u', '', $text) ?? '';
            $out[$key] = mb_substr(trim($text), 0, $key === 'body' ? 40000 : 2000);
        }
    }
    return $out;
}

/** Validate content before publishing. Returns a list of problems (empty = OK). */
function validate_content(array $c): array
{
    $errors = [];
    foreach (['contact.email' => $c['contact']['email'], 'form.mail_to' => $c['form']['mail_to'],
              'form.mail_from' => $c['form']['mail_from'], 'privacy.email' => $c['privacy']['email']] as $field => $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "“{$email}” is not a valid email address ({$field}).";
        }
    }
    if (!preg_match('#^https://[a-z0-9.-]+\.[a-z]{2,}$#i', $c['site']['url'])) {
        $errors[] = 'Website address must look like https://www.example.com (no slash at the end).';
    }
    foreach ([$c['site']['title'], $c['hero']['headline'], $c['company']['name']] as $required) {
        if ($required === '') {
            $errors[] = 'Page title, headline and company name cannot be empty.';
            break;
        }
    }
    return $errors;
}

function save_content(array $c): bool
{
    return write_json('content.json', $c);
}

// ---------- Page building ----------

/** Multi-line text: escape and keep line breaks. */
function lines(string $text): string
{
    return nl2br(e($text), false);
}

function tel_link(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Simple text formatting for the privacy notice:
 *   "## Heading" line → heading, lines starting "- " → bullet list, blank line → new paragraph.
 * Emails and https:// links become clickable. {company} {address} {uid} {email} are filled in.
 */
function format_text(string $text, array $c): string
{
    $tokens = [
        '{company}' => $c['company']['name'],
        '{address}' => $c['company']['address'],
        '{uid}'     => $c['company']['uid'],
        '{email}'   => $c['privacy']['email'],
    ];
    $inline = function (string $s) use ($tokens): string {
        $s = e(strtr($s, $tokens));
        $s = preg_replace('#\bhttps?://[^\s<)]+[^\s<).,;:]#', '<a href="$0" rel="noopener">$0</a>', $s);
        return preg_replace('/(?<![\w.\/@-])[\w.+-]+@[\w-]+(\.[\w-]+)+/', '<a href="mailto:$0">$0</a>', $s);
    };

    $html = '';
    foreach (preg_split('/\n\s*\n/', trim(str_replace("\r\n", "\n", $text))) as $block) {
        $rows = array_map('trim', explode("\n", trim($block)));
        if (str_starts_with($rows[0], '## ')) {
            $html .= '<h2>' . $inline(substr($rows[0], 3)) . "</h2>\n";
            $rows = array_slice($rows, 1);
            if (!$rows) continue;
        }
        if (count(array_filter($rows, fn($r) => str_starts_with($r, '- '))) === count($rows)) {
            $html .= "<ul>\n" . implode('', array_map(fn($r) => '  <li>' . $inline(substr($r, 2)) . "</li>\n", $rows)) . "</ul>\n";
        } else {
            $html .= '<p>' . implode("<br>\n", array_map($inline, $rows)) . "</p>\n";
        }
    }
    return $html;
}

function render(string $template, array $c): string
{
    ob_start();
    include __DIR__ . '/templates/' . $template . '.php';
    return (string)ob_get_clean();
}

/** Build and write the public files. Returns a list of files that could not be written. */
function publish(array $c): array
{
    $url = rtrim($c['site']['url'], '/');
    $day = date('Y-m-d');
    $files = [
        'index.html'   => render('index', $c),
        'privacy.html' => render('privacy', $c),
        'robots.txt'   => "User-agent: *\nAllow: /\nDisallow: /data/\nDisallow: /admin/\n\nSitemap: {$url}/sitemap.xml\n",
        'sitemap.xml'  => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
                        . "  <url><loc>{$url}/</loc><lastmod>{$day}</lastmod></url>\n"
                        . "  <url><loc>{$url}/privacy.html</loc><lastmod>{$c['privacy']['updated']}</lastmod></url>\n</urlset>\n",
    ];
    $failed = [];
    foreach ($files as $name => $data) {
        if (!write_file(SITE_ROOT . '/' . $name, $data)) {
            $failed[] = $name;
        }
    }
    return $failed;
}

// ---------- Messages from the contact form ----------

function load_messages(): array
{
    $file = DATA_DIR . '/messages.jsonl';
    if (!is_file($file)) return [];
    $out = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $m = json_decode($line, true);
        if (is_array($m)) {
            $m['id'] ??= substr(hash('sha256', $line), 0, 12);
            $out[] = $m;
        }
    }
    return array_reverse($out); // newest first
}

function delete_message(string $id): bool
{
    $file = DATA_DIR . '/messages.jsonl';
    if (!is_file($file)) return false;
    $keep = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $m = json_decode($line, true);
        $mid = $m['id'] ?? substr(hash('sha256', $line), 0, 12);
        if ($mid !== $id) $keep[] = $line;
    }
    return write_file($file, $keep ? implode("\n", $keep) . "\n" : '');
}
