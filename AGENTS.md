# AGENTS.md — AI Agent Instructions

This file directs all non-Copilot AI agents (Claude, Gemini, Cursor, Aider, GPT-based tools, and any other
coding assistant) operating in this repository.

---

## Canonical instruction source

**All AI agent behavior for this repository is governed by a single canonical file:**

```
.github/copilot-instructions.md
```

You MUST read, parse, and follow `.github/copilot-instructions.md` in full before performing any task in this
repository. That file is the authoritative source of truth for:

- Project overview, architecture, and repository structure
- Tech stack and minimum requirements
- Coding standards (PHP, TypeScript/React, SCSS)
- Branching strategy and Git workflow
- Security checklist
- Testing requirements
- What to do and what never to do

Do not rely on your training-data assumptions about this project. Always read the instructions file first.

---

## Instruction centralization policy

Custom instructions for this repository are **defined once, for GitHub Copilot, in `.github/copilot-instructions.md`
and `.github/instructions/*.instructions.md`**. They are not duplicated in agent-specific config files
(`.cursorrules`, `claude.md`, `aider.conf`, or similar).

If you are an agent that supports agent-specific instruction files:
- Do **not** create a separate instructions file with overlapping rules.
- Do **not** override or contradict the rules in `.github/copilot-instructions.md`.
- **Do** read `.github/copilot-instructions.md` as your primary prompt context before each session.

Path-specific instruction files in `.github/instructions/` also apply when working on files that match their
`applyTo` glob. Read all relevant `*.instructions.md` files alongside the root instructions.

---

## MCP Tools — required setup check

This repository's workflows rely on two MCP tool servers. Before starting work, check whether they are available
and, if not, ask the user to set them up.

### Context7

Context7 provides up-to-date, version-specific documentation for every library used in this project
(WordPress APIs, React, TypeScript, Playwright, CodeMirror, etc.).

**Check:** Try to call `mcp_context7_resolve-library-id`. If the tool is unavailable, notify the user:

> "The Context7 MCP server is not available in this session. It provides accurate library docs that prevent
> outdated API usage. To set it up, run:
> `npx -y @upstash/context7-mcp@latest`
> then add it to your MCP config (VS Code `mcp.json` or equivalent). Please set it up and restart the session
> so I can use it for accurate documentation lookups."

**When to use (once available):**
- Before writing any code that calls a WordPress hook, REST API endpoint, or `@wordpress/*` package method.
- Before using any Playwright, React, or TypeScript API you are not 100% certain about.
- Use `resolve-library-id` → `query-docs` workflow for all library lookups.

### Chrome DevTools

Chrome DevTools MCP (`mcp_chrome-devtoo_*`) enables live browser inspection: DOM snapshots, console errors,
network request inspection, accessibility audits, and performance traces.

**Check:** Try to call `mcp_chrome-devtoo_list_pages`. If the tool is unavailable, notify the user:

> "The Chrome DevTools MCP server is not available in this session. It lets me inspect the live WordPress admin
> UI, verify REST API responses, and check accessibility. To set it up, install the Chrome DevTools MCP
> extension in VS Code or add `@modelcontextprotocol/server-chrome` to your MCP config, then restart the session."

**When to use (once available):**
- Debugging UI regressions, layout issues, or JS errors in the WP admin.
- Verifying REST API call payloads and responses.
- Validating keyboard navigation, ARIA attributes, and WCAG contrast for UI changes.
- Running performance traces on frontend pages.

---

## Quick-start checklist for agents

Before writing or modifying any code in this repository:

1. Read `.github/copilot-instructions.md` completely.
2. Read any `.github/instructions/*.instructions.md` file whose `applyTo` pattern matches the files you will touch.
3. Check that Context7 MCP is available; if not, ask the user to set it up before proceeding.
4. Check that Chrome DevTools MCP is available when your task involves browser/UI work; if not, ask the user.
5. Follow the branching strategy: branch from `core-beta` for all feature and fix work.
6. Apply the security checklist to every change before submitting.
7. Ensure tests (PHPUnit / Playwright) cover any new or changed behaviour.
8. Never ship `src/dist/` or `bundle/` build artifacts in a feature branch.

---

## Why this structure exists

GitHub Copilot is the primary AI assistant for this repository. Its instruction format
(`.github/copilot-instructions.md` + `.github/instructions/*.instructions.md`) is the most
expressive and maintainable way to encode project-wide and path-specific rules. Centralizing
all instructions here — and pointing other agents at these files — ensures:

- A single source of truth that is easy to review and update.
- Consistent behaviour across all AI tools without duplicating rules.
- Clear ownership: Copilot instructions define the standard; other agents follow it.
