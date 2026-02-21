<?php
/**
 * Activity card grid partial.
 * $activities — array of meta arrays from ActivityLoader::loadAllMeta()
 */
declare(strict_types=1);
if (!defined('BASEPATH')) {
    exit;
}
?>
<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 fw-bold"><?= h(t('main.title')) ?></h1>
            <p class="text-muted"><?= h(t('main.subtitle')) ?></p>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($activities as $meta): ?>
        <?php
            $slug      = h($meta['slug'] ?? '');
            $title     = h(t($meta['title_key'] ?? ''));
            $desc      = h(t($meta['description_key'] ?? ''));
            $thumbRel  = $meta['thumbnail'] ?? '';
            $thumbnail = h($thumbRel);
            $hasThumbnail = $thumbRel !== '' && file_exists(BASEPATH . '/' . $thumbRel);
            $ready     = !empty($meta['ready']);
            $actUrl    = h(APP_URL) . '/activity.php?slug=' . $slug;
        ?>
        <div class="col">
            <div class="card activity-card h-100<?= $ready ? '' : ' activity-card--wip' ?>">
                <div class="position-relative">
                <?php if ($hasThumbnail): ?>
                <img src="<?= h(APP_URL) ?>/<?= $thumbnail ?>"
                     alt="<?= $title ?>"
                     class="card-img-top"
                     loading="lazy">
                <?php else: ?>
                <div class="card-img-placeholder" aria-hidden="true">✝</div>
                <?php endif; ?>
                <?php if (!$ready): ?>
                <span class="badge bg-secondary position-absolute top-0 end-0 m-2">
                    <?= h(t('main.coming_soon')) ?>
                </span>
                <?php endif; ?>
                </div>

                <div class="card-body d-flex flex-column">
                    <h2 class="card-title h5 fw-bold"><?= $title ?></h2>
                    <p class="card-text text-muted small flex-grow-1"><?= $desc ?></p>
                    <a href="<?= $actUrl ?>"
                       class="btn <?= $ready ? 'btn-primary' : 'btn-secondary' ?> btn-sm mt-2 stretched-link">
                        <?= h(t($ready ? 'main.start_activity' : 'main.coming_soon')) ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
