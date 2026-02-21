<?php
/**
 * activity.php — GET: render activity (learn + quiz phases).
 *               POST: AJAX answer-check endpoint.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$loader = new ActivityLoader();

// ── POST: check answer (AJAX) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Must be an XHR (belt-and-suspenders; real check is CSRF)
    header('Content-Type: application/json; charset=utf-8');

    $respond = static function (array $payload, int $status = 200): never {
        http_response_code($status);
        echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    };

    // Rate limit (session-based)
    $maxRequests = defined('RATE_LIMIT_MAX')    ? RATE_LIMIT_MAX    : 30;
    $window      = defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 60;
    if (!$security->checkRateLimit('check_answer', $maxRequests, $window)) {
        $respond(['error' => t('errors.rate_limit')], 429);
    }

    // Validate action
    $action = $_POST['action'] ?? '';
    if ($action !== 'check_answer') {
        $respond(['error' => t('errors.server_error')], 400);
    }

    // Validate CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!$security->validateToken($submittedToken)) {
        // Issue a fresh token so the client can retry
        $newToken = $security->generateToken();
        $respond(['error' => t('errors.csrf_invalid'), 'newToken' => $newToken], 403);
    }

    // Generate new CSRF token for next request
    $newToken = $security->generateToken();

    // Validate slug
    $slug = $_POST['slug'] ?? '';
    if (!ActivityLoader::validateSlug($slug)) {
        $respond(['error' => t('errors.invalid_slug'), 'newToken' => $newToken], 400);
    }

    // Validate question_id (alphanumeric + dash, max 20 chars)
    $questionId = $_POST['question_id'] ?? '';
    if (!preg_match('/^[a-z0-9\-]{1,20}$/i', $questionId)) {
        $respond(['error' => t('errors.server_error'), 'newToken' => $newToken], 400);
    }

    // Validate selected_key (single letter a-z)
    $selectedKey = $_POST['selected_key'] ?? '';
    if (!preg_match('/^[a-z]$/', $selectedKey)) {
        $respond(['error' => t('errors.server_error'), 'newToken' => $newToken], 400);
    }

    try {
        $result = $loader->checkAnswer($slug, $questionId, $selectedKey);
    } catch (RuntimeException $e) {
        $respond(['error' => t('errors.server_error'), 'newToken' => $newToken], 500);
    }

    $respond([
        'isCorrect'  => $result['isCorrect'],
        'correctKey' => $result['correctKey'],
        'newToken'   => $newToken,
    ]);
}

// ── GET: render activity page ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

// Validate slug
$slug = $_GET['slug'] ?? '';
if (!ActivityLoader::validateSlug($slug)) {
    http_response_code(400);
    $pageTitle = t('errors.invalid_slug');
    require __DIR__ . '/templates/layout.php';
    echo '<div class="container mt-4"><div class="alert alert-danger">' . h(t('errors.invalid_slug')) . '</div></div>';
    require __DIR__ . '/templates/layout_end.php';
    exit;
}

// Load activity data
try {
    $meta      = $loader->loadMeta($slug);
    $learnData = $loader->loadLearn($slug);
    $quizData  = $loader->loadQuiz($slug);
} catch (RuntimeException $e) {
    http_response_code(404);
    $pageTitle = t('errors.not_found');
    require __DIR__ . '/templates/layout.php';
    echo '<div class="container mt-4"><div class="alert alert-warning">' . h(t('errors.not_found')) . '</div></div>';
    require __DIR__ . '/templates/layout_end.php';
    exit;
}

// Show work-in-progress page for activities not yet ready
if (empty($meta['ready'])) {
    $pageTitle = t($meta['title_key'] ?? '');
    require __DIR__ . '/templates/layout.php';
    echo '<div class="container mt-5 text-center">'
        . '<div class="mb-4" style="font-size:3rem">🚧</div>'
        . '<h1 class="h3 fw-bold mb-3">' . h(t('errors.wip_title')) . '</h1>'
        . '<p class="text-muted mb-4">' . h(t('errors.wip_body')) . '</p>'
        . '<a href="' . h(APP_URL) . '/main.php" class="btn btn-primary">' . h(t('main.back')) . '</a>'
        . '</div>';
    require __DIR__ . '/templates/layout_end.php';
    exit;
}

// Translate learn data server-side (hotspots or slides)
if (isset($learnData['hotspots']) && is_array($learnData['hotspots'])) {
    foreach ($learnData['hotspots'] as &$spot) {
        $spot['title'] = t($spot['title_key'] ?? '');
        $spot['body']  = t($spot['body_key']  ?? '');
    }
    unset($spot);
}
if (isset($learnData['slides']) && is_array($learnData['slides'])) {
    foreach ($learnData['slides'] as &$slide) {
        $slide['bodyText'] = t($slide['body_key']      ?? '');
        $slide['imageAlt'] = t($slide['image_alt_key'] ?? '');
        $slide['imageSrc'] = APP_URL . '/' . ($slide['image'] ?? '');
    }
    unset($slide);
}

// Translate quiz question texts and choice labels server-side
if (isset($quizData['questions']) && is_array($quizData['questions'])) {
    foreach ($quizData['questions'] as &$q) {
        $q['questionText'] = t($q['question_key'] ?? '');
        if (isset($q['choices']) && is_array($q['choices'])) {
            foreach ($q['choices'] as &$choice) {
                $choice['labelText'] = t($choice['label_key'] ?? '');
            }
            unset($choice);
        }
    }
    unset($q);
}

$csrfToken = $security->generateToken();
$postUrl   = APP_URL . '/activity.php';
$pageTitle = t($meta['title_key'] ?? '');
$bodyClass = 'activity-page';

require __DIR__ . '/templates/layout.php';
require __DIR__ . '/templates/activity.php';
require __DIR__ . '/templates/layout_end.php';
