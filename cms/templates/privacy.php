<?php
/** @var array $c */
$url = rtrim($c['site']['url'], '/');
$updated = date('j F Y', strtotime($c['privacy']['updated']) ?: time());
?>
<!doctype html>
<html lang="en">
<head>
<?php include __DIR__ . '/_head.php'; ?>
  <title>Privacy notice — Zoha Aero Consulting</title>
  <meta name="description" content="How <?= e($c['company']['name']) ?> collects and uses personal data sent through this website.">
  <link rel="canonical" href="<?= e($url) ?>/privacy.html">
</head>
<body>
<?php include __DIR__ . '/_mark.php'; ?>

  <header class="site-header">
    <div class="wrap header-inner">
      <a class="logo" href="/" aria-label="Zoha Aero Consulting — home">
        <svg viewBox="0 0 118 114" aria-hidden="true"><use href="#mark"/></svg>
        <span class="logo-text"><span class="logo-name">ZOHA</span><span class="logo-sub">AERO CONSULTING</span></span>
      </a>
      <a href="/">&larr; Back to home</a>
    </div>
  </header>

  <main class="legal">
    <div class="wrap">
      <h1>Privacy notice</h1>
      <p class="updated">Last updated: <?= e($updated) ?></p>

<?= format_text($c['privacy']['body'], $c) ?>
    </div>
  </main>

<?php include __DIR__ . '/_footer.php'; ?>
  <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>
