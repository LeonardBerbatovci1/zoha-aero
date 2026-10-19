<?php
/**
 * Zoha mini-CMS — admin screen (/admin)
 * One password, no database. Saving content rebuilds the public pages.
 */
declare(strict_types=1);

require __DIR__ . '/../cms/lib.php';

// Always use /admin/ with the trailing slash
if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) === '/admin') {
    header('Location: /admin/', true, 301);
    exit;
}

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

// ---------- Session ----------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('zoha_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => '/admin', 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$account  = read_json('admin.json');
$hasSetup = !empty($account['hash']);
$loggedIn = !empty($_SESSION['admin']) && ($_SESSION['seen'] ?? 0) > time() - SESSION_MINUTES * 60;
if ($loggedIn) {
    $_SESSION['seen'] = time();
} elseif (!empty($_SESSION['admin'])) {
    unset($_SESSION['admin']);
    $_SESSION['flash'] = ['ok', 'You were logged out after a period of inactivity.'];
}

function flash(string $type, string $text): void { $_SESSION['flash'] = [$type, $text]; }
function go(string $tab = ''): never
{
    header('Location: /admin/' . ($tab ? '?tab=' . urlencode($tab) : ''), true, 303);
    exit;
}

// ---------- Login rate limit (per hashed IP) ----------
$ipKey = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . __DIR__);
function attempts(string $ipKey): array
{
    $all = read_json('login.json');
    return array_values(array_filter($all[$ipKey] ?? [], fn($t) => $t > time() - 900));
}
function record_attempt(string $ipKey, bool $clear = false): void
{
    $all = read_json('login.json');
    foreach ($all as $k => $times) {
        $all[$k] = array_values(array_filter($times, fn($t) => $t > time() - 900));
        if (!$all[$k]) unset($all[$k]);
    }
    if ($clear) unset($all[$ipKey]); else $all[$ipKey][] = time();
    write_json('login.json', $all);
}

// ---------- Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        flash('err', 'The form expired. Please try again.');
        go();
    }
    $action = (string)($_POST['action'] ?? '');

    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));

    if ($action === 'setup' && !$hasSetup) {
        $pw = (string)($_POST['password'] ?? '');
        $code = (string)($_POST['code'] ?? '');
        $codeOk = (SETUP_CODE !== 'change-me-before-upload' && hash_equals(SETUP_CODE, $code))
               || (defined('SETUP_CODE_HASH') && password_verify($code, SETUP_CODE_HASH));
        if (count(attempts($ipKey)) >= 5) {
            flash('err', 'Too many attempts. Please wait 15 minutes.');
        } elseif (!$codeOk) {
            record_attempt($ipKey);
            flash('err', 'Wrong setup code.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('err', 'Enter a valid email address.');
        } elseif (mb_strlen($pw) < 10) {
            flash('err', 'Use a password of at least 10 characters.');
        } elseif ($pw !== ($_POST['password2'] ?? '')) {
            flash('err', 'The two passwords do not match.');
        } elseif (!write_json('admin.json', ['email' => $email, 'hash' => password_hash($pw, PASSWORD_DEFAULT)])) {
            flash('err', 'Could not save. Make sure the data folder is writable.');
        } else {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['seen'] = time();
            if (!is_file(DATA_DIR . '/content.json')) save_content(load_content());
            flash('ok', 'Password created. Welcome!');
        }
        go();
    }

    if ($action === 'login' && $hasSetup) {
        $pw = (string)($_POST['password'] ?? '');
        $storedEmail = (string)($account['email'] ?? '');
        // Check the password first in every case, so response time does not reveal whether the email was right.
        $pwOk = password_verify($pw, $account['hash']);
        $emailOk = $storedEmail === '' ? (bool)filter_var($email, FILTER_VALIDATE_EMAIL) : hash_equals($storedEmail, $email);
        if (count(attempts($ipKey)) >= 5) {
            flash('err', 'Too many attempts. Please wait 15 minutes.');
        } elseif ($pwOk && $emailOk) {
            record_attempt($ipKey, true);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['seen'] = time();
            // Accounts created before email login get the email from their first login.
            if ($storedEmail === '') $account['email'] = $email;
            if (password_needs_rehash($account['hash'], PASSWORD_DEFAULT)) $account['hash'] = password_hash($pw, PASSWORD_DEFAULT);
            write_json('admin.json', $account);
        } else {
            record_attempt($ipKey);
            flash('err', 'Wrong email or password.');
        }
        go();
    }

    if (!$loggedIn) go();

    $tab = (string)($_POST['tab'] ?? '');

    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('ok', 'You are logged out.');
        go();
    }

    if ($action === 'save') {
        $current = load_content();
        $new = clean_content(is_array($_POST['c'] ?? null) ? $_POST['c'] : [], $current);
        if ($new['privacy']['body'] !== $current['privacy']['body'] || $new['privacy']['email'] !== $current['privacy']['email']) {
            $new['privacy']['updated'] = date('Y-m-d');
        }
        $errors = validate_content($new);
        if ($errors) {
            $_SESSION['draft'] = $new;
            flash('err', 'Not saved: ' . implode(' ', $errors));
        } elseif (!save_content($new)) {
            flash('err', 'Could not save. Make sure the data folder is writable.');
        } elseif ($failed = publish($new)) {
            flash('err', 'Saved, but these files could not be updated: ' . implode(', ', $failed) . '. Check file permissions.');
        } else {
            flash('ok', 'Saved and published. The website is updated.');
        }
        go($tab);
    }

    if ($action === 'delete_message') {
        delete_message((string)($_POST['id'] ?? ''))
            ? flash('ok', 'Message deleted.')
            : flash('err', 'Could not delete the message.');
        go('messages');
    }

    if ($action === 'password') {
        $pw = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $account['hash'])) {
            flash('err', 'Current password is wrong.');
        } elseif (mb_strlen($pw) < 10) {
            flash('err', 'Use a password of at least 10 characters.');
        } elseif ($pw !== ($_POST['new2'] ?? '')) {
            flash('err', 'The two new passwords do not match.');
        } else {
            $account['hash'] = password_hash($pw, PASSWORD_DEFAULT);
            write_json('admin.json', $account);
            session_regenerate_id(true);
            flash('ok', 'Password changed.');
        }
        go('settings');
    }

    if ($action === 'login_email') {
        if (!password_verify((string)($_POST['current'] ?? ''), $account['hash'])) {
            flash('err', 'Current password is wrong.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('err', 'Enter a valid email address.');
        } else {
            $account['email'] = $email;
            write_json('admin.json', $account);
            flash('ok', 'Login email changed.');
        }
        go('settings');
    }

    go($tab);
}

