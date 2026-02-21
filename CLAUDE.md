# Orthodox Catechesis — Agent Instructions

## Security is paramount

This project serves children and is deployed on a public server. **Security must never be
compromised for convenience.** When in doubt, choose the stricter option. If a change would
weaken security, do not make it without explicit written approval from the repository owner.

---

## Project overview

A PHP web application for orthodox catechesis activities (learn + quiz). It runs on shared
hosting (Infomaniak, Apache + PHP). No build step, no npm, no bundler — everything is plain
PHP, vanilla JS, and static assets.

Key entry points:
- `index.php` — language switching POST handler
- `main.php` — activity listing page
- `activity.php` — learn + quiz page for a single activity
- `includes/bootstrap.php` — loaded by every entry point; sets up session, headers, helpers
- `templates/` — PHP partials included by entry points
- `assets/` — local CSS, JS, images (no CDN)

---

## Security requirements

### Content Security Policy
The CSP is defined in `.htaccess` and must remain as strict as possible:
- `script-src 'self'` only — no CDN domains, no `'unsafe-inline'`, no `'unsafe-eval'`
- `style-src 'self' 'unsafe-inline'` — Bootstrap requires inline styles; keep it limited
- `connect-src 'self'` — no external API calls from the browser
- Never add `'unsafe-eval'` — it defeats much of the purpose of a CSP

### Alpine.js CSP build
The project uses `@alpinejs/csp` (not standard Alpine.js). This version does not use
`eval()`, `new Function()`, or `new AsyncFunction()`. The following rules are mandatory:

1. **No inline `x-data` objects** — never write `x-data="{ key: value }"` in HTML.
   Register every component with `Alpine.data('name', factory)` in `assets/js/app.js` and
   reference it by name: `x-data="name"`.
2. **No function-call syntax in `x-data`** — never write `x-data="component()"`.
   Use `x-data="component"` (no parentheses).
3. **No assignment expressions in event handlers** — never write `@click="foo = 'bar'"`.
   Add a named method to the component instead: `@click="setFoo"`.
4. **No `$dispatch()` inline** — never write `@click="$dispatch('event')"`.
   Add a named method that calls `this.$dispatch('event')`.
5. All components must be registered inside the `alpine:init` event listener.

### No CDN dependencies
All JavaScript and CSS libraries must be hosted locally under `assets/`. Never add `<script>`
or `<link>` tags that load from external domains. If a new library is needed:
1. Download the production minified file(s) to `assets/js/` or `assets/css/`
2. Update the relevant template (`templates/layout.php`, `templates/layout_end.php`)
3. Verify the CSP still holds — no new domains needed

### CSRF protection
Every form and every AJAX POST must include a CSRF token. Use `$security->getTokenField()`
in forms and `data-csrf-token="<?= h($csrfToken) ?>"` for AJAX. The `Security` class rotates
the token after each validation. Do not bypass or disable CSRF checks.

### Output escaping
Every value rendered into HTML must pass through `h()` (`htmlspecialchars` wrapper). Never
echo raw user input or raw data from JSON files. Use `json_encode()` with `JSON_HEX_TAG |
JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` when embedding JSON in HTML.

### Input validation
Validate and sanitise all input at the boundary (`index.php`, `activity.php`). Use allow-lists
for expected values (slugs, language codes, action names). Reject or ignore unexpected input.

### File access
The `.htaccess` blocks direct HTTP access to `config/`, `lang/`, `activities/`, `includes/`,
all dot-files, and all `.json` files. Do not relax these rules. If a new sensitive directory
is added, add a matching `RewriteRule` block.

### Session security
Session parameters are hardened in `includes/bootstrap.php` (`HttpOnly`, `SameSite=Strict`,
`use_strict_mode`, etc.). Do not change these settings. Session IDs are regenerated on first
use and every 30 minutes.

### Rate limiting
Sensitive actions (e.g. `check_answer`) are rate-limited via `Security::checkRateLimit()`.
If new user-facing POST actions are added, apply rate limiting.

---

## Code conventions

- PHP 8.x with `declare(strict_types=1)` at the top of every file
- `h()` for HTML escaping, `t()` for translations
- `BASEPATH` guard at the top of every partial/include: `if (!defined('BASEPATH')) { exit; }`
- Templates receive data via PHP variables set before inclusion; no globals beyond `$security`
  and `$translator`
- Translations live in `lang/fr.json` and `lang/ro.json`; keys follow `section.key` convention
- Activity data lives in `activities/<slug>/` as `meta.json`, `learn.json`, `quiz.json`;
  correct answers must never be exposed in the client-facing quiz payload

---

## What NOT to do

- Do not add `'unsafe-eval'` or `'unsafe-inline'` (scripts) to the CSP
- Do not load any resource from an external domain
- Do not use the standard Alpine.js build — use `@alpinejs/csp` only
- Do not write inline `x-data` objects or assignment event handlers (see Alpine rules above)
- Do not expose correct answers in the JSON sent to the browser
- Do not commit `config/config.php` (it is gitignored; use `config/config.example.php` as
  the template)
- Do not disable the `.htaccess` access rules for `activities/`, `lang/`, or `config/`
- Do not use `eval()`, `exec()`, `system()`, `passthru()`, or `shell_exec()` in PHP
- Do not use `innerHTML` or `document.write()` in JavaScript
