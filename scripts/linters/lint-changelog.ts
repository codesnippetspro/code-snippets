/**
 * Lint-changelog.ts
 *
 * Lints and auto-fixes CHANGELOG.md for formatting consistency.
 *
 * Rules enforced (based on current file conventions):
 *
 * File title
 *   - First line must be exactly: # Changelog
 *
 * Release headers
 *   - Format: ## [X.Y.Z] (YYYY-MM-DD)
 *     or: ## [X.Y.Z-modifier.N] (YYYY-MM-DD)
 *   - Non-bracketed versions (e.g. "## 3.6.5.1 (...)") are normalised to the
 *     bracketed form: ## [3.6.5.1] (YYYY-MM-DD)
 *   - Date format: YYYY-MM-DD (required)
 *
 * Section sub-headings
 *   - Allowed: ### Added, ### Changed, ### Fixed, ### Removed,
 *              ### Deprecated, ### Security
 *   - Bold variants (**Added**, __Added__) are promoted to ### headings
 *   - Casing is normalised to the canonical form above
 *
 * Lists
 *   - Items start with "* " (not "- ")
 *   - No trailing whitespace
 *
 * Spacing
 *   - Exactly 1 blank line before every ## heading (not before the first one)
 *   - Exactly 1 blank line after every # and ## headings
 *   - No blank lines after ### headings
 *   - Exactly 1 blank line before every ### heading (not immediately after ##)
 *   - No consecutive blank lines (max 1)
 *   - File ends with exactly one newline
 */

import { existsSync, readFileSync, writeFileSync } from 'fs'
import { resolve } from 'path'

/* ── helpers ─────────────────────────────────────────────────────────── */

const KNOWN_CHANGE_TYPES = ['Added', 'Changed', 'Fixed', 'Removed', 'Deprecated', 'Security']

const CLI_ARGS_START_INDEX = 2

const trimTrailing = (lines: string[]): string[] =>
	lines.map(l => l.trimEnd())

const collapseBlankLines = (lines: string[]): string[] => {
	const out: string[] = []
	let prevBlank = false
	for (const l of lines) {
		const blank = '' === l.trim()
		if (blank && prevBlank) {continue}
		out.push(l)
		prevBlank = blank
	}
	return out
}

/**
 * Ensure exactly `n` blank lines appear immediately before every line matching
 * `headingRe`. Lines matching `skipAfterRe` suppress spacing for the immediately
 * following heading (used to avoid a blank line between a ## and its first ###).
 */
const normaliseBlanksBefore = (
	lines: string[],
	headingRe: RegExp,
	n: number,
	skipAfterRe?: RegExp
): string[] => {
	const out: string[] = []
	let suppressNext = true // Suppress before very first heading

	for (const line of lines) {
		if (skipAfterRe?.test(line)) {
			suppressNext = true
			out.push(line)
			continue
		}

		if (headingRe.test(line)) {
			if (!suppressNext) {
				while (0 < out.length && '' === out[out.length - 1].trim()) {out.pop()}
				for (let b = 0; b < n; b += 1) {out.push('')}
			}
			out.push(line)
			suppressNext = false
			continue
		}

		if ('' !== line.trim()) {suppressNext = false}
		out.push(line)
	}
	return out
}

/** Ensure exactly 1 blank line immediately after every line matching `headingRe`. */
const normaliseBlankAfter = (lines: string[], headingRe: RegExp): string[] => {
	const out: string[] = []
	let i = 0
	while (i < lines.length) {
		const line = lines[i]
		out.push(line)
		if (headingRe.test(line)) {
			i += 1
			while (i < lines.length && '' === lines[i].trim()) {i += 1}
			if (i < lines.length) {out.push('')}
			continue
		}
		i += 1
	}
	return out
}

/** Remove any blank lines immediately after lines matching `headingRe`. */
const removeBlankAfter = (lines: string[], headingRe: RegExp): string[] => {
	const out: string[] = []
	let i = 0
	while (i < lines.length) {
		const line = lines[i]
		out.push(line)
		if (headingRe.test(line)) {
			i += 1
			// Skip all blank lines following the heading
			while (i < lines.length && '' === lines[i].trim()) {i += 1}
			continue
		}
		i += 1
	}
	return out
}

/* ── linter ─────────────────────────────────────────────────────────── */

const ensureTitle = (lines: string[], errors: string[]): string[] => {
	if ('# Changelog' !== lines[0]) {
		if (/^#\s+changelog/i.test(lines[0])) {
			lines[0] = '# Changelog'
		} else {
			errors.push('CHANGELOG.md: First line must be "# Changelog"')
		}
	}

	return lines
}

const promoteBoldChangeTypeLabels = (lines: string[]): string[] =>
	lines.map(line => {
		const match = /^\s*(?:\*\*|__)(?<type>\w+)(?:\*\*|__)\s*$/.exec(line)
		if (!match?.groups) {return line}
		const { type } = match.groups
		const canonical = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === type.toLowerCase())
		return canonical ? `### ${canonical}` : line
	})

