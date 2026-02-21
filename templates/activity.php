<?php
/**
 * Activity page partial — Learn + Quiz phases.
 *
 * Required variables:
 *   $meta       — from ActivityLoader::loadMeta()
 *   $learnData  — from ActivityLoader::loadLearn()
 *   $quizData   — from ActivityLoader::loadQuiz() (no correct answers)
 *   $slug       — validated slug string
 *   $postUrl    — URL for AJAX POST
 *   $csrfToken  — from Security::generateToken()
 */
declare(strict_types=1);
if (!defined('BASEPATH')) {
    exit;
}

$title = t($meta['title_key'] ?? '');
?>
<div class="container">

    <!-- Back link -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= h(APP_URL) ?>/main.php"><?= h(t('nav.activities')) ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= h($title) ?></li>
        </ol>
    </nav>

    <h1 class="h2 fw-bold mb-4"><?= h($title) ?></h1>

    <!-- Phase tabs (Alpine driven) -->
    <!-- @start-quiz.window listens for the event dispatched from the nested hotspotViewer -->
    <div x-data="{ phase: 'learn' }" @start-quiz.window="phase = 'quiz'">

        <!-- Tab switcher -->
        <div class="phase-tabs" role="tablist">
            <button class="phase-tab"
                    :class="{ active: phase === 'learn' }"
                    @click="phase = 'learn'"
                    role="tab"
                    :aria-selected="phase === 'learn'"
                    id="tab-learn">
                <?= h(t('activity.phase_learn')) ?>
            </button>
            <button class="phase-tab"
                    :class="{ active: phase === 'quiz' }"
                    @click="phase = 'quiz'"
                    role="tab"
                    :aria-selected="phase === 'quiz'"
                    id="tab-quiz">
                <?= h(t('activity.phase_quiz')) ?>
            </button>
        </div>

        <!-- ── LEARN PHASE ─────────────────────────────────── -->
        <div x-show="phase === 'learn'"
             x-transition
             role="tabpanel"
             aria-labelledby="tab-learn">

            <!-- Inline JSON data island (never executed as script) -->
            <script type="application/json" id="learn-data">
                <?= json_encode($learnData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            </script>

            <?php $learnType = $learnData['type'] ?? 'hotspots'; ?>

            <?php if ($learnType === 'slides'): ?>
            <!-- ── SLIDE VIEWER ─────────────────────────────── -->
            <div x-data="slideViewer()">
                <p class="text-muted small mb-3"><?= h(t('activity.slide_instruction')) ?></p>

                <div class="slide-card mb-3" style="max-width: 600px; margin: 0 auto;">
                    <template x-if="currentSlide">
                        <div>
                            <div class="slide-image-wrapper">
                                <img :src="currentSlide.imageSrc"
                                     :alt="currentSlide.imageAlt"
                                     class="slide-image">
                            </div>
                            <p class="slide-body" x-text="currentSlide.bodyText"></p>
                        </div>
                    </template>
                </div>

                <!-- Navigation row -->
                <div class="d-flex align-items-center justify-content-center gap-3 mb-3"
                     style="max-width: 600px; margin: 0 auto;">
                    <button class="btn btn-outline-secondary btn-sm"
                            @click="prev()" :disabled="isFirst"
                            aria-label="<?= h(t('activity.previous')) ?>">
                        &larr; <?= h(t('activity.previous')) ?>
                    </button>

                    <!-- Dot indicators -->
                    <div class="d-flex gap-2" role="tablist">
                        <template x-for="(s, i) in slides" :key="i">
                            <button class="slide-dot"
                                    :class="{ active: i === currentIndex }"
                                    @click="goTo(i)"
                                    role="tab"
                                    :aria-selected="i === currentIndex"
                                    :aria-label="(i + 1) + ' / ' + slides.length">
                            </button>
                        </template>
                    </div>

                    <button class="btn btn-outline-secondary btn-sm"
                            @click="next()" :disabled="isLast"
                            aria-label="<?= h(t('activity.next')) ?>">
                        <?= h(t('activity.next')) ?> &rarr;
                    </button>
                </div>

                <p class="text-center text-muted small mb-4">
                    <span x-text="currentIndex + 1"></span> / <span x-text="slides.length"></span>
                </p>

                <!-- Start quiz CTA -->
                <div class="text-center mt-2">
                    <button class="btn btn-success btn-lg"
                            @click="$dispatch('start-quiz')">
                        <?= h(t('activity.start_quiz')) ?>
                    </button>
                </div>
            </div>

            <?php else: ?>
            <!-- ── HOTSPOT VIEWER ────────────────────────────── -->
            <div x-data="hotspotViewer()">
                <p class="text-muted mb-3"><?= h(t('activity.learn_instruction')) ?></p>

                <!-- Image + hotspot pins -->
                <div class="hotspot-container mb-4" style="max-width: 700px; margin: 0 auto;">
                    <?php
                    $imgSrc = h(APP_URL) . '/' . h($learnData['image'] ?? '');
                    $imgAlt = h(t($learnData['image_alt_key'] ?? ''));
                    ?>
                    <img src="<?= $imgSrc ?>"
                         alt="<?= $imgAlt ?>"
                         @load="onImageLoad()"
                         x-show="imageLoaded"
                         style="display:none">

                    <div x-show="!imageLoaded"
                         class="activity-card card-img-placeholder rounded"
                         style="height:350px" aria-hidden="true">✝</div>

                    <template x-for="(spot, i) in hotspots" :key="spot.id">
                        <button
                            class="hotspot-pin"
                            :style="'left:' + spot.x + '%;top:' + spot.y + '%;'"
                            @click="openHotspot(spot)"
                            :aria-label="'<?= h(t('activity.learn_phase')) ?> ' + (i + 1)"
                            :title="spot.title_key"
                            x-show="imageLoaded">
                            <span x-text="i + 1" aria-hidden="true"></span>
                        </button>
                    </template>
                </div>

                <!-- Hotspot detail panel -->
                <div x-show="activeHotspot !== null"
                     x-transition
                     role="dialog"
                     aria-modal="true"
                     :aria-label="activeHotspot ? activeHotspot.title_key : ''"
                     class="hotspot-panel mb-4"
                     style="max-width: 600px; margin: 0 auto;"
                     @keydown.escape="closeHotspot()">

                    <button class="btn btn-sm btn-outline-secondary hotspot-close"
                            @click="closeHotspot()"
                            aria-label="<?= h(t('activity.close')) ?>">
                        &times; <?= h(t('activity.close')) ?>
                    </button>

                    <template x-if="activeHotspot">
                        <div>
                            <h3 class="h5 fw-bold mb-2 pe-5"
                                x-text="activeHotspot.title"></h3>
                            <p class="mb-0"
                               x-text="activeHotspot.body"></p>
                        </div>
                    </template>
                </div>

                <!-- Start quiz CTA -->
                <div class="text-center mt-4">
                    <button class="btn btn-success btn-lg"
                            @click="$dispatch('start-quiz')">
                        <?= h(t('activity.start_quiz')) ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── QUIZ PHASE ──────────────────────────────────── -->
        <div x-show="phase === 'quiz'"
             x-transition
             role="tabpanel"
             aria-labelledby="tab-quiz">

            <!-- Inline quiz data island (no correct answers) -->
            <script type="application/json" id="quiz-data">
                <?= json_encode($quizData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            </script>

            <div x-data="quizEngine()"
                 data-post-url="<?= h($postUrl) ?>"
                 data-csrf-token="<?= h($csrfToken) ?>"
                 data-activity-slug="<?= h($slug) ?>"
                 data-label-submit="<?= h(t('activity.submit')) ?>"
                 data-label-next-question="<?= h(t('activity.next_question')) ?>"
                 data-label-finish="<?= h(t('activity.finish')) ?>"
                 data-label-correct="<?= h(t('activity.correct')) ?>"
                 data-label-incorrect="<?= h(t('activity.incorrect')) ?>"
                 data-label-retry="<?= h(t('activity.retry')) ?>"
                 data-label-back-to-acts="<?= h(t('activity.back_to_activities')) ?>"
                 data-label-question="<?= h(t('activity.question')) ?>"
                 data-label-of="<?= h(t('activity.of')) ?>"
                 data-label-score-label="<?= h(t('activity.score_label')) ?>"
                 data-label-excellent="<?= h(t('activity.result_excellent')) ?>"
                 data-label-good="<?= h(t('activity.result_good')) ?>"
                 data-label-try-again="<?= h(t('activity.result_try_again')) ?>">

                <!-- ── Quiz questions ── -->
                <div x-show="phase === 'quiz'" style="max-width: 600px; margin: 0 auto;">

                    <!-- Progress bar -->
                    <div class="quiz-progress mb-3" role="progressbar"
                         :aria-valuenow="progressPercent"
                         aria-valuemin="0" aria-valuemax="100">
                        <div class="quiz-progress-fill" :style="'width:' + progressPercent + '%'"></div>
                    </div>
                    <p class="text-muted small mb-3">
                        <span x-text="labels.question"></span>
                        <span x-text="currentIndex + 1"></span>
                        <span x-text="labels.of"></span>
                        <span x-text="questions.length"></span>
                    </p>

                    <!-- Error message -->
                    <div x-show="errorMsg" class="alert alert-danger" role="alert" x-text="errorMsg"></div>

                    <template x-if="currentQuestion">
                        <div>
                            <!-- Question text (translated server-side) -->
                            <p class="fs-5 fw-semibold mb-4"
                               x-text="currentQuestion.questionText"></p>

                            <!-- Answer choices -->
                            <div class="d-grid gap-2 mb-4">
                                <template x-for="choice in currentQuestion.choices" :key="choice.key">
                                    <button
                                        class="quiz-choice"
                                        :class="choiceClass(choice.key)"
                                        @click="selectChoice(choice.key)"
                                        :disabled="submitted || loading"
                                        :aria-pressed="selectedKey === choice.key">
                                        <span x-text="choice.labelText"></span>
                                    </button>
                                </template>
                            </div>

                            <!-- Feedback -->
                            <div x-show="submitted" x-transition class="alert mb-3"
                                 :class="isCorrect ? 'alert-success' : 'alert-danger'"
                                 role="status">
                                <span x-text="isCorrect ? labels.correct : labels.incorrect"></span>
                            </div>

                            <!-- Action buttons -->
                            <div class="d-flex gap-2">
                                <!-- Submit (before answered) -->
                                <button x-show="!submitted"
                                        @click="submitAnswer()"
                                        :disabled="!selectedKey || loading"
                                        class="btn btn-primary flex-grow-1">
                                    <span x-show="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    <span x-text="labels.submit"></span>
                                </button>

                                <!-- Next / Finish (after answered) -->
                                <button x-show="submitted"
                                        @click="nextQuestion()"
                                        class="btn btn-success flex-grow-1"
                                        x-text="isLastQuestion ? labels.finish : labels.nextQuestion">
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ── Results screen ── -->
                <div x-show="phase === 'result'" x-transition style="max-width: 500px; margin: 0 auto; text-align: center;">
                    <div class="score-circle" :class="scoreClass">
                        <span x-text="score"></span>
                        <span style="font-size:0.7rem; font-weight:400" x-text="'/' + questions.length"></span>
                    </div>

                    <h2 class="h4 fw-bold mb-2"><?= h(t('activity.score')) ?></h2>
                    <p class="mb-1">
                        <strong x-text="score"></strong>
                        <span x-text="labels.scoreLabel"></span>
                        <strong x-text="questions.length"></strong>
                    </p>
                    <p class="text-muted mb-4" x-text="resultMessage"></p>

                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <button class="btn btn-outline-primary" @click="retry()" x-text="labels.retry"></button>
                        <a class="btn btn-primary" href="<?= h(APP_URL) ?>/main.php"
                           x-text="labels.backToActs"></a>
                    </div>
                </div>

            </div><!-- /quizEngine -->
        </div><!-- /quiz phase -->

    </div><!-- /phase tabs -->
</div>
