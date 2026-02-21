<?php
/**
 * index.php — Landing page + language switcher POST handler.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// ── POST: set language ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action !== 'set_language') {
        http_response_code(400);
        exit;
    }

    // Validate CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!$security->validateToken($submittedToken)) {
        http_response_code(403);
        $pageTitle = h(t('errors.csrf_invalid'));
        require __DIR__ . '/templates/layout.php';
        echo '<div class="container"><div class="alert alert-danger mt-4">' . h(t('errors.csrf_invalid')) . '</div></div>';
        require __DIR__ . '/templates/layout_end.php';
        exit;
    }

    // Validate lang param (whitelist)
    $lang = $_POST['lang'] ?? '';
    if (!in_array($lang, \Translator::getAvailableLocales(), true)) {
        $lang = DEFAULT_LOCALE;
    }

    // Persist to session
    \Translator::setSessionLocale($lang);

    // Reload translator with new locale
    $translator = \Translator::fromSession();

    // Redirect target: validate it starts with APP_URL to prevent open redirect
    $redirect = $_POST['redirect'] ?? '';
    $redirect = filter_var($redirect, FILTER_SANITIZE_URL);
    if (!str_starts_with($redirect, APP_URL)) {
        $redirect = APP_URL . '/main.php';
    }

    header('Location: ' . $redirect);
    exit;
}

// ── GET: render landing page ──────────────────────────────
$pageTitle = t('landing.welcome');
$bodyClass = 'landing-page';

require __DIR__ . '/templates/layout.php';
require __DIR__ . '/templates/landing.php';
require __DIR__ . '/templates/layout_end.php';
