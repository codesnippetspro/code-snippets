# Code Snippets

This file is the single source of truth for project standards, coding conventions, and development workflows in this
repository. All contributors — human or automated — should follow these rules.

Tool-specific configuration lives in dedicated locations:

- **GitHub Copilot:** `.github/copilot-instructions.md` and `.github/instructions/*.instructions.md`
- **Cursor:** `.cursor/rules/*.mdc`
- **Claude:** `claude.md`

## Project Overview

**Code Snippets** is a WordPress plugin that lets site owners manage and execute PHP, HTML, CSS, and JavaScript code
snippets through a graphical interface — replacing the need to edit `functions.php` or maintain multiple single-purpose
plugins.

## Repository Structure

```
<root>/
├── src/                    # Shipped plugin root
│   ├── code-snippets.php   # Plugin bootstrap
│   ├── php/                # PHP application code (PSR-4: Code_Snippets\)
│   │   ├── Plugin.php      # Main orchestrator
│   │   ├── Admin/          # Admin UI, menus
│   │   ├── Client/         # Cloud API clients
│   │   ├── Controller/     # Cloud/search controllers
│   │   ├── Core/           # Bootstrap, safe mode
│   │   ├── REST_API/       # REST endpoint controllers
│   │   ├── Model/          # Snippet and cloud data models
│   │   ├── Flat_Files/     # File-based snippet storage
│   │   ├── Integration/    # Third-party integrations
│   │   ├── Migration/      # Data migration logic
│   │   ├── Settings/       # Plugin settings
│   │   ├── Utils/          # Shared utilities
│   │   ├── snippet-ops.php # Snippet operations API
│   ├── js/                 # TypeScript / React source
│   │   ├── entries/        # Webpack entrypoints
│   │   ├── components/     # Feature-grouped React UI
│   │   ├── hooks/          # Custom React hooks
│   │   ├── services/       # API service layer
│   │   ├── types/          # TypeScript type definitions
│   │   └── utils/          # JS utilities
│   ├── css/                # SCSS source files
│   ├── dist/               # Webpack output (built assets, not committed)
│   ├── vendor/             # Composer dependencies
│   └── composer.json       # PHP dependency management
├── .github/                # CI workflows, issue templates, Copilot instructions
├── assets/                 # WordPress.org screenshots, icons, and banners
├── config/                 # Webpack and PostCSS configuration
├── scripts/                # Release, versioning, and linter scripts
└── tests/                  # PHPUnit suites, Playwright E2E specs
```

## Tech Stack

| Layer         | Technology                                                     |
|---------------|----------------------------------------------------------------|
| Backend       | PHP 7.4+, WordPress APIs, PSR-4 via Composer                   |
| Frontend      | TypeScript, React 18, `@wordpress/components`, CodeMirror 5    |
| Styling       | SCSS (PostCSS, logical properties via `stylelint-use-logical`) |
| Build         | Webpack 5, Babel, `ts-loader`, `sass-loader`                   |
| PHP linting   | PHPCS + WPCS (`npm run lint:php`)                              |
| JS/TS linting | ESLint 9 flat config (`npm run lint:js`)                       |
| CSS linting   | Stylelint (`npm run lint:styles`)                              |
| PHP tests     | PHPUnit (`npm run test:php`)                                   |
| E2E tests     | Playwright (`npm run test:playwright`)                         |
| WP dev env    | `@wordpress/env` / `wp-env` (`npx wp-env start`)               |
| Pre-commit    | Husky + lint-staged (auto-fix on commit)                       |
| PHP deps      | Composer with Imposter (namespace-prefixing)                   |

## Coding Standards

### General

