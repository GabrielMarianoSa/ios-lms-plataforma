</main>

<footer class="border-top bg-white">
  <div class="container py-4 small text-muted d-flex flex-column flex-md-row justify-content-between gap-2">
    <div>
      <strong class="text-body">IOS</strong> &copy; <?= date('Y') ?> · Instituto da Oportunidade Social
    </div>
    <div>
      <a class="text-decoration-none" href="https://www.ios.org.br" target="_blank" rel="noreferrer">Conheça o IOS</a>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.IOS_BASE = <?= json_encode(ios_base_path(), JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= htmlspecialchars(ios_url('/assets/css/js/chatbot.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php if (defined('IOS_BUILD')): ?>
<!-- ios-build: <?= htmlspecialchars((string)IOS_BUILD, ENT_QUOTES, 'UTF-8') ?> -->
<?php endif; ?>
</body>
</html>
