<?php
/**
 * main.php — Activity grid page.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Only GET allowed here
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

$loader     = new ActivityLoader();
$activities = $loader->loadAllMeta();

// Translate title/description for display
foreach ($activities as &$meta) {
    // Keep raw keys — templates call t() directly
}
unset($meta);

$pageTitle = t('main.title');
$bodyClass = 'main-page';

require __DIR__ . '/templates/layout.php';
require __DIR__ . '/templates/main.php';
require __DIR__ . '/templates/layout_end.php';
