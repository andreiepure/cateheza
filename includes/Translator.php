<?php
/**
 * Translator — dot-notation JSON-based translations with fallback to 'ro'.
 */
declare(strict_types=1);

class Translator
{
    private const AVAILABLE_LOCALES = ['ro', 'fr'];
    private const FALLBACK_LOCALE   = 'ro';
    private const SESSION_KEY       = '_locale';

    private string $locale;
    /** @var array<string,mixed> */
    private array $strings = [];
    /** @var array<string,mixed> */
    private array $fallback = [];

    public function __construct(string $locale)
    {
        $this->locale = $this->validateLocale($locale);
        $this->strings  = $this->loadFile($this->locale);
        if ($this->locale !== self::FALLBACK_LOCALE) {
            $this->fallback = $this->loadFile(self::FALLBACK_LOCALE);
        }
    }

    // ── Factory ───────────────────────────────────────────────

    public static function fromSession(): self
    {
        $locale = $_SESSION[self::SESSION_KEY] ?? self::FALLBACK_LOCALE;
        return new self($locale);
    }

    // ── Public API ────────────────────────────────────────────

    /**
     * Get a translation by dot-notation key.
     *
     * @param array<string,string> $replacements  Key → value substitutions (e.g., [':name' => 'Andrei']).
     */
    public function get(string $key, array $replacements = []): string
    {
        $value = $this->resolve($key, $this->strings)
               ?? $this->resolve($key, $this->fallback)
               ?? $key; // Return the key itself as last resort

        if (!empty($replacements)) {
            $value = str_replace(array_keys($replacements), array_values($replacements), $value);
        }

        return $value;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /** @return string[] */
    public static function getAvailableLocales(): array
    {
        return self::AVAILABLE_LOCALES;
    }

    public static function setSessionLocale(string $locale): void
    {
        $validated = in_array($locale, self::AVAILABLE_LOCALES, true) ? $locale : self::FALLBACK_LOCALE;
        $_SESSION[self::SESSION_KEY] = $validated;
    }

    // ── Private helpers ───────────────────────────────────────

    private function validateLocale(string $locale): string
    {
        return in_array($locale, self::AVAILABLE_LOCALES, true) ? $locale : self::FALLBACK_LOCALE;
    }

    private function loadFile(string $locale): array
    {
        $path = BASEPATH . '/lang/' . $locale . '.json';
        if (!file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Traverse dot-notation key through a nested array.
     *
     * @param array<string,mixed> $data
     */
    private function resolve(string $key, array $data): ?string
    {
        $parts   = explode('.', $key);
        $current = $data;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return is_string($current) ? $current : null;
    }
}