// ---------- View helpers ----------
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$c = $_SESSION['draft'] ?? load_content();
unset($_SESSION['draft']);

function field(array $c, string $label, array $path, string $type = 'text', string $help = ''): void
{
    $value = $c;
    foreach ($path as $p) $value = $value[$p] ?? '';
    $name = 'c[' . implode('][', $path) . ']';
    $id   = 'f-' . implode('-', $path);
    echo '<div class="field"><label for="' . e($id) . '">' . e($label) . '</label>';
    if ($help) echo '<small>' . e($help) . '</small>';
    if ($type === 'textarea' || $type === 'long') {
        echo '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="' . ($type === 'long' ? 28 : 3) . '">' . e($value) . '</textarea>';
    } else {
        echo '<input id="' . e($id) . '" name="' . e($name) . '" type="' . e($type) . '" value="' . e($value) . '">';
    }
    echo '</div>';
}
function form_open(string $tab): void
{
    echo '<form method="post" class="panel-form"><input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'
       . '<input type="hidden" name="action" value="save"><input type="hidden" name="tab" value="' . e($tab) . '">';
}
function form_close(): void
{
    echo '<div class="actions"><button class="btn" type="submit">Save and publish</button></div></form>';
}

$tabs = ['content' => 'Website text', 'privacy' => 'Privacy notice', 'messages' => 'Messages', 'settings' => 'Settings'];
$tab  = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'content';
$messages = $loggedIn ? load_messages() : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin — Zoha Aero Consulting</title>
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<header class="top">
  <div class="top-inner">
    <span class="brand"><img src="/assets/img/favicon.svg" alt="" width="28" height="28"> Zoha Admin</span>
    <?php if ($loggedIn): ?>
      <span class="top-links">
        <a href="/" target="_blank" rel="noopener">View website ↗</a>
        <form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button name="action" value="logout" class="link">Log out</button></form>
      </span>
    <?php endif; ?>
  </div>
</header>

<main class="main">
<?php if ($flash): ?>
  <p class="flash flash--<?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></p>
<?php endif; ?>

<?php if (!$hasSetup): ?>
  <section class="card narrow">
    <h1>First-time setup</h1>
    <p>Create the admin login. Enter the setup code you were given.</p>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="setup">
      <div class="field"><label for="code">Setup code</label><input id="code" name="code" type="password" required autocomplete="off"></div>
      <div class="field"><label for="em">Email</label><input id="em" name="email" type="email" required autocomplete="username"></div>
      <div class="field"><label for="pw">New password <small>(at least 10 characters)</small></label><input id="pw" name="password" type="password" required minlength="10" autocomplete="new-password"></div>
      <div class="field"><label for="pw2">Repeat password</label><input id="pw2" name="password2" type="password" required minlength="10" autocomplete="new-password"></div>
      <button class="btn" type="submit">Create password</button>
    </form>
  </section>