- Follow the **WordPress Coding Standards** for PHP, JS, CSS, and HTML.
- `strict_types=1` is not enforced project-wide — match the style of the file being edited.
- Keep lines readable; break long imports or expressions when it helps. Hard limits are the linters' job, not a fixed number here.
- All user-visible strings must be wrapped in a WordPress i18n function with the text domain `code-snippets`.
- Use `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `_x()`, `_n()` as appropriate — never echo raw translatable
  strings.
- Use the actual ellipsis character (`…`) instead of three periods (`...`) in user-facing strings.

### Code Comments

- A comment must state a current constraint or behaviour the code itself cannot show — written for the next reader, not
  the reviewer.
- Do not write comments that narrate what the next line does, justify why a change is correct, or argue with the
  previous implementation ("not a no-op", "this used to…", "fixes the bug where…"). History belongs in the commit
  message.
- Match the comment density and tone of the surrounding file; when in doubt, omit the comment.

### PHP

- Namespace: `Code_Snippets\` — all new classes must live under this namespace and be PSR-4 autoloaded.
- Vendor dependencies: always use the prefixed namespace `Code_Snippets\Vendor\…` (Imposter-prefixed).
- Guard direct execution at the top of executable entry points and procedural files with
  `defined('ABSPATH') || exit;`; do not add guards to autoloaded class files.
- Use `wp_die()` for fatal admin errors; never `die()` or `exit` with user-facing output.
- In `src/php/Plugin.php` (and Core bootstrap), rely on `autoload.php`; avoid manual `require_once` chains.
- Avoid creating custom database tables. Prefer WordPress-native storage: `wp_options` for settings/flags, transients
  for cached/temporary data, or hidden custom post types for structured content. Custom tables require manual schema
  management, migration, and uninstall logic — only justify them when native storage genuinely cannot meet the
  requirement.
- Do not create new instances of `_Controller` classes outside of `Plugin` – reuse the existing instances stored as
  class properties of `Plugin`.

### TypeScript / React

- **Exports**: Export types that appear in exported function signatures — do not leak unexported shapes.
- **API calls**: Use the `useRestAPI` context for all WP REST calls.
- **UI components**: Prefer `@wordpress/components` for UI; avoid reimplementing existing WP admin patterns.
- **Parameter objects**: For functions with 3+ parameters, use a typed parameter object instead of positional
  parameters.
- **Imports**: Keep imports alphabetically sorted within their groups (third-party, local, relative).
- **Declarative patterns**: Write React in a declarative style—prefer state + conditional rendering over imperative DOM
  manipulation.
    - Avoid `useEffect` chains for cascading state updates; use `useMemo` to derive state instead.
    - Prefer direct state updates in event handlers over using `useEffect` to synchronize multiple state values.
    - Use controlled components for forms; keep input values in React state.
    - Derive computed values at render-time rather than duplicating them in separate state.
- **Hooks**: Hooks return state and actions, not rendered elements.
- **Clarity**: Avoid nested ternaries, trivial memoization, unnecessary ARIA roles, and props that repeat component
  defaults.
- **Conditional rendering**: Use `condition && <element>`; reserve `condition ? <element> : null` for numeric or
  string conditions that React would otherwise render (a stray `0` or `''`).
- **Utilities**: Extend the closest existing utility module rather than creating a new file for a single function.
- **Callback types**: Type no-argument callbacks as `VoidFunction`, not `() => void`.
- **Translator comments**: Place `// translators:` comments on the line immediately before the translatable string,
  not before the enclosing JSX expression.
- **Headings**: Admin screens render a single `h1`; section headings within a page start at `h2`.
- **Component composition**: Separate components when doing so clarifies responsibility or enables reuse. Use JSX for
  markup rather than `createElement` in utility functions.
- Do not create 'index.ts' barrel files for components or hooks; import them directly to avoid circular dependencies and
  improve tree-shaking.
- Reuse existing functions and components where possible, creating new common components under
  `src/js/components/common` if pragmatic to do so, updating the original usages.

### SCSS

- Use logical CSS properties (e.g., `margin-inline-start` not `margin-left`) — enforced by stylelint.

### Code Organization

- Treat file length as a signal, not a reason to split cohesive code. Extract code only for reuse, meaningful separation,
  or clearer testing; avoid single-use and pass-through abstractions.
- Maintain a direct mapping between source classes and their test files; split source classes and tests only when doing
  so creates a meaningful separation or clearer testing boundary.

## Architecture Patterns

- **PSR-4 autoloading via Composer** — class files are discovered automatically; do not `require` them manually.
- **Imposter prefixing** — all `vendor/` code is rewritten to `Code_Snippets\Vendor\…` to prevent conflicts with other
  plugins using the same libraries.
- **Snippet model** — core data unit is `Code_Snippets\Model\Snippet`; use its API for reading/writing snippet data, not
  raw DB access.
- **Data boundaries** — models preserve data, remote response decoding normalizes remote data, and rendering handles
  presentation sanitation and escaping.
- **Hook-driven extensibility** — use WordPress filters and actions as the primary extension mechanism; expose a filter
  before changing any default behaviour that may be preference-driven.
- **Safe mode** — `src/php/Core/load.php` boots a recovery path when safe mode is active; any change to snippet
  execution must preserve this path.

## Branching & Git Workflow

- **Production branch:** `core`
- **Pre-release branch:** `core-beta`
- **Development branches:** `feat/…`, `fix/…`, `chore/…`, `hotfix/…`
- Name every new branch with an edition suffix as its final path segment: `/core` in the Core repository and `/pro`
  in the Pro repository (for example, `chore/align-agent-rules/core` and `chore/align-agent-rules/pro`). Repository
  rules reject branch creation without the suffix. Release automation follows the same pattern
  (`release/v…/core`, `sync/core-v…/pro`).
