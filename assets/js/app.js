/**
 * Orthodox Catechesis — Alpine.js Components
 *
 * IMPORTANT: Uses the @alpinejs/csp build.
 * - No eval(), no new Function(), no new AsyncFunction().
 * - All components MUST be registered via Alpine.data().
 * - x-data attributes must use registered names only (no inline objects).
 * - Event handlers must reference named methods (no inline assignments).
 *
 * Components:
 *  - phaseController — learn/quiz phase tab switcher (activity page outer wrapper)
 *  - hotspotViewer   — interactive image with clickable hotspot pins
 *  - slideViewer     — slide-through image+text viewer
 *  - quizEngine      — multi-question quiz with server-side answer checking
 */

/* ── phaseController ────────────────────────────────────── */
function phaseController() {
    return {
        phase: 'learn',
        quizUnlocked: false,

        init() {
            // Hotspot activities have no sequential constraint — unlock quiz immediately.
            // Slide activities set data-quiz-gated and unlock only after slides-completed.
            if (!this.$el.dataset.quizGated) {
                this.quizUnlocked = true;
            }
        },

        unlockQuiz() {
            this.quizUnlocked = true;
        },

        startQuiz() {
            if (this.quizUnlocked) this.phase = 'quiz';
        },

        setLearnPhase() {
            this.phase = 'learn';
        },

        setQuizPhase() {
            if (this.quizUnlocked) this.phase = 'quiz';
        },
    };
}

/* ── hotspotViewer ──────────────────────────────────────── */
function hotspotViewer() {
    return {
        hotspots: [],
        activeHotspot: null,
        imageLoaded: false,
        _lastFocused: null,

        init() {
            const el = document.getElementById('learn-data');
            if (!el) return;
            try {
                const data = JSON.parse(el.textContent);
                this.hotspots = Array.isArray(data.hotspots) ? data.hotspots : [];
            } catch (e) {
                console.error('hotspotViewer: failed to parse learn data', e);
            }

            // Keyboard: close on Escape
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.activeHotspot) {
                    this.closeHotspot();
                }
            });
        },

        openHotspot(spot) {
            // Remember which pin was focused so we can restore focus on close
            this._lastFocused = document.activeElement;
            this.activeHotspot = spot;

            // Focus the panel's close button after next render
            this.$nextTick(() => {
                const closeBtn = this.$el.querySelector('.hotspot-close');
                if (closeBtn) closeBtn.focus();
            });
        },

        closeHotspot() {
            this.activeHotspot = null;
            // Restore focus to the pin that opened this panel
            this.$nextTick(() => {
                if (this._lastFocused) {
                    this._lastFocused.focus();
                    this._lastFocused = null;
                }
            });
        },

        onImageLoad() {
            this.imageLoaded = true;
        },

        // Dispatch event to parent phaseController to switch to quiz phase.
        // Named method required by the @alpinejs/csp build (no inline $dispatch calls).
        requestQuiz() {
            this.$dispatch('start-quiz');
        },

        // Returns the 1-based display number for a hotspot pin.
        // Named getter required by the @alpinejs/csp build (no inline arithmetic).
        hotspotNumber(i) {
            return i + 1;
        },
    };
}

/* ── slideViewer ────────────────────────────────────────── */
function slideViewer() {
    return {
        slides: [],
        currentIndex: 0,
        seenLast: false,

        init() {
            const el = document.getElementById('learn-data');
            if (!el) return;
            try {
                const data = JSON.parse(el.textContent);
                this.slides = Array.isArray(data.slides) ? data.slides : [];
            } catch (e) {
                console.error('slideViewer: failed to parse learn data', e);
            }

            // Single-slide activity: already at last slide on load.
            if (this.slides.length > 0 && this.isLast) {
                this.reachedEnd();
            }

            // Keyboard: left/right arrow navigation
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') this.next();
                if (e.key === 'ArrowLeft')  this.prev();
            });
        },

        get currentSlide() {
            return this.slides[this.currentIndex] || null;
        },

        get isFirst() {
            return this.currentIndex === 0;
        },

        get isLast() {
            return this.currentIndex === this.slides.length - 1;
        },

        // 1-based display index for x-text (no inline arithmetic in CSP build).
        get displayCurrentIndex() {
            return this.currentIndex + 1;
        },

        next() {
            if (!this.isLast) {
                this.currentIndex++;
                if (this.isLast) this.reachedEnd();
            }
        },

        prev() {
            if (!this.isFirst) this.currentIndex--;
        },

        goTo(index) {
            this.currentIndex = Math.max(0, Math.min(this.slides.length - 1, index));
            if (this.isLast) this.reachedEnd();
        },

        // Called the first time the user reaches the last slide.
        // Dispatches slides-completed so phaseController can unlock the quiz.
        reachedEnd() {
            if (!this.seenLast) {
                this.seenLast = true;
                this.$dispatch('slides-completed');
            }
        },

        // Aria label for dot nav buttons (no inline string concat in CSP build).
        dotAriaLabel(i) {
            return (i + 1) + ' / ' + this.slides.length;
        },

        // Dispatch event to parent phaseController to switch to quiz phase.
        requestQuiz() {
            this.$dispatch('start-quiz');
        },
    };
}

