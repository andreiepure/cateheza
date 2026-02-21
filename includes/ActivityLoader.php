<?php
/**
 * ActivityLoader — loads and validates activity JSON data.
 * Correct answers are NEVER returned to the caller of loadQuiz().
 */
declare(strict_types=1);

class ActivityLoader
{
    /** Hardcoded whitelist — the only valid slugs. */
    public const ALLOWED_SLUGS = [
        'final-judgement',
        'intampinarea-domnului',
        'ce-este-biserica',
        'pilda-semanatorului',
        'saint-nektarios',
        'sfintii-arhangheli',
        'sfanta-parascheva',
        'sf-dimitrie',
        'bogatul-nemilostic',
        'botezul-domnului',
        'zaheu-vamesul',
        'pilda-samarineanului',
        'fiul-risipitor',
        'sf-andrei',
    ];

    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = BASEPATH . '/activities';
    }

    // ── Validation ────────────────────────────────────────────

    public static function validateSlug(string $slug): bool
    {
        return in_array($slug, self::ALLOWED_SLUGS, true);
    }

    // ── Loaders ───────────────────────────────────────────────

    /** @return array<string,mixed> */
    public function loadMeta(string $slug): array
    {
        return $this->loadJsonFile($slug, 'meta.json');
    }

    /**
     * Load learn data; validates hotspot coordinates are 0.0–100.0.
     * @return array<string,mixed>
     */
    public function loadLearn(string $slug): array
    {
        $data = $this->loadJsonFile($slug, 'learn.json');

        // Validate hotspot coordinates
        if (isset($data['hotspots']) && is_array($data['hotspots'])) {
            foreach ($data['hotspots'] as &$hotspot) {
                if (!isset($hotspot['x'], $hotspot['y'])) {
                    continue;
                }
                $hotspot['x'] = $this->clampPercent((float) $hotspot['x']);
                $hotspot['y'] = $this->clampPercent((float) $hotspot['y']);
            }
            unset($hotspot);
        }

        return $data;
    }

    /**
     * Load quiz data with correct answers STRIPPED.
     * Correct answers are kept only internally for checkAnswer().
     *
     * @return array<string,mixed>
     */
    public function loadQuiz(string $slug): array
    {
        $data = $this->loadJsonFile($slug, 'quiz.json');
        return $this->stripCorrectAnswers($data);
    }

    /**
     * Check a submitted answer.
     * Loads quiz JSON server-side; never exposes correct field.
     *
     * @return array{isCorrect: bool, correctKey: string}
     */
    public function checkAnswer(string $slug, string $questionId, string $selectedKey): array
    {
        $data = $this->loadJsonFile($slug, 'quiz.json');
        $questions = $data['questions'] ?? [];

        foreach ($questions as $q) {
            if (($q['id'] ?? '') !== $questionId) {
                continue;
            }
            $correctKey = $q['correct'] ?? '';

            // Validate selectedKey is a known choice for this question
            $validKeys = array_column($q['choices'] ?? [], 'key');
            if (!in_array($selectedKey, $validKeys, true)) {
                return ['isCorrect' => false, 'correctKey' => ''];
            }

            return [
                'isCorrect'  => $selectedKey === $correctKey,
                'correctKey' => $correctKey,
            ];
        }

        return ['isCorrect' => false, 'correctKey' => ''];
    }

    /**
     * Load meta for all activities (sorted by 'order').
     * @return list<array<string,mixed>>
     */
    public function loadAllMeta(): array
    {
        $results = [];
        foreach (self::ALLOWED_SLUGS as $slug) {
            try {
                $results[] = $this->loadMeta($slug);
            } catch (RuntimeException) {
                // Skip activities with missing/invalid meta
            }
        }

        usort($results, static fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
        return $results;
    }

    // ── Private helpers ───────────────────────────────────────

    /**
     * Load and decode a JSON file for a given slug.
     * Verifies the resolved path stays within the activities directory.
     *
     * @return array<string,mixed>
     * @throws RuntimeException on path traversal, missing file, or invalid JSON.
     */
    private function loadJsonFile(string $slug, string $filename): array
    {
        if (!self::validateSlug($slug)) {
            throw new RuntimeException("Invalid slug: {$slug}");
        }

        // Build expected path using whitelisted slug only
        $expectedBase = $this->baseDir . '/' . $slug . '/';
        $filePath     = $expectedBase . $filename;

        // realpath() check prevents path traversal
        $realPath = realpath($filePath);
        if ($realPath === false) {
            throw new RuntimeException("Activity file not found: {$slug}/{$filename}");
        }
        $realBase = realpath($this->baseDir);
        if ($realBase === false || strncmp($realPath, $realBase . DIRECTORY_SEPARATOR, strlen($realBase) + 1) !== 0) {
            throw new RuntimeException("Path traversal detected for: {$slug}/{$filename}");
        }

        $content = file_get_contents($realPath);
        if ($content === false) {
            throw new RuntimeException("Cannot read file: {$slug}/{$filename}");
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new RuntimeException("Invalid JSON in: {$slug}/{$filename}");
        }

        return $data;
    }

    /**
     * Recursively strip 'correct' keys from quiz data.
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function stripCorrectAnswers(array $data): array
    {
        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as &$q) {
                unset($q['correct']);
            }
            unset($q);
        }
        return $data;
    }

    private function clampPercent(float $value): float
    {
        return max(0.0, min(100.0, $value));
    }
}
