<?php
/**
 * Bootstrap — loaded at the very top of every entry point.
 * Sets up session security, loads config, defines helpers.
 */
declare(strict_types=1);

// ── Path constant ────────────────────────────────────────────
define('BASEPATH', dirname(__DIR__));

// ── Load config ──────────────────────────────────────────────
$configPath = BASEPATH . '/config/config.php';
if (!file_exists($configPath)) {
    // Fall back to example config for first-run; production must have config.php
    $configPath = BASEPATH . '/config/config.example.php';
}
require $configPath;

// ── Error reporting ──────────────────────────────────────────
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ── Session security (must be set BEFORE session_start) ──────
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.gc_maxlifetime', '7200');

// cookie_secure should be '1' in production; allow '0' in dev
$secureCookie = (defined('APP_ENV') && APP_ENV === 'production') ? '1' : '0';
ini_set('session.cookie_secure', $secureCookie);

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'cateheza_sess');
session_start();

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['_initiated'])) {
    session_regenerate_id(true);
    $_SESSION['_initiated'] = true;
    $_SESSION['_created']   = time();
} elseif (time() - ($_SESSION['_created'] ?? 0) > 1800) {
    // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// ── Security response headers ─────────────────────────────────
// (Supplement .htaccess — belt-and-suspenders for PHP-served requests)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ── Load core classes ─────────────────────────────────────────
require BASEPATH . '/includes/Security.php';
require BASEPATH . '/includes/Translator.php';
require BASEPATH . '/includes/ActivityLoader.php';

// ── Global instances ──────────────────────────────────────────
$security   = new Security();
$translator = Translator::fromSession();

// ── Helper: HTML-escape ───────────────────────────────────────
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Helper: translate ─────────────────────────────────────────
function t(string $key, array $replacements = []): string
{
    global $translator;
    return $translator->get($key, $replacements);
}
