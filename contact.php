<?php
/**
 * Contact form handler — Zoha Aero Consulting
 * Spam protection: honeypot field, minimum fill time, per-IP rate limit, header-injection checks.
 * Every valid message is also saved to data/messages.jsonl, so nothing is lost if mail delivery fails.
 */

require __DIR__ . '/cms/lib.php';

// Recipient and sender addresses are set in the admin (Settings → Contact form email).
$settings = load_content()['form'];
define('MAIL_TO', $settings['mail_to']);
define('MAIL_FROM', $settings['mail_from']);
const MIN_SECONDS  = 3;   // faster submissions are treated as bots
const MAX_PER_HOUR = 5;   // per IP address

function back(string $status): void {
    header('Location: /?status=' . $status . '#contact', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /#contact', true, 303);
    exit;
}

// Honeypot: real visitors never see this field. Pretend success so bots learn nothing.
if (!empty($_POST['website'])) {
    back('sent');
}

// Time check (the timestamp is set by JavaScript when the page loads)
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && (time() - $ts) < MIN_SECONDS) {
    back('sent');
}

$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$valid = $name !== '' && mb_strlen($name) <= 100
    && !preg_match('/[\r\n]/', $name)
    && filter_var($email, FILTER_VALIDATE_EMAIL) && mb_strlen($email) <= 150
    && $message !== '' && mb_strlen($message) <= 5000;

if (!$valid) {
    back('error');
}

// Simple link-spam filter
if (preg_match_all('#https?://#i', $message) > 3) {
    back('sent');
}

// Rate limit per IP (hashed — no raw IPs stored)
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0750, true);
}
$ipHash   = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . __FILE__);
$rateFile = DATA_DIR . '/rate.json';
$rates    = is_file($rateFile) ? (json_decode((string)file_get_contents($rateFile), true) ?: []) : [];
$now      = time();
foreach ($rates as $k => $times) {
    $rates[$k] = array_values(array_filter($times, fn($t) => $t > $now - 3600));
    if (!$rates[$k]) unset($rates[$k]);
}
if (count($rates[$ipHash] ?? []) >= MAX_PER_HOUR) {
    back('error');
}
$rates[$ipHash][] = $now;
@file_put_contents($rateFile, json_encode($rates), LOCK_EX);

// Keep a copy of the message
$record = ['id' => bin2hex(random_bytes(6)), 'date' => date('c'), 'name' => $name, 'email' => $email, 'message' => $message];
$saved = @file_put_contents(DATA_DIR . '/messages.jsonl', json_encode($record, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);

// Send email
$subject = '=?UTF-8?B?' . base64_encode('Website enquiry from ' . $name) . '?=';
$body    = "New message from the website contact form\n\n"
         . "Name:  {$name}\n"
         . "Email: {$email}\n\n"
         . "Message:\n{$message}\n";
$headers = implode("\r\n", [
    'From: Zoha Aero Website <' . MAIL_FROM . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
]);

$sent = @mail(MAIL_TO, $subject, $body, $headers, '-f' . MAIL_FROM);

// The message is stored either way; report success if it was saved or mailed.
back($sent || $saved !== false ? 'sent' : 'error');
