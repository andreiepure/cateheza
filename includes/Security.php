<?php
/**
 * Security — CSRF token management and session-based rate limiting.
 */
declare(strict_types=1);

class Security
{
    private const TOKEN_KEY   = '_csrf_token';
    private const TOKEN_TS_KEY = '_csrf_token_ts';

    // ── CSRF ─────────────────────────────────────────────────

    /**
     * Generate (or return existing) CSRF token, storing it in session.
     */
    public function generateToken(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY]   = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_TS_KEY] = time();
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Returns an escaped hidden input with the CSRF token.
     */
    public function getTokenField(): string
    {
        $token = $this->generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }

    /**
     * Validate submitted CSRF token.
     * Always regenerates after validation attempt (pass or fail).
     *
     * @return bool True if token matches and has not expired.
     */
    public function validateToken(string $submitted): bool
    {
        $stored    = $_SESSION[self::TOKEN_KEY]    ?? '';
        $createdAt = $_SESSION[self::TOKEN_TS_KEY] ?? 0;
        $lifetime  = defined('CSRF_TOKEN_LIFETIME') ? CSRF_TOKEN_LIFETIME : 3600;

        // Invalidate existing token so it cannot be reused
        unset($_SESSION[self::TOKEN_KEY], $_SESSION[self::TOKEN_TS_KEY]);

        if (empty($stored) || empty($submitted)) {
            return false;
        }

        if ((time() - $createdAt) > $lifetime) {
            return false;
        }

        return hash_equals($stored, $submitted);
    }

    // ── Rate limiting ─────────────────────────────────────────

    /**
     * Check and record a rate-limited action.
     *
     * @param string $key    Unique action key (e.g., 'check_answer').
     * @param int    $max    Max allowed calls within $window seconds.
     * @param int    $window Time window in seconds.
     * @return bool True if within limit (allowed), false if limit exceeded.
     */
    public function checkRateLimit(string $key, int $max, int $window): bool
    {
        $sessionKey = '_rl_' . $key;
        $now        = time();

        // Initialise array if missing
        if (!isset($_SESSION[$sessionKey]) || !is_array($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        // Remove timestamps outside the current window
        $_SESSION[$sessionKey] = array_filter(
            $_SESSION[$sessionKey],
            static fn(int $ts): bool => ($now - $ts) < $window
        );
        $_SESSION[$sessionKey] = array_values($_SESSION[$sessionKey]);

        if (count($_SESSION[$sessionKey]) >= $max) {
            return false; // Limit exceeded
        }

        // Record this call
        $_SESSION[$sessionKey][] = $now;
        return true;
    }
}