<?php elseif (!$loggedIn): ?>
  <section class="card narrow">
    <h1>Log in</h1>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="login">
      <div class="field"><label for="em">Email</label><input id="em" name="email" type="email" required autofocus autocomplete="username"></div>
      <div class="field"><label for="pw">Password</label><input id="pw" name="password" type="password" required autocomplete="current-password"></div>
      <button class="btn" type="submit">Log in</button>
    </form>
  </section>

<?php else: ?>
  <nav class="tabs" aria-label="Sections">
    <?php foreach ($tabs as $key => $label): ?>
      <a href="?tab=<?= e($key) ?>"<?= $key === $tab ? ' aria-current="page"' : '' ?>><?= e($label) ?><?php if ($key === 'messages' && $messages): ?> <span class="badge"><?= count($messages) ?></span><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($tab === 'content'): form_open('content'); ?>
    <section class="card">
      <h2>1. Hero</h2>
      <?php field($c, 'Headline', ['hero', 'headline']); ?>
      <?php field($c, 'Sub-line', ['hero', 'subline'], 'textarea'); ?>
      <?php field($c, 'Button text', ['hero', 'button']); ?>
      <?php foreach ($c['hero']['highlights'] as $i => $item): ?>
        <fieldset><legend>Key point <?= $i + 1 ?> (white strip under the hero)</legend>
          <div class="grid2">
            <?php field($c, 'Title', ['hero', 'highlights', (string)$i, 'title']); ?>
            <?php field($c, 'Text', ['hero', 'highlights', (string)$i, 'text']); ?>
          </div>
        </fieldset>
      <?php endforeach; ?>
    </section>

    <section class="card">
      <h2>2. What we do</h2>
      <div class="grid2">
        <?php field($c, 'Small label', ['services', 'eyebrow']); ?>
        <?php field($c, 'Heading', ['services', 'heading']); ?>
      </div>
      <?php foreach ($c['services']['items'] as $i => $item): ?>
        <fieldset><legend>Service card <?= $i + 1 ?></legend>
          <?php field($c, 'Title', ['services', 'items', (string)$i, 'title']); ?>
          <?php field($c, 'One line', ['services', 'items', (string)$i, 'text'], 'textarea'); ?>
        </fieldset>
      <?php endforeach; ?>
    </section>

    <section class="card">
      <h2>3. How we work</h2>
      <div class="grid2">
        <?php field($c, 'Small label', ['process', 'eyebrow']); ?>
        <?php field($c, 'Heading', ['process', 'heading']); ?>
      </div>
      <?php foreach ($c['process']['items'] as $i => $item): ?>
        <fieldset><legend>Step <?= $i + 1 ?></legend>
          <?php field($c, 'Title', ['process', 'items', (string)$i, 'title']); ?>
          <?php field($c, 'Text', ['process', 'items', (string)$i, 'text'], 'textarea'); ?>
        </fieldset>
      <?php endforeach; ?>
    </section>

    <section class="card">
      <h2>4. Who we work with / About</h2>
      <div class="grid2">
        <?php field($c, 'Small label', ['about', 'eyebrow']); ?>
        <?php field($c, 'Heading', ['about', 'heading']); ?>
      </div>
      <?php field($c, 'First paragraph — title', ['about', 'who_title']); ?>
      <?php field($c, 'First paragraph — text', ['about', 'who_text'], 'textarea'); ?>
      <?php field($c, 'Second paragraph — title', ['about', 'about_title']); ?>
      <?php field($c, 'Second paragraph — text', ['about', 'about_text'], 'textarea'); ?>
      <fieldset><legend>“At a glance” panel</legend>
        <?php field($c, 'Panel title', ['about', 'glance_title']); ?>
        <?php foreach ($c['about']['glance'] as $i => $row): ?>
          <div class="grid2">
            <?php field($c, 'Label ' . ($i + 1), ['about', 'glance', (string)$i, 'label']); ?>
            <?php field($c, 'Value ' . ($i + 1), ['about', 'glance', (string)$i, 'value']); ?>
          </div>
        <?php endforeach; ?>
      </fieldset>
      <p class="hint">Reminder from the brief: no client names, partner names or logos.</p>
    </section>

    <section class="card">
      <h2>5. Contact</h2>
      <div class="grid2">
        <?php field($c, 'Small label', ['contact', 'eyebrow']); ?>
        <?php field($c, 'Heading', ['contact', 'heading']); ?>
      </div>
      <?php field($c, 'Intro line', ['contact', 'intro']); ?>
      <?php field($c, 'Address (shown on the page)', ['contact', 'address'], 'textarea'); ?>
      <div class="grid2">
        <?php field($c, 'Email (shown on the page)', ['contact', 'email'], 'email'); ?>
        <?php field($c, 'Phone', ['contact', 'phone'], 'text', 'Example: +383 44 123 456'); ?>
      </div>
      <?php field($c, 'Message after sending', ['form', 'success']); ?>
      <?php field($c, 'Message if sending fails', ['form', 'error']); ?>
    </section>
  <?php form_close(); endif; ?>

  <?php if ($tab === 'privacy'): form_open('privacy'); ?>
    <section class="card">
      <h2>Privacy notice</h2>
      <p class="hint">Last updated: <?= e($c['privacy']['updated']) ?> — the date changes automatically when you edit the text.</p>
      <?php field($c, 'Privacy contact email', ['privacy', 'email'], 'email'); ?>
      <?php field($c, 'Text', ['privacy', 'body'], 'long',
          'Formatting: start a line with "## " for a heading, "- " for a bullet point; leave an empty line between paragraphs. '
        . '{company}, {address}, {uid} and {email} are filled in automatically.'); ?>
    </section>
  <?php form_close(); endif; ?>

  <?php if ($tab === 'messages'): ?>
    <section class="card">
      <h2>Messages from the contact form</h2>
      <p class="hint">Messages are also emailed to <?= e($c['form']['mail_to']) ?>. Delete them once handled (privacy notice: kept up to 12 months).</p>
      <?php if (!$messages): ?>
        <p>No messages yet.</p>
      <?php endif; ?>
      <?php foreach ($messages as $m): ?>
        <article class="msg">
          <header>
            <strong><?= e($m['name'] ?? '') ?></strong>
            <a href="mailto:<?= e($m['email'] ?? '') ?>"><?= e($m['email'] ?? '') ?></a>
            <time><?= e(date('j M Y, H:i', strtotime($m['date'] ?? '') ?: 0)) ?></time>
          </header>
          <p><?= lines($m['message'] ?? '') ?></p>
          <form method="post" onsubmit="return confirm('Delete this message?')">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="id" value="<?= e($m['id']) ?>">
            <a class="btn btn--small" href="mailto:<?= e($m['email'] ?? '') ?>?subject=<?= rawurlencode('Re: your enquiry') ?>">Reply</a>
            <button class="link danger" name="action" value="delete_message">Delete</button>
          </form>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <?php if ($tab === 'settings'): ?>
    <?php form_open('settings'); ?>
      <section class="card">
        <h2>Website</h2>
        <?php field($c, 'Website address', ['site', 'url'], 'url', 'Example: https://www.zoha-aero.com (used for search engines and sharing)'); ?>
        <?php field($c, 'Page title (browser tab and Google)', ['site', 'title']); ?>
        <?php field($c, 'Meta description (Google snippet, about 150 characters)', ['site', 'description'], 'textarea'); ?>
      </section>
      <section class="card">
        <h2>Company details (footer)</h2>
        <?php field($c, 'Company name', ['company', 'name']); ?>
        <?php field($c, 'Address (one line)', ['company', 'address']); ?>
        <?php field($c, 'Unique Identification Number', ['company', 'uid']); ?>
      </section>
      <section class="card">
        <h2>Contact form email</h2>
        <?php field($c, 'Send form messages to', ['form', 'mail_to'], 'email'); ?>
        <?php field($c, 'Send from (an address on your domain)', ['form', 'mail_from'], 'email', 'Must be an address on your own domain, e.g. website@your-domain.com, or emails may land in spam.'); ?>
      </section>
    <?php form_close(); ?>

    <section class="card">
      <h2>Change password</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="action" value="password">
        <div class="field"><label for="cur">Current password</label><input id="cur" name="current" type="password" required autocomplete="current-password"></div>
        <div class="grid2">
          <div class="field"><label for="n1">New password</label><input id="n1" name="new" type="password" required minlength="10" autocomplete="new-password"></div>
          <div class="field"><label for="n2">Repeat new password</label><input id="n2" name="new2" type="password" required minlength="10" autocomplete="new-password"></div>
        </div>
        <button class="btn" type="submit">Change password</button>
      </form>
    </section>

    <section class="card">
      <h2>Login email</h2>
      <p class="hint">Currently: <?= e($account['email'] ?? '—') ?></p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="action" value="login_email">
        <div class="grid2">
          <div class="field"><label for="ne">New login email</label><input id="ne" name="email" type="email" required autocomplete="username"></div>
          <div class="field"><label for="cur2">Current password</label><input id="cur2" name="current" type="password" required autocomplete="current-password"></div>
        </div>
        <button class="btn" type="submit">Change login email</button>
      </form>
    </section>
  <?php endif; ?>
<?php endif; ?>
</main>
</body>
</html>