const normaliseReleaseHeaders = (lines: string[], errors: string[]): string[] =>
	lines.map(line => {
		const bracketed = /^## \[(?<ver>[^\]]+)\]\s*\((?<date>\d{4}-\d{2}-\d{2}|[A-Z][A-Z0-9-]*)\)/.exec(line)
		if (bracketed?.groups) {return `## [${bracketed.groups.ver}] (${bracketed.groups.date})`}

		const bracketedMissingClose = /^## \[(?<ver>[^\]]+)\]\s*\((?<date>\d{4}-\d{2}-\d{2}|[A-Z][A-Z0-9-]*)$/.exec(line)
		if (bracketedMissingClose?.groups) {
			return `## [${bracketedMissingClose.groups.ver}] (${bracketedMissingClose.groups.date})`
		}

		const bracketedNoDate = /^## \[(?<ver>[^\]]+)\]/.exec(line)
		if (bracketedNoDate) {
			errors.push(`CHANGELOG.md: Release header missing or malformed date: ${line}`)
			return line
		}

		const plain = /^## (?<ver>\d[^\s(]+)\s*(?:\((?<date>\d{4}-\d{2}-\d{2}|[A-Z][A-Z0-9-]*)\))?/.exec(line)
		if (!plain?.groups) {return line}
		if (plain.groups.date) {return `## [${plain.groups.ver}] (${plain.groups.date})`}
		errors.push(`CHANGELOG.md: Release header missing date: ${line}`)
		return `## [${plain.groups.ver}]`
	})

const normaliseSectionNames = (lines: string[]): string[] =>
	lines.map(line => {
		const match = /^###\s+(?<name>.+)$/.exec(line)
		if (!match?.groups) {return line}
		let key = match.groups.name.trim()
		let canonical = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === key.toLowerCase())
		if (!canonical && /s$/i.test(key)) {
			const singular = key.replace(/s$/i, '')
			canonical = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === singular.toLowerCase())
			if (canonical) {key = singular}
		}
		return canonical ? `### ${canonical}` : `### ${key}`
	})

const normaliseIndentedSubListItems = (lines: string[]): string[] => {
	const out: string[] = []

	for (const line of lines) {
		const indented = /^ {2}[*-] (?<text>.*)$/.exec(line)
		if (!indented?.groups) {
			out.push(line)
			continue
		}

		const text = indented.groups.text.trimEnd()
		let parentIdx = -1
		for (let j = out.length - 1; 0 <= j; j -= 1) {
			if ('' === out[j].trim()) {break}
			if (out[j].startsWith('* ')) {
				parentIdx = j
				break
			}
		}

		if (-1 !== parentIdx) {
			const parentTrimmed = out[parentIdx].trimEnd()
			if (!parentTrimmed.endsWith(':')) {
				out[parentIdx] = `${parentTrimmed.replace(/[.,;]$/, '')}:`
			}
			out.push(`  - ${text}`)
		} else {
			out.push(`* ${text}`)
		}
	}

	return out
}

const normaliseTopLevelBulletMarkers = (lines: string[]): string[] =>
	lines.map(line => {
		const match = /^(?<bullet>[*-]) (?<content>.*)$/.exec(line)
		if (match?.groups) {return `* ${match.groups.content.trimEnd()}`}
		return line
	})

const applySpacingRules = (lines: string[]): string[] => {
	const RELEASE_RE = /^## /
	const SECTION_RE = /^### /
	const TITLE_RE = /^# Changelog/

	let out = lines
	out = normaliseBlankAfter(out, TITLE_RE)
	out = normaliseBlanksBefore(out, RELEASE_RE, 1, TITLE_RE)
	out = normaliseBlankAfter(out, RELEASE_RE)
	out = normaliseBlanksBefore(out, SECTION_RE, 1, RELEASE_RE)
	out = removeBlankAfter(out, SECTION_RE)
	return out
}

const finaliseLines = (lines: string[]): string[] => {
	const out = collapseBlankLines(lines)
	while (0 < out.length && '' === out[out.length - 1]) {out.pop()}
	out.push('')
	return out
}

export const lintChangelog = (src: string): { fixed: string; errors: string[] } => {
	const errors: string[] = []
	let lines = src.split('\n')

	lines = ensureTitle(lines, errors)
	lines = trimTrailing(lines)
	lines = promoteBoldChangeTypeLabels(lines)
	lines = normaliseReleaseHeaders(lines, errors)
	lines = normaliseSectionNames(lines)
	lines = normaliseIndentedSubListItems(lines)
	lines = normaliseTopLevelBulletMarkers(lines)
	lines = applySpacingRules(lines)
	lines = finaliseLines(lines)

	return { fixed: lines.join('\n'), errors }
}

/* ── entry point ─────────────────────────────────────────────────────── */

const root = resolve(__dirname, '../..')
const args = process.argv.slice(CLI_ARGS_START_INDEX)
const files = 0 < args.length ? args : [resolve(root, 'CHANGELOG.md')]
let anyErrors = false
let anyProcessed = false

for (const f of files) {
	const abs = resolve(f)
	if (!abs.endsWith('CHANGELOG.md')) {continue}
	anyProcessed = true

	if (!existsSync(abs)) { console.error(`lint-changelog: file not found – ${abs}`); anyErrors = true; continue }

	const src = readFileSync(abs, 'utf8')
	const { fixed, errors } = lintChangelog(src)

	errors.forEach(e => console.error(`  ✗ ${e}`))
	if (0 < errors.length) {anyErrors = true}

	if (fixed !== src) {
		writeFileSync(abs, fixed, 'utf8')
		console.log(`  ✔ auto-fixed: ${abs}`)
	} else {
		console.log(`  ✔ no changes: ${abs}`)
	}
}

if (!anyProcessed) {process.exit(0)}
process.exit(anyErrors ? 1 : 0)
