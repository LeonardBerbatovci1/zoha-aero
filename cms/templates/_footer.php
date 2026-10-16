  <footer class="site-footer">
    <div class="wrap footer-inner">
      <div>
        <p><strong><?= e($c['company']['name']) ?></strong></p>
        <p><?= e($c['company']['address']) ?> · Unique Identification Number: <?= e($c['company']['uid']) ?></p>
      </div>
      <p>&copy; <span id="year"><?= date('Y') ?></span> <?= e($c['company']['name']) ?> · <a href="/privacy.html">Privacy notice</a></p>
    </div>
  </footer>
