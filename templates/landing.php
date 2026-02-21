<?php
/**
 * Landing page partial — language selector.
 */
declare(strict_types=1);
if (!defined('BASEPATH')) {
    exit;
}
?>
<section class="landing-hero">
    <div class="text-center px-3">
        <div class="mb-4" style="font-size:4rem" aria-hidden="true">✝</div>
        <h1 class="display-4 fw-bold mb-2"><?= h(t('landing.welcome')) ?></h1>
        <p class="lead mb-5 text-white-50"><?= h(t('landing.subtitle')) ?></p>

        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php foreach (\Translator::getAvailableLocales() as $loc): ?>
            <form method="post" action="<?= h(APP_URL) ?>/index.php">
                <?= $security->getTokenField() ?>
                <input type="hidden" name="action" value="set_language">
                <input type="hidden" name="lang" value="<?= h($loc) ?>">
                <input type="hidden" name="redirect" value="<?= h(APP_URL) ?>/main.php">
                <button type="submit" class="btn btn-<?= $loc === $translator->getLocale() ? 'light' : 'outline-light' ?> lang-btn">
                    <div class="fw-bold"><?= h(t('landing.' . $loc)) ?></div>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</section>
