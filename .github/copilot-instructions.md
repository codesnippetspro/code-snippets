# Code Snippets — Copilot Instructions

> **Project standards** live in `AGENTS.md` at the repository root. Read that file in full before performing
> any task. This file contains only GitHub Copilot-specific configuration.

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

**How to use:**

Use the `mcp_chrome-devtoo_*` family of tools — take a snapshot, navigate a page, inspect network requests,
evaluate scripts, or start a performance trace.

**If Chrome DevTools MCP is not installed:**

Ask the user: *"The Chrome DevTools MCP server is not available. Would you like to set it up? It lets me
inspect the live browser state. Install via the VS Code MCP extension or add `@modelcontextprotocol/server-chrome`
to your MCP config."*

---

## Path-Specific Instructions

More targeted rules live in `.github/instructions/` as `*.instructions.md` files:

| File | Scope |
|---|---|
| `code-review.instructions.md` | Copilot Code Review (CCR) — review standards (excludes coding agent) |

Add new `*.instructions.md` files here for language- or area-specific rules (e.g., `php.instructions.md`,
`react.instructions.md`).
