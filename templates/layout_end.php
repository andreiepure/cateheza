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

<!-- Bootstrap 5.3.8 JS Bundle -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>

<!-- Alpine.js 3.15.8 (defer) -->
<script
    defer
    src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.8/dist/cdn.min.js"
    integrity="sha384-LXWjKwDZz29o7TduNe+r/UxaolHh5FsSvy2W7bDHSZ8jJeGgDeuNnsDNHoxpSgDi"
    crossorigin="anonymous"></script>

<!-- Custom JS (Alpine components) -->
<script src="<?= h(APP_URL) ?>/assets/js/app.js"></script>

</body>
</html>
