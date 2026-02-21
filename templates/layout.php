<?php
/**
 * HTML shell — included by every page.
 * $pageTitle  (string) — <title> suffix
 * $bodyClass  (string, optional) — extra class on <body>
 */
declare(strict_types=1);
if (!defined('BASEPATH')) {
    exit;
}
$pageTitle = isset($pageTitle) ? h($pageTitle) . ' — ' : '';
$bodyClass = isset($bodyClass) ? h($bodyClass) : '';
$currentLocale = $translator->getLocale();
?>
<!DOCTYPE html>
<html lang="<?= h($currentLocale) ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title><?= $pageTitle ?><?= h(t('site.title')) ?></title>
    <meta name="description" content="<?= h(t('site.tagline')) ?>">

    <!-- Bootstrap 5.3.8 CSS (local) -->
    <link href="<?= h(APP_URL) ?>/assets/css/bootstrap.min.css?v=<?= filemtime(BASEPATH . '/assets/css/bootstrap.min.css') ?>" rel="stylesheet">

    <!-- Custom styles -->
    <link rel="stylesheet" href="<?= h(APP_URL) ?>/assets/css/app.css?v=<?= filemtime(BASEPATH . '/assets/css/app.css') ?>">
</head>
<body class="<?= $bodyClass ?>">

<!-- Navigation -->
<nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= h(APP_URL) ?>/main.php">
            <span class="me-2">✝</span><?= h(t('site.title')) ?>
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="<?= h(t('nav.activities')) ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link" href="<?= h(APP_URL) ?>/main.php">
                        <?= h(t('nav.activities')) ?>
                    </a>
                </li>
                <!-- Language switcher -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <?= h(t('nav.language')) ?>:
                        <?= h(strtoupper($currentLocale)) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach (\Translator::getAvailableLocales() as $loc): ?>
                        <li>
                            <form method="post" action="<?= h(APP_URL) ?>/index.php">
                                <?= $security->getTokenField() ?>
                                <input type="hidden" name="action" value="set_language">
                                <input type="hidden" name="lang" value="<?= h($loc) ?>">
                                <input type="hidden" name="redirect" value="<?= h(APP_URL . ($_SERVER['REQUEST_URI'] ?? '/main.php')) ?>">
                                <button type="submit" class="dropdown-item<?= $currentLocale === $loc ? ' active' : '' ?>">
                                    <?= h(t('landing.' . $loc)) ?>
                                </button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main content -->
<main class="py-4">
<?php // Page content is rendered between layout start/end calls ?>
