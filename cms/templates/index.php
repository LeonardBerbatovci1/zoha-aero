<?php
/** @var array $c */
$url = rtrim($c['site']['url'], '/');
$org = [
    '@context'    => 'https://schema.org',
    '@type'       => 'Organization',
    'name'        => $c['company']['name'],
    'url'         => $url . '/',
    'logo'        => $url . '/assets/img/logo-navy.svg',
    'description' => $c['site']['description'],
    'email'       => $c['contact']['email'],
    'telephone'   => $c['contact']['phone'],
    'address'     => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => explode("\n", $c['contact']['address'])[0],
        'addressLocality' => 'Prishtinë',
        'addressCountry'  => 'XK',
    ],
    'identifier'  => ['@type' => 'PropertyValue', 'propertyID' => 'Unique Identification Number', 'value' => $c['company']['uid']],
    'areaServed'  => ['US', 'Europe'],
];
// Line icons for the four service cards
$svg = fn(string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
$icons = [
    $svg('<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-6"/><path d="M16 8h4v4"/>'),
    $svg('<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3v2h6V3"/><path d="M9 13l2 2 4-4"/>'),
    $svg('<circle cx="5" cy="12" r="2.5"/><circle cx="19" cy="5" r="2.5"/><circle cx="19" cy="19" r="2.5"/><path d="M7.3 10.9l9.4-4.8M7.3 13.1l9.4 4.8"/>'),
    $svg('<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>'),
];
$jsonFlags =JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;
?>
<!doctype html>
<html lang="en">
<head>
<?php include __DIR__ . '/_head.php'; ?>
  <title><?= e($c['site']['title']) ?></title>
  <meta name="description" content="<?= e($c['site']['description']) ?>">
  <link rel="canonical" href="<?= e($url) ?>/">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($c['company']['name']) ?>">
  <meta property="og:description" content="<?= e($c['hero']['headline']) ?>">
  <meta property="og:url" content="<?= e($url) ?>/">
  <meta property="og:image" content="<?= e($url) ?>/assets/img/og-image.png">
  <script type="application/ld+json">
<?= json_encode($org, $jsonFlags) ?>

  </script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
<?php include __DIR__ . '/_mark.php'; ?>

  <header class="site-header">
    <div class="wrap header-inner">
      <a class="logo" href="#top" aria-label="Zoha Aero Consulting — back to top">
        <svg viewBox="0 0 118 114" aria-hidden="true"><use href="#mark"/></svg>
        <span class="logo-text"><span class="logo-name">ZOHA</span><span class="logo-sub">AERO CONSULTING</span></span>
      </a>

      <nav class="nav nav-desktop" aria-label="Main">
        <ul>
          <li><a href="#services"><?= e($c['services']['eyebrow']) ?></a></li>
          <li><a href="#process"><?= e($c['process']['eyebrow']) ?></a></li>
          <li><a href="#about">About</a></li>
          <li><a class="btn" href="#contact"><?= e($c['hero']['button']) ?></a></li>
        </ul>
      </nav>

      <button class="burger" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-nav">
        <span></span><span></span><span></span>
      </button>
    </div>

    <nav class="nav mobile-nav" id="mobile-nav" aria-label="Main (mobile)">
      <div class="mobile-nav-inner">
        <ul class="wrap">
          <li><a href="#services"><?= e($c['services']['eyebrow']) ?></a></li>
          <li><a href="#process"><?= e($c['process']['eyebrow']) ?></a></li>
          <li><a href="#about">About</a></li>
          <li><a class="btn" href="#contact"><?= e($c['hero']['button']) ?></a></li>
        </ul>
      </div>
    </nav>
  </header>

  <main id="main">

    <!-- 1. Hero -->
    <section class="hero" id="top">
      <div class="wrap hero-grid">
        <div class="hero-copy">
          <span class="hero-tag"><svg viewBox="0 0 118 114" aria-hidden="true"><use href="#mark"/></svg> Aviation R&amp;D consulting</span>
          <h1><?= e($c['hero']['headline']) ?></h1>
          <p><?= e($c['hero']['subline']) ?></p>
          <a class="btn btn--on-dark" href="#contact"><?= e($c['hero']['button']) ?> <span aria-hidden="true">&rarr;</span></a>
        </div>

        <!-- Transatlantic route illustration -->
        <svg class="hero-art" viewBox="0 0 560 400" role="img" aria-label="Route between the USA and Europe">
          <defs>
            <linearGradient id="arc" x1="0" x2="1">
              <stop offset="0" stop-color="#2E9CD8" stop-opacity=".25"/>
              <stop offset=".5" stop-color="#2E9CD8"/>
              <stop offset="1" stop-color="#2E9CD8" stop-opacity=".25"/>
            </linearGradient>
          </defs>
          <g fill="none" stroke="#ffffff" stroke-opacity=".08">
            <ellipse cx="280" cy="230" rx="270" ry="160"/>
            <ellipse cx="280" cy="230" rx="200" ry="160"/>
            <ellipse cx="280" cy="230" rx="120" ry="160"/>
            <ellipse cx="280" cy="230" rx="40" ry="160"/>
            <path d="M14 190 H546 M26 140 H534 M60 95 H500 M14 270 H546 M40 320 H520"/>
          </g>
          <path d="M110 270 Q 300 20 450 205" fill="none" stroke="url(#arc)" stroke-width="2.5" stroke-dasharray="2 9" stroke-linecap="round"/>
          <path d="M450 205 Q 290 330 110 270" fill="none" stroke="#ffffff" stroke-opacity=".18" stroke-width="1.5" stroke-dasharray="2 8" stroke-linecap="round"/>
          <g transform="translate(287 132) rotate(75)">
            <circle r="22" fill="#2E9CD8" fill-opacity=".15"/>
            <path d="M0-12 1.8-4 11 1v2.6L1.8 1.3 1.3 7.5 4 9.7v1.8L0 10.3-4 11.5V9.7L-1.3 7.5-1.8 1.3-11 3.6V1L-1.8-4Z" fill="#ffffff"/>
          </g>
          <g font-family="Montserrat, sans-serif" font-weight="700" letter-spacing="3" font-size="13">
            <circle cx="110" cy="270" r="16" fill="#2E9CD8" fill-opacity=".18"/>
            <circle cx="110" cy="270" r="6" fill="#2E9CD8"/>
            <text x="110" y="306" text-anchor="middle" fill="#ffffff">USA</text>
            <circle cx="450" cy="205" r="16" fill="#2E9CD8" fill-opacity=".18"/>
            <circle cx="450" cy="205" r="6" fill="#2E9CD8"/>
            <text x="450" y="241" text-anchor="middle" fill="#ffffff">EUROPE</text>
            <circle cx="492" cy="178" r="4" fill="#ffffff"/>
            <text x="492" y="163" text-anchor="middle" fill="#9fc3e3" font-size="10" letter-spacing="1.5" font-weight="600">PRISHTINË</text>
            <text x="280" y="262" text-anchor="middle" fill="#ffffff" fill-opacity=".35" font-size="10" font-weight="600" letter-spacing="6">ATLANTIC</text>
          </g>
        </svg>
      </div>
    </section>

    <!-- Key points -->
    <div class="highlights">
      <div class="wrap">
        <ul class="highlights-list">
<?php foreach ($c['hero']['highlights'] as $item): ?>
          <li><strong><?= e($item['title']) ?></strong><span><?= e($item['text']) ?></span></li>
<?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- 2. What we do -->
    <section id="services">
      <div class="wrap">
        <div class="section-head">
          <span class="eyebrow"><?= e($c['services']['eyebrow']) ?></span>
          <h2><?= e($c['services']['heading']) ?></h2>
        </div>
        <div class="cards">
<?php foreach ($c['services']['items'] as $i => $item): ?>
          <article class="card">
            <span class="card-icon" aria-hidden="true"><?= $icons[$i % 4] ?></span>
            <span class="card-num"><?= sprintf('%02d', $i + 1) ?></span>
            <h3><?= e($item['title']) ?></h3>
            <p><?= e($item['text']) ?></p>
          </article>
<?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- 3. How we work -->
    <section class="process" id="process">
      <div class="wrap">
        <div class="section-head">
          <span class="eyebrow"><?= e($c['process']['eyebrow']) ?></span>
          <h2><?= e($c['process']['heading']) ?></h2>
        </div>
        <ol class="steps">
<?php foreach ($c['process']['items'] as $item): ?>
          <li class="step">
            <h3><?= e($item['title']) ?></h3>
            <p><?= e($item['text']) ?></p>
          </li>
<?php endforeach; ?>
        </ol>
      </div>
    </section>

    <!-- 4. Who we work with / About -->
    <section id="about">
      <div class="wrap">
        <div class="section-head">
          <span class="eyebrow"><?= e($c['about']['eyebrow']) ?></span>
          <h2><?= e($c['about']['heading']) ?></h2>
        </div>
        <div class="about-grid">
          <div class="about-text">
            <h3><?= e($c['about']['who_title']) ?></h3>
            <p><?= lines($c['about']['who_text']) ?></p>
            <h3><?= e($c['about']['about_title']) ?></h3>
            <p><?= lines($c['about']['about_text']) ?></p>
          </div>
          <aside class="glance" aria-label="<?= e($c['about']['glance_title']) ?>">
            <svg class="glance-mark" viewBox="0 0 118 114" aria-hidden="true"><use href="#mark"/></svg>
            <h3><?= e($c['about']['glance_title']) ?></h3>
            <dl>
<?php foreach ($c['about']['glance'] as $row): ?>
              <div><dt><?= e($row['label']) ?></dt><dd><?= e($row['value']) ?></dd></div>
<?php endforeach; ?>
            </dl>
          </aside>
        </div>
      </div>
    </section>

    <!-- 5. Contact -->
    <section class="contact" id="contact">
      <div class="wrap">
        <div class="section-head">
          <span class="eyebrow"><?= e($c['contact']['eyebrow']) ?></span>
          <h2><?= e($c['contact']['heading']) ?></h2>
          <p><?= e($c['contact']['intro']) ?></p>
        </div>
        <div class="contact-grid">
          <ul class="details">
            <li>
              <span class="details-icon" aria-hidden="true"><?= $svg('<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>') ?></span>
              <div><span class="label">Address</span><address><?= lines($c['contact']['address']) ?></address></div>
            </li>
            <li>
              <span class="details-icon" aria-hidden="true"><?= $svg('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>') ?></span>
              <div><span class="label">Email</span><a href="mailto:<?= e($c['contact']['email']) ?>"><?= e($c['contact']['email']) ?></a></div>
            </li>
            <li>
              <span class="details-icon" aria-hidden="true"><?= $svg('<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>') ?></span>
              <div><span class="label">Phone</span><a href="<?= e(tel_link($c['contact']['phone'])) ?>"><?= e($c['contact']['phone']) ?></a></div>
            </li>
          </ul>

          <form class="form" action="/contact.php" method="post" id="contact-form">
            <p class="notice notice--ok" id="form-ok" role="status" hidden><?= e($c['form']['success']) ?></p>
            <p class="notice notice--err" id="form-err" role="alert" hidden><?= e($c['form']['error']) ?></p>

            <div class="field">
              <label for="name">Name</label>
              <input id="name" name="name" type="text" autocomplete="name" required maxlength="100">
            </div>
            <div class="field">
              <label for="email">Email</label>
              <input id="email" name="email" type="email" autocomplete="email" required maxlength="150">
            </div>
            <div class="field">
              <label for="message">Message</label>
              <textarea id="message" name="message" required maxlength="5000"></textarea>
            </div>

            <!-- Spam protection: honeypot field (hidden from people) + time check -->
            <div class="hp" aria-hidden="true">
              <label for="website">Leave this field empty</label>
              <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>
            <input type="hidden" name="ts" id="ts" value="">

            <p class="form-note">We use your details only to reply to your message. See our <a href="/privacy.html">privacy notice</a>.</p>
            <button class="btn" type="submit">Send message</button>
          </form>
        </div>
      </div>
    </section>

  </main>

<?php include __DIR__ . '/_footer.php'; ?>

  <script>
    (function () {
      var y = document.getElementById('year');
      if (y) y.textContent = new Date().getFullYear();
      var ts = document.getElementById('ts');
      if (ts) ts.value = Math.floor(Date.now() / 1000);
      var status = new URLSearchParams(location.search).get('status');
      if (status === 'sent') document.getElementById('form-ok').hidden = false;
      if (status === 'error') document.getElementById('form-err').hidden = false;
      // Mobile hamburger menu
      var burger = document.querySelector('.burger');
      var header = document.querySelector('.site-header');
      function setMenu(open) {
        header.classList.toggle('menu-open', open);
        burger.setAttribute('aria-expanded', open);
        burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      }
      burger.addEventListener('click', function () { setMenu(!header.classList.contains('menu-open')); });
      document.getElementById('mobile-nav').addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });
      document.addEventListener('click', function (e) { if (!header.contains(e.target)) setMenu(false); });
    })();
  </script>
</body>
</html>
