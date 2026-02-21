<?php
/**
 * Configuration template — copy to config.php and fill in values.
 * NEVER commit config.php to version control.
 */
declare(strict_types=1);

// ── Environment ─────────────────────────────────────────────
define('APP_ENV', 'production'); // 'development' or 'production'
define('APP_DEBUG', false);       // true only in development

// ── Base URL (no trailing slash) ────────────────────────────
define('APP_URL', 'https://example.com');

// ── Default locale ──────────────────────────────────────────
define('DEFAULT_LOCALE', 'ro');

// ── Session name (change to something non-default) ──────────
define('SESSION_NAME', 'cateheza_sess');

// ── CSRF token lifetime in seconds ──────────────────────────
define('CSRF_TOKEN_LIFETIME', 3600);

// ── Rate limiting: max POSTs per window ─────────────────────
define('RATE_LIMIT_MAX', 30);
define('RATE_LIMIT_WINDOW', 60); // seconds
