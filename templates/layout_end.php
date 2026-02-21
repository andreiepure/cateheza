<?php
/**
 * Closing part of the HTML shell.
 */
declare(strict_types=1);
if (!defined('BASEPATH')) {
    exit;
}
?>
</main>

<!-- Footer -->
<footer class="footer py-3 bg-light border-top mt-auto">
    <div class="container text-center text-muted small">
        <p class="mb-0"><?= h(t('site.title')) ?> &mdash; <?= h(t('site.tagline')) ?></p>
    </div>
</footer>

<!-- Bootstrap 5.3.8 JS Bundle (local) -->
<script src="<?= h(APP_URL) ?>/assets/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS (Alpine components — must be registered before Alpine initialises) -->
<script defer src="<?= h(APP_URL) ?>/assets/js/app.js"></script>

<!-- Alpine.js 3.15.8 (local, defer — must come after app.js so alpine:init fires after components are registered) -->
<script defer src="<?= h(APP_URL) ?>/assets/js/alpine.min.js"></script>

</body>
</html>