- Branch from `core-beta` for all feature and fix work.
- Open PRs back into `core-beta`.
- Preserve granular history during development and use merge commits for active stack propagation. Squash-merging a
  large, fully reviewed chain into `core-beta` is preferred after explicit approval and a passing chain-peak matrix.
- Hotfixes branch from `core` and merge directly back into `core`.
- Intermediate stacked PRs may fail when the failure is understood and fixed later in the same chain. The current chain
  peak must pass the complete required matrix before release integration.

### Commit Messages

- Single-line conventional commit messages only — no body, no trailers.
- The subject must start with exactly one of: `fix:`, `feat:`, `chore:`, `docs:`.
- No scope in parentheses — write `fix: restore cloud search`, never `fix(cloud): …`.
- No co-author lines or any other trailers (`Co-Authored-By:`, `Signed-off-by:`, etc.).
- Scope each commit to one feature, one change, or one file — do not bundle unrelated changes.

### Pull Request Descriptions

- PR descriptions must follow these standards as strictly as code and commit messages do.
- Keep the language professional and factual — state what changed and how it was verified.
- Do not include narrative reasoning, architectural rationale, or a record of the deliberation behind the
  change; that belongs in the issue and commit history, not the PR body.
- On the public repository, do not disclose premium (Pro) decisions, features, or roadmap, and avoid
  unnecessary references to private repositories, issues, or cross-edition work.

## Development Commands

```bash
# Install dependencies
npm install                  # Node deps
composer -d src install      # PHP deps (or: npm run bundle)

# WordPress environment
npx wp-env start             # Start local WP instance
npx wp-env stop
npx wp-env clean all         # Reset all data

# Build
npm run build                # Webpack build (JS + CSS → src/dist/)
npm run watch                # Webpack watch mode (not recommended in AI workflows)
npm run bundle               # Full distribution build → bundle/

# Lint
npm run lint                 # All: PHP + JS + CSS
npm run lint:php             # PHPCS with WPCS
npm run lint:js              # ESLint
npm run lint:styles          # Stylelint
npm run lint:php:fix         # Auto-fix PHP linting issues
npm run lint:js:fix          # Auto-fix JS/TS linting issues
npm run lint:styles:fix      # Auto-fix CSS/SCSS linting issues

# Tests
npm run test:setup:php       # Download WordPress test suite and configure PHPUnit
npm run test:php             # PHPUnit
npm run test:php:watch       # PHPUnit with detailed output
npm run test:setup:playwright # Prepare environment for Playwright E2E
npm run test:playwright      # Playwright E2E (requires wp-env running)
npm run test:playwright:ui   # Playwright with UI runner
npm run test:playwright:debug # Playwright in debug mode
./test-playwright.sh         # Helper script for Playwright

# Versioning (for maintainers)
npm run version-dev          # Pre-release dev version
npm run version-alpha        # Pre-release alpha version
npm run version-beta         # Pre-release beta version
npm run version-rc           # Pre-release release candidate
npm run version              # Final release
```

## Security Checklist

Apply to every change:

- Sanitize all user inputs server-side before use.
- Escape all outputs at the correct boundary (HTML, attribute, JS, URL).
- Verify a WordPress nonce on every state-changing request.
- Verify `current_user_can()` alongside nonce checks.
- Never use `eval`, `create_function`, or `call_user_func` with untrusted input.
- Apply `rel="noopener noreferrer"` to every `target="_blank"` link.
- Use `$wpdb->prepare()` for every dynamic SQL value.
- Any feature fetching remote content must document its trust model.

## Testing Requirements

- **Unit tests** (PHPUnit) — required for all PHP logic changes.
- **Integration tests** (PHPUnit) — required when changing DB schema, REST endpoints, or hook behaviour.
- **E2E tests** (Playwright) — required for changes affecting snippet create/edit/execute flows.
- **Minimum requirements** - Tests must be compatible with the minimum supported PHP 7.4 and WordPress 5.5.
- **Build** - Generated build artifacts (`src/dist/`, Composer autoload maps) must be regenerated when source changes.

## Do Not

- Do not create custom database tables without strong justification — use `wp_options`, transients, or custom post types
  instead.
- Do not echo raw user input — always escape at output.
- Do not concatenate translated string fragments — translate full sentences.
- Do not place HTML markup inside translated strings.
- Do not rely on "deactivate the plugin" as a recovery path — safe mode must remain functional.
- Do not ship build artifacts to feature branches — `src/dist/` and `bundle/` are built in CI.
