---
applyTo: "**/*"
excludeAgent: "coding-agent"
description: "Repository code-review standards for Copilot Code Review (CCR). Follow the severity model, security checks, WP conventions, i18n, accessibility, tests, and suggested remediation steps."
---

# Code Review Standards for Code Snippets

## Purpose

These instructions guide Copilot code review across all files in the Code Snippets WordPress plugin repository.
Use these rules when reviewing pull requests to produce specific, actionable feedback.

Severity labels used in this file:
- **MUST** — Flag as a blocking issue; must be resolved before merge.
- **SHOULD** — Flag as a recommendation; resolve before merge unless a risk-aware rationale is provided.

## Practical Reviewer Checks

- Don't silently widen types or drop generics when refactoring. If typing gets weaker, require a clear reason. [MUST]
- In namespaced PHP, call WordPress globals with `\function_name()` and do not assume pluggable/core functions exist on very early execution paths. If a callback can run during early bootstrap, guard availability appropriately. [MUST]
- Ensure hook callbacks that respond to options/actions are tightly gated to the intended option/action. Be suspicious of inverted or overly broad conditionals that can be triggered by other plugins. [MUST]
- Avoid no-op abstractions (pass-through helpers, one-liner wrappers) unless they materially improve readability, reuse, or testability. [SHOULD]
- Inline single-use extractions that do not clarify intent (local functions/variables used once). [SHOULD]
- Prefer `undefined` for absent optional values in TypeScript/React unless `null` has explicit semantics in that API. [SHOULD]
- For conditional class names, prefer the repository `classnames.classnames` helper over manual array filtering and joining. [SHOULD]
- Prefer JSX for React markup. If a hook/util needs to render elements, suggest moving the markup into a `.tsx` component instead of using `createElement` in a `.ts` file. [SHOULD]
- For simple key-to-value parsing/transforms, prefer a literal object/record map over a loop + `switch` when it improves clarity. [SHOULD]
- Do not stack redundant `catch` blocks (e.g., `ParseError` plus `Throwable`) unless the handlers differ. [SHOULD]
- When a screen is React-driven, question heavy PHP view logic. If PHP is used due to WordPress admin primitives (e.g., Screen Options, non-REST file streaming), require a short rationale. [SHOULD]

## Scope and Diff Hygiene

- Flag PRs that mix behavior changes with refactors, renames, or formatting-only edits. Each change should be single-purpose. [MUST]
- Flag renamed identifiers, files, UI labels, or data keys that lack a concrete justification in the commit message or comments. [MUST]
- Look for indentation or formatting regressions in PHP, JS/TS, CSS, or Markdown. [MUST]
- Identify redundant changes that do not alter behavior (e.g., pointless `printf`/wrapper edits). [MUST]
- Check whether large changes could be split into smaller reviewable units (UI refactor vs logic vs data model). [SHOULD]
- Flag "drive-by" cleanup outside the area being changed. [SHOULD]

## Correctness, Resilience, and Types

- Verify all external/variable inputs (request parameters, shortcode content, option values, API responses) are validated before use. [MUST]
- Check that code does not assume array structure; verify the expected key/shape exists before parsing or indexing. [MUST]
- Flag type inconsistencies (e.g., storing an "int-like" state as a `string` when it is treated as an `int`). [MUST]
- Identify redundant checks and tautologies (e.g., checking a condition already guaranteed by casting or a documented union type). [MUST]
- Look for inverted `if` chains that reduce readability; prefer early returns and simpler control flow. [SHOULD]
- Check that conditionals use braces; flag unbraced single-line `if` statements. Prefer `switch` for multi-branch dispatch logic. [SHOULD]
- Flag `if/else` chains that could be simplified as "default then override" to reduce nesting (e.g., initialize `$primary_button` then adjust fields). [SHOULD]
- Flag mutation of list arrays via numeric offsets like `$buttons[0]`; prefer named local variables or associative keys. [SHOULD]
- Identify repeated passes over the same data that could be consolidated (e.g., multiple `array_filter` iterations). [SHOULD]

## WordPress Conventions and Internal APIs

- Verify user-facing behavior and labels follow WordPress conventions and terminology (e.g., "Trash" and "Undo" patterns for reversible deletion). [MUST]
- Flag hand-built admin page URLs; use platform/internal URL builders and constants instead. [MUST]
- Flag use of fragile constants for environment/variant checks; use established internal APIs when a canonical method exists. [MUST]
- Check for vendored assets that duplicate WordPress core functionality (e.g., dashicons, CodeMirror/linting scripts). Prefer core assets. [SHOULD]
- Verify changes do not silently degrade when other plugins load conflicting assets. [SHOULD]

