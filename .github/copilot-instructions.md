# Code Snippets — Copilot Instructions

> **Instruction authority:** This file is the canonical source for all AI agent behaviour in this repository.
> All custom instructions live here and in `.github/instructions/`. Other agents (Claude, Gemini, Cursor, Aider, etc.)
> must read, parse, and follow this file. Do not duplicate or override these instructions in agent-specific files —
> instead, point those agents at this file. See `AGENTS.md` for an explicit directive to non-Copilot agents.

---

## Project Overview

**Code Snippets** is a WordPress plugin (GPL-2.0-or-later) that lets site owners manage and execute PHP, HTML, CSS,
and JavaScript code snippets through a graphical admin interface — replacing the need to edit `functions.php` or
maintain multiple single-purpose plugins.

- **Repo:** `codesnippetspro/code-snippets` — this repository.
- **Homepage / docs:** https://codesnippets.pro
- **Current version:** 3.10.x (see `package.json` and `src/code-snippets.php`)
- **Minimum requirements:** PHP 7.4+, WordPress 5.5+

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 7.4+, WordPress APIs, PSR-4 via Composer |
| Frontend | TypeScript, React 18, `@wordpress/components`, CodeMirror 5 |
| Styling | SCSS (PostCSS, logical properties via `stylelint-use-logical`) |
| Build | Webpack 5, Babel, `ts-loader`, `sass-loader` |
| PHP linting | PHPCS + WPCS (`npm run lint:php`) |
| JS/TS linting | ESLint 9 (`npm run lint:js`) |
| CSS linting | Stylelint (`npm run lint:styles`) |
| PHP tests | PHPUnit (`npm run test:php`) |
| E2E tests | Playwright (`npm run test:playwright`) |
| WP dev env | `@wordpress/env` / `wp-env` (`npm run wp-env:start`) |
| Pre-commit | Husky + lint-staged (auto-fix on commit) |
| PHP deps | Composer with Imposter (namespace-prefixing) |

---

## Coding Standards

### General
- Follow the **WordPress Coding Standards** for PHP, JS, CSS, and HTML.
- Use `strict_types=1` is not enforced project-wide — match the style of the file being edited.
- Keep lines under 120 characters for PHP; 100 for JS/TS.
- All user-visible strings must be wrapped in a WordPress i18n function with the text domain `code-snippets`.
- Use `__()`, `_e()`, `esc_html__()`, `_x()`, `_n()` as appropriate — never raw echo for translatable strings.
- Do not load translations before `init` or `plugins_loaded`.

### PHP
- Namespace: `Code_Snippets\` — all new classes must live under this namespace and be PSR-4 autoloaded.
- Vendor dependencies: always use the prefixed namespace `Code_Snippets\Vendor\…` (Imposter-prefixed).
- Guard direct execution at the top of every standalone file: `defined('ABSPATH') || exit;`
- Use `wp_die()` for fatal admin errors; never `die()` or `exit` with user-facing output.
- In `src/php/Plugin.php` (and Core bootstrap), rely on `autoload.php`; avoid manual `require_once` chains.
- Avoid creating custom database tables. Prefer WordPress-native storage: `wp_options` for settings/flags, transients for cached/temporary data, or hidden custom post types for structured content. Custom tables require manual schema management, migration, and uninstall logic — only justify them when native storage genuinely cannot meet the requirement.

### TypeScript / React
- Export types that appear in exported function signatures — do not leak unexported shapes.
- Use `@wordpress/api-fetch` or the service layer in `src/js/services/` for all WP REST calls.
- Prefer `@wordpress/components` for UI; avoid reimplementing existing WP admin patterns.

### SCSS
- Use logical CSS properties (e.g., `margin-inline-start` not `margin-left`) — enforced by stylelint.

---

## Architecture Patterns

- **PSR-4 autoloading via Composer** — class files are discovered automatically; do not `require` them manually.
- **Imposter prefixing** — all `vendor/` code is rewritten to `Code_Snippets\Vendor\…` to prevent conflicts with other plugins using the same libraries.
- **Snippet model** — core data unit is `Code_Snippets\Model\Snippet`; use its API for reading/writing snippet data, not raw DB access.
- **Hook-driven extensibility** — use WordPress filters and actions as the primary extension mechanism; expose a filter before changing any default behaviour that may be preference-driven.
- **Safe mode** — `src/php/Core/load.php` boots a recovery path when safe mode is active; any change to snippet execution must preserve this path.

---

## Branching & Git Workflow

- **Production branch:** `core`
- **Pre-release branch:** `core-beta`
- **Development branches:** `feat/…`, `fix/…`, `chore/…`, `hotfix/…`
- Branch from `core-beta` for all feature and fix work.
- Open PRs back into `core-beta`.
- Use **merge commits** (not squash/rebase) when merging dev branches into `core-beta`.
- Hotfixes branch from `core` and merge directly back into `core`.

---

## Development Commands

```bash
# Install dependencies
npm install                  # Node deps + Husky hooks
cd src && composer install   # PHP deps (or: npm run bundle)