/* ── quizEngine ─────────────────────────────────────────── */
function quizEngine() {
    return {
        questions: [],
        currentIndex: 0,
        selectedKey: null,
        submitted: false,
        isCorrect: null,
        revealedCorrectKey: null,
        answers: [],      // [{questionId, selectedKey, isCorrect}]
        phase: 'quiz',    // 'quiz' | 'result'
        loading: false,
        errorMsg: null,

        // UI labels injected from PHP via data-* attributes (avoids inline JS strings in CSP)
        labels: {},

        init() {
            // Read quiz data from the inline JSON island
            const el = document.getElementById('quiz-data');
            if (el) {
                try {
                    const data = JSON.parse(el.textContent);
                    this.questions = Array.isArray(data.questions) ? data.questions : [];
                } catch (e) {
                    console.error('quizEngine: failed to parse quiz data', e);
                }
            }

            // Read labels from data-* attributes on the root element
            const root = this.$el;
            this.labels = {
                submit:        root.dataset.labelSubmit       || 'Submit',
                nextQuestion:  root.dataset.labelNextQuestion || 'Next question',
                finish:        root.dataset.labelFinish       || 'Finish',
                correct:       root.dataset.labelCorrect      || 'Correct!',
                incorrect:     root.dataset.labelIncorrect    || 'Incorrect!',
                retry:         root.dataset.labelRetry        || 'Retry',
                backToActs:    root.dataset.labelBackToActs   || 'Back to activities',
                question:      root.dataset.labelQuestion     || 'Question',
                of:            root.dataset.labelOf           || 'of',
                scoreLabel:    root.dataset.labelScoreLabel   || 'correct answers out of',
                excellent:     root.dataset.labelExcellent    || 'Excellent!',
                good:          root.dataset.labelGood         || 'Good!',
                tryAgain:      root.dataset.labelTryAgain     || 'Keep practising!',
                postUrl:       root.dataset.postUrl           || '',
                csrfToken:     root.dataset.csrfToken         || '',
                activitySlug:  root.dataset.activitySlug      || '',
            };
        },

        get currentQuestion() {
            return this.questions[this.currentIndex] || null;
        },

        get isLastQuestion() {
            return this.currentIndex === this.questions.length - 1;
        },

        get progressPercent() {
            if (this.questions.length === 0) return 0;
            return Math.round(((this.currentIndex) / this.questions.length) * 100);
        },

        get score() {
            return this.answers.filter(a => a.isCorrect).length;
        },

        get scoreClass() {
            const pct = this.score / this.questions.length;
            if (pct >= 1)   return 'excellent';
            if (pct >= 0.5) return 'good';
            return 'low';
        },

        get resultMessage() {
            const pct = this.score / this.questions.length;
            if (pct >= 1)   return this.labels.excellent;
            if (pct >= 0.5) return this.labels.good;
            return this.labels.tryAgain;
        },

        // 1-based display index for x-text (no inline arithmetic in CSP build).
        get quizDisplayIndex() {
            return this.currentIndex + 1;
        },

        selectChoice(key) {
            if (this.submitted || this.loading) return;
            this.selectedKey = key;
        },

        async submitAnswer() {
            if (!this.selectedKey || this.submitted || this.loading) return;
            const q = this.currentQuestion;
            if (!q) return;

            this.loading = true;
            this.errorMsg = null;

            try {
                const body = new URLSearchParams({
                    action:      'check_answer',
                    csrf_token:  this.labels.csrfToken,
                    slug:        this.labels.activitySlug,
                    question_id: q.id,
                    selected_key: this.selectedKey,
                });

                const res = await fetch(this.labels.postUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                               'X-Requested-With': 'XMLHttpRequest' },
                    body:    body.toString(),
                });

                if (!res.ok) {
                    throw new Error('Server error: ' + res.status);
                }

                const json = await res.json();

                if (json.error) {
                    this.errorMsg = json.error;
                    // Refresh CSRF token if provided
                    if (json.newToken) this.labels.csrfToken = json.newToken;
                    return;
                }

                this.isCorrect         = json.isCorrect;
                this.revealedCorrectKey = json.correctKey;
                // Update CSRF token for next request
                if (json.newToken) this.labels.csrfToken = json.newToken;

                this.submitted = true;
                this.answers.push({
                    questionId:  q.id,
                    selectedKey: this.selectedKey,
                    isCorrect:   json.isCorrect,
                });

            } catch (e) {
                this.errorMsg = 'Network error. Please try again.';
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        nextQuestion() {
            if (this.isLastQuestion) {
                this.phase = 'result';
                return;
            }
            this.currentIndex++;
            this.selectedKey = null;
            this.submitted   = false;
            this.isCorrect   = null;
            this.revealedCorrectKey = null;
        },

        retry() {
            this.currentIndex = 0;
            this.selectedKey  = null;
            this.submitted    = false;
            this.isCorrect    = null;
            this.revealedCorrectKey = null;
            this.answers      = [];
            this.phase        = 'quiz';
            this.errorMsg     = null;
        },

        choiceClass(key) {
            if (!this.submitted) {
                return this.selectedKey === key ? 'selected' : '';
            }
            if (key === this.revealedCorrectKey) return 'correct';
            if (key === this.selectedKey && !this.isCorrect) return 'incorrect';
            return '';
        },
    };
}

/* ── Register all components with Alpine ────────────────── */
// Must use the alpine:init event so components are registered before Alpine
// processes the DOM. Required by the @alpinejs/csp build.
document.addEventListener('alpine:init', () => {
    Alpine.data('phaseController', phaseController);
    Alpine.data('hotspotViewer',   hotspotViewer);
    Alpine.data('slideViewer',     slideViewer);
    Alpine.data('quizEngine',      quizEngine);
});