## Extensibility: Hooks, Filters, and Configuration

- Flag changes to default behavior that lack a non-UI escape hatch (filter/hook) or explicit configuration surface, when the behavior may be preference-driven. [MUST]
- Flag hooks/actions added without a clear, long-term extension need. [MUST]
- Check whether filters are preferred over new UI options for niche workflows (unless discoverability is critical or support burden demands UI). [SHOULD]
- Flag "too-early" checks outside the runtime context they depend on; prefer evaluating conditions inside the actual hook callback. [SHOULD]

## Security and Trust Boundaries

- Flag dynamic execution patterns (e.g., `eval`, `create_function`) that increase security risk or trigger security-scanner false positives. Require a documented trust model if used. [MUST]
- Flag remote code or remotely sourced content executed without an explicit, robust trust model. [MUST]
- Verify escaping/sanitization is applied at the correct output boundary (attribute context, HTML context, JS context). Flag "random escaping" that breaks dependent scripts or UI. [MUST]
- Check that shared UI renderers (base classes/helpers/templates) escape/sanitize their own inputs rather than relying on callers returning pre-escaped strings. [MUST]
- Check every `target="_blank"` link for `rel="noopener noreferrer"`. Flag any missing instance unless a documented exception exists. [MUST]
- Verify all state-changing requests (AJAX endpoints, form submissions, REST/action handlers) require and verify a WordPress nonce server-side. [MUST]
- Verify capability checks are present alongside nonce checks for state-changing endpoints. [MUST]
- Check that all external and remote inputs are validated server-side; flag reliance on client-side validation alone. [MUST]
- Flag features that fetch or render remote content without a documented threat assessment (attack surface, trust model, mitigations). [MUST]
- Check whether output helpers could be refactored to own correct escaping, rather than scattering escaping at call sites. [SHOULD]
- Flag large opaque blobs (e.g., encoded payloads) shipped into contexts where security tools may flag them. [SHOULD]

## Internationalization (i18n)

- Verify correct translation functions are used for each string type and context (e.g., context-aware functions when the string is ambiguous or partial). [MUST]
- Flag HTML markup placed inside translated strings unless there is a strong, documented reason. [MUST]
- Flag access to translated labels before translation files are loaded. [MUST]
- Check for concatenation of translated fragments; prefer translating full sentences/phrases. [SHOULD]

## UX and Accessibility

- Flag removal of accessibility-relevant context that lacks an equivalent or better replacement affordance. [MUST]
- When `title` attributes are replaced with ARIA attributes, verify the change does not regress non-assistive UX (e.g., hover help) or reduce meaning for screen readers. [MUST]
- Verify user settings that disable or hide UI elements are respected (e.g., do not render upsell/promotions when the "hide" setting is enabled). [MUST]
- Check that dismissible notices actually persist dismissal for the users who can see them (capability checks, AJAX handlers, and nonces must align). [MUST]
- Check that the change follows WordPress admin interaction patterns (undo/trash flows, notices behavior, iconography). [SHOULD]
- Verify built-in icon sets and established admin styling conventions are used where applicable. [SHOULD]
- For UI changes: verify all interactive elements are keyboard-reachable and operable. [MUST]
- For UI changes: check semantic markup and ARIA usage for screen reader support. [MUST]
- For UI changes: verify text and UI elements meet WCAG AA color contrast ratios. [MUST]
- For UI changes: check for visible and logical focus order on interactive elements. [MUST]

## Architecture and Code Organization

- Flag new classes, namespaces, or files that lack a clear need, or redundant wrapper classes. [MUST]
- Flag complex commands or subsystems stuffed into unrelated classes; keep concerns separated. [MUST]
- Flag manual loading of class files that are already covered by Composer autoloading. [MUST]
- In `src/php/class-plugin.php`, verify Composer autoloading is used; flag `require_once` unless the file cannot be loaded via Composer. [MUST]
- Flag direct-access guards or runtime checks in individual class files; keep bootstrapping and gating at entry points. [SHOULD]
- Check that class names, file names, and directory naming are consistent. [SHOULD]
- Check whether complex logic could be extracted into small, named methods with single responsibilities. [SHOULD]
- Flag hook registration split into extra methods without clear value; prefer co-locating registration with construction/bootstrap. [SHOULD]