# WordPress environment
npm run wp-env:start         # Start local WP instance
npm run wp-env:stop
npm run wp-env:clean         # Reset all data

# Build
npm run build                # Webpack build (JS + CSS → src/dist/)
npm run watch                # Webpack watch mode
npm run bundle               # Full distribution build → bundle/

# Lint
npm run lint                 # All: PHP + JS + CSS
npm run lint:php             # PHPCS with WPCS
npm run lint:js              # ESLint
npm run lint:styles          # Stylelint

# Tests
npm run test:php             # PHPUnit
npm run test:playwright      # Playwright E2E (requires wp-env running)
./test-playwright.sh         # Helper script for Playwright
```

---

## MCP Tools — Context7 and Chrome DevTools

When working on tasks that involve library documentation, API references, or browser debugging, use the available
MCP tools for better, up-to-date results.

### Context7 (`mcp_context7_*`)

Use Context7 to look up accurate, version-specific documentation for any library used in this project
(WordPress, React, TypeScript, CodeMirror, Playwright, etc.) rather than relying on training-data knowledge.

**When to use:**
- Looking up WordPress hook signatures, REST API schemas, or WP component props.
- Confirming TypeScript / React API details.
- Checking Playwright test API or assertion methods.
- Any time you are about to write code that depends on a third-party API you are not 100% certain about.

**How to use:**
1. Call `mcp_context7_resolve-library-id` with the library name to get its Context7 ID.
2. Call `mcp_context7_query-docs` with the resolved ID and a specific question.

**If Context7 is not installed:**
Ask the user: *"The Context7 MCP server is not available. Would you like to set it up? It gives me access to
up-to-date library docs. Install via: `npx -y @upstash/context7-mcp@latest` and add it to your MCP config."*

### Chrome DevTools (`mcp_chrome-devtoo_*`)

Use Chrome DevTools MCP for any task involving the browser UI: inspecting rendered admin pages, debugging
JavaScript errors, validating accessibility (contrast, ARIA), checking network requests, or running
performance traces.

**When to use:**
- Debugging a UI regression or layout issue in the WP admin.
- Verifying that a REST API call returns the expected payload.
- Checking console errors after a JS change.
- Validating accessibility of admin UI changes (colour contrast, keyboard focus, ARIA).
- Running a Lighthouse / performance trace on a frontend page.

**How to use:** Use the `mcp_chrome-devtoo_*` family of tools — take a snapshot, navigate a page, inspect
network requests, evaluate scripts, or start a performance trace.

**If Chrome DevTools MCP is not installed:**
Ask the user: *"The Chrome DevTools MCP server is not available. Would you like to set it up? It lets me
inspect the live browser state. Install via the VS Code MCP extension or add `@modelcontextprotocol/server-chrome`
to your MCP config."*

---

## Security Checklist (apply to every change)

- Sanitize all user inputs server-side before use.
- Escape all outputs at the correct boundary (HTML, attribute, JS, URL).
- Verify a WordPress nonce on every state-changing request.
- Verify `current_user_can()` alongside nonce checks.
- Never use `eval`, `create_function`, or `call_user_func` with untrusted input.
- Apply `rel="noopener noreferrer"` to every `target="_blank"` link.
- Use `$wpdb->prepare()` for every dynamic SQL value.
- Any feature fetching remote content must document its trust model.

---

## Testing Requirements

- **Unit tests** (PHPUnit) — required for all PHP logic changes.
- **Integration tests** (PHPUnit) — required when changing DB schema, REST endpoints, or hook behaviour.
- **E2E tests** (Playwright) — required for changes affecting snippet create/edit/execute flows.
- Tests must be compatible with the minimum supported PHP 7.4 and WordPress 5.5.
- Generated build artifacts (`src/dist/`, Composer autoload maps) must be regenerated when source changes.

---

## Path-Specific Instructions

More targeted rules live in `.github/instructions/` as `*.instructions.md` files:

| File | Scope |
|---|---|
| `code-review.instructions.md` | Copilot Code Review (CCR) — review standards (excludes coding agent) |

Add new `*.instructions.md` files here for language- or area-specific rules (e.g., `php.instructions.md`,
`react.instructions.md`). Always define rules in Copilot instruction files first; other agent
implementations must reference and follow the same rules.

---

## Do Not

- Do not create custom database tables without strong justification — use `wp_options`, transients, or custom post types instead.
- Do not echo raw user input — always escape at output.
- Do not concatenate translated string fragments — translate full sentences.
- Do not place HTML markup inside translated strings.
- Do not rely on "deactivate the plugin" as a recovery path — safe mode must remain functional.
- Do not ship build artifacts to feature branches — `src/dist/` and `bundle/` are built in CI.