## Dependencies, Assets, and Tooling

- Flag dependencies that are not needed for the actual implementation. [MUST]
- Flag bundled assets that duplicate platform-provided equivalents. If bundling is unavoidable, note the maintenance obligation (updates, conflicts, compatibility). [MUST]
- Verify the repository's linting/formatting conventions are followed (e.g., logical CSS properties, consistent formatting). [SHOULD]
- Check for manual workarounds that could be replaced by framework-native patterns in React/TS. [SHOULD]

## Persistence, Cleanup, and Uninstall

- Verify any new persistent option/setting is accounted for in uninstall/cleanup paths. [MUST]
- Flag storage of empty/default data that could be safely removed to reduce configuration drift. [MUST]
- Verify installation/uninstallation behavior is coherent and repeatable (fresh installs vs reinstalls vs data-preserving removals). [MUST]
- Check whether WordPress API conveniences (e.g., `get_option` default values) could reduce conditional noise and edge cases. [SHOULD]
- For new DB schema changes or options: verify migration and rollback paths exist. [MUST]
- For new persistent options: verify an uninstall path removes them. [MUST]

## JavaScript/React State and Async Behavior

- Verify types referenced by exported functions and public APIs are also exported. Flag unexported internal shapes leaked through public APIs. [MUST]
- Check that the correct state primitive (`useState` vs `useRef`) is used based on rendering and lifecycle needs. Flag refs used as a state substitute. [MUST]
- Look for concurrent async calls and race conditions; flag state updates that can interleave unpredictably. [MUST]
- Check whether patterns degrade gracefully on partial failure (e.g., one failed async request should not fail the entire operation when partial results are acceptable). [SHOULD]
- Flag repeated state updates in loops; prefer a single update at the end. [SHOULD]

## Tests, Compatibility, and Release Hygiene

- Verify changes are compatible with the minimum supported WordPress/PHP versions (especially type-related behavior). [MUST]
- Flag automation/scripts that rely on brittle timing/waiting; they must be deterministic. [MUST]
- Verify changelog/readme formatting remains valid (Markdown and `readme.txt` heading/list spacing must not break rendering). [MUST]
- When the repo ships generated artifacts (Composer autoload/classmaps, built `dist` assets), verify they are regenerated and include newly added files/classes. [MUST]
- Check whether tests cover the minimum supported platform versions, not just "latest". [SHOULD]
- Flag unverified bug fixes; require additional diagnostics (logs, error output, minimal reproduction) rather than guessing. [SHOULD]
- Verify unit tests are included for logic changes, covering edge cases and error paths. [MUST]
- Verify integration tests are included when cross-cutting concerns (DB, REST, hooks) or subsystem interactions change. [MUST]
- Verify E2E tests are included for changes affecting critical user flows (snippet creation, execution, editor workflows); check for updated Playwright specs. [MUST]
- Check for tests asserting compatibility with minimum-supported WordPress/PHP versions when behavior differs by version. [SHOULD]

## Operational Safety and Recovery

- For changes affecting snippet execution: verify recovery paths (safe mode flows) are preserved. Flag changes that turn recoverable failures into unrecoverable lockouts. [MUST]
- Verify documented safe-mode activation mechanisms are not broken. [MUST]
- Flag reliance on "deactivate the plugin" as a recovery mechanism; the product must support safe recovery without forcing users to lose state. [MUST]
- For changes affecting snippet execution: check for a recovery and verification plan. [MUST]
- Check that proactive validation (syntax checks, duplicate identifier checks) does not produce false positives for legitimate WordPress patterns (e.g., pluggable functions). [SHOULD]
- Verify example snippet code (docs, help text, UI templates) uses anonymous functions and collision-resistant patterns. [SHOULD]

## Backwards Compatibility and Deprecation

- For any public API change (hooks/filters, public methods, REST endpoints, option names): verify the PR description documents compatibility impact and deprecation plan. [MUST]
- Verify deprecation shims with clear warnings and compatibility tests are provided when removing or changing public APIs. [MUST]
- Check for migration guidance and a compatibility matrix listing affected versions and suggested mitigations. [MUST]
- Verify automated tests exercise deprecated paths to ensure compatibility remains intact until removal. [MUST]
- Check for a code example showing how to migrate away from the deprecated API. [SHOULD]
- Check that user-facing deprecation messages are included where appropriate. [SHOULD]
