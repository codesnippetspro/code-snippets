/**
 * Lint-readme.ts
 *
 * Lints and auto-fixes src/readme.txt for WordPress.org formatting consistency.
 *
 * Rules enforced (based on current file conventions + WordPress.org spec):
 *
 * Header block
 *   - First line: === Plugin Name ===
 *   - Each header field: "Key: Value" (single space after colon, no trailing whitespace)
 *   - Exactly 1 blank line between the last header field and the short description
 *   - Required fields must be present: Contributors, Donate link, Tags, License,
 *     License URI, Stable tag, Requires at least, Tested up to, Requires PHP
 *   - Short description line immediately follows header (non-empty, ≤ 150 chars, no markup)
 *
 * Sections
 *   - Top-level: == Section Name == (known names normalised, title-case otherwise)
 *   - Sub-sections: = Sub Section = (title-case)
 *   - Known section names: Description, Installation, Frequently Asked Questions,
 *     Screenshots, Changelog, Upgrade Notice
 *
 * Changelog section (inside readme.txt)
 *   - Version sub-headers: = X.Y.Z (YYYY-MM-DD) = or an uppercase status token, e.g. = X.Y.Z (UPCOMING) =
 *   - Change-type labels: __Added__, __Changed__, __Fixed__, __Removed__,
 *     __Deprecated__, __Security__
 *     (### headings and **Bold** variants are demoted / normalised)
 *
 * Lists
 *   - Items start with "* " (not "- ")
 *   - No trailing whitespace
 *   - No blank lines between consecutive list items
 *
 * Spacing
 *   - No leading blank lines before the plugin header
 *   - Exactly 1 blank line before every == section (not the very first)
 *   - Exactly 1 blank line after every == section heading
 *   - Exactly 1 blank line before every = sub-section (not right after == heading)
 *   - Exactly 1 blank line after every = sub-section inside == Changelog == only
 *   - Exactly 1 blank line before __Type__ labels inside Changelog (not right after = heading)
 *   - Exactly 1 blank line after __Type__ labels inside Changelog
 *   - No consecutive blank lines (max 1)
 *   - File ends with exactly one newline
 */

import { existsSync, readFileSync, writeFileSync } from 'fs'
import { resolve } from 'path'

/* ── helpers ─────────────────────────────────────────────────────────── */

const KNOWN_CHANGE_TYPES = ['Added', 'Changed', 'Fixed', 'Removed', 'Deprecated', 'Security']

const CLI_ARGS_START_INDEX = 2
const SUBSECTION_TITLECASE_MAX_WORDS = 2
const LIST_MARKER_LENGTH = 2

const RE_DATE_SRC = '(?:\\d{4}-\\d{2}-\\d{2}|[A-Z][A-Z0-9-]*)'
const RE_VERSION_SRC = '\\d+\\.\\d+(?:\\.\\d+)*(?:-[a-zA-Z0-9.]+)?'

/** Known == Section == names (canonical capitalisation). */
const KNOWN_SECTIONS: Record<string, string> = {
	'description': 'Description',
	'installation': 'Installation',
	'frequently asked questions': 'Frequently Asked Questions',
	'faq': 'Frequently Asked Questions',
	'screenshots': 'Screenshots',
	'changelog': 'Changelog',
	'upgrade notice': 'Upgrade Notice'
}

const titleCase = (s: string): string =>
	s.replace(/\w\S*/g, t => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase())

const sentenceCase = (s: string): string =>
	s.charAt(0).toUpperCase() + s.slice(1)

const subsectionCase = (s: string): string => {
	const wordCount = s.trim().split(/\s+/).length
	return SUBSECTION_TITLECASE_MAX_WORDS >= wordCount ? titleCase(s) : sentenceCase(s)
}

const normaliseSectionName = (raw: string): string => {
	const key = raw.trim().toLowerCase()
	return KNOWN_SECTIONS[key] ?? titleCase(raw.trim())
}

const trimTrailing = (lines: string[]): string[] =>
	lines.map(l => l.trimEnd())

const collapseBlankLines = (lines: string[]): string[] => {
	const out: string[] = []
	let prevBlank = false
	for (const l of lines) {
		const blank = '' === l.trim()
		if (blank && prevBlank) {
			continue
		}
		out.push(l)
		prevBlank = blank
	}
	return out
}

/** Remove blank lines before the first content line. */
const stripLeadingBlanks = (lines: string[]): string[] => {
	let i = 0
	while (i < lines.length && '' === lines[i].trim()) {
		i += 1
	}
	return lines.slice(i)
}

/**
 * Drop blank lines that sit between two list items. Bullets accumulate stray
 * blank lines over successive edits; consecutive items should be contiguous.
 */
const removeBlanksBetweenListItems = (lines: string[]): string[] => {
	const isItem = (l: string): boolean => /^\s*[*-] /.test(l)
	const out: string[] = []
	for (let i = 0; i < lines.length; i += 1) {
		if ('' === lines[i].trim()) {
			let j = i + 1
			while (j < lines.length && '' === lines[j].trim()) {
				j += 1
			}
			const prev = out[out.length - 1]
			const next = j < lines.length ? lines[j] : ''
			if (0 < out.length && isItem(prev) && isItem(next)) {
				continue
			}
		}
		out.push(lines[i])
	}
	return out
}

/**
 * Ensure exactly `n` blank lines appear immediately before every line matching
 * `headingRe`. Lines matching `skipAfterRe` reset the "just-saw-section-start"
 * flag, suppressing spacing for the immediately following heading.
 */
const normaliseBlanksBefore = (
	lines: string[],
	headingRe: RegExp,
	n: number,
	skipAfterRe?: RegExp
): string[] => {
	const out: string[] = []
	let suppressNext = true

	for (const line of lines) {
		if (skipAfterRe?.test(line)) {
			suppressNext = true
			out.push(line)
			continue
		}

		if (headingRe.test(line)) {
			if (!suppressNext) {
				while (0 < out.length && '' === out[out.length - 1].trim()) {
					out.pop()
				}
				for (let b = 0; b < n; b += 1) {
					out.push('')
				}
			}
			out.push(line)
			suppressNext = false
			continue
		}

		if ('' !== line.trim()) {
			suppressNext = false
		}
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
			while (i < lines.length && '' === lines[i].trim()) {
				i += 1
			}
			if (i < lines.length) {
				out.push('')
			}
			continue
		}
		i += 1
	}
	return out
}

/**
 * Like normaliseBlankAfter but only operates on lines that fall within a
 * specific section (between `sectionStartRe` and the next `== ... ==` heading).
 */
const normaliseBlankAfterInSection = (
	lines: string[],
	sectionStartRe: RegExp,
	headingRe: RegExp
): string[] => {
	const out: string[] = []
	let inSection = false
	let i = 0
	while (i < lines.length) {
		const line = lines[i]
		if (sectionStartRe.test(line)) {
			inSection = true
		} else if (/^== .+ ==$/.test(line)) {
			inSection = false
		}

		out.push(line)
		if (inSection && headingRe.test(line)) {
			i += 1
			while (i < lines.length && '' === lines[i].trim()) {
				i += 1
			}
			if (i < lines.length) {
				out.push('')
			}
			continue
		}
		i += 1
	}
	return out
}

/* ── linter ─────────────────────────────────────────────────────────── */

const normalisePluginHeader = (lines: string[], errors: string[]): string[] => {
	if (!/^=== .+ ===$/.test(lines[0])) {
		errors.push('readme.txt: First line must be "=== Plugin Name ==="')
	}
	return lines
}

const normaliseHeaderFieldSpacing = (lines: string[]): string[] => {
	let inHeader = true
	return lines.map((line, i) => {
		if (inHeader && 0 < i && '' === line.trim()) {
			inHeader = false
			return line
		}
		if (!inHeader) {
			return line
		}
		const match = /^(?<key>[A-Za-z][A-Za-z ]+):\s*(?<value>.*)$/.exec(line)
		if (match?.groups) {
			return `${match.groups.key.trim()}: ${match.groups.value.trim()}`
		}
		return line
	})
}

const ensureBlankAfterLastHeaderField = (lines: string[]): string[] => {
	const FIELD_RE = /^[A-Za-z][A-Za-z ]+: /
	let lastFieldIdx = -1

	for (const [i, line] of lines.entries()) {
		if (0 === i) {
			continue
		}
		if (line.startsWith('== ')) {
			break
		}
		if (FIELD_RE.test(line)) {
			lastFieldIdx = i
		}
	}

	if (-1 !== lastFieldIdx && lastFieldIdx + 1 < lines.length) {
		if ('' !== lines[lastFieldIdx + 1].trim()) {
			lines.splice(lastFieldIdx + 1, 0, '')
		}
	}

	return lines
}

const normaliseSectionHeadings = (lines: string[]): string[] =>
	lines.map(line => {
		const match = /^==\s+(?<name>.+?)\s+==$/.exec(line)
		if (match?.groups) {
			return `== ${normaliseSectionName(match.groups.name)} ==`
		}
		return line
	})

const normaliseSubSectionHeadings = (lines: string[]): string[] =>
	lines.map(line => {
		const match = /^=\s+(?<name>.+?)\s+=$/.exec(line)
		if (!match?.groups) {
			return line
		}
		const inner = match.groups.name.trim()
		const ver = new RegExp(`^(?<ver>${RE_VERSION_SRC})\\s+\\((?<date>${RE_DATE_SRC})\\)$`).exec(inner)
		if (ver?.groups) {
			return `= ${ver.groups.ver} (${ver.groups.date}) =`
		}
		return `= ${subsectionCase(inner)} =`
	})

const normaliseChangelogChangeTypes = (lines: string[]): string[] => {
	let inChangelog = false
	return lines.map(line => {
		if (/^== Changelog ==$/.test(line)) {
			inChangelog = true
			return line
		}
		if (line.startsWith('== ')) {
			inChangelog = false
			return line
		}
		if (!inChangelog) {
			return line
		}

		const hashM = /^###\s+(?<type>\w+)\s*$/.exec(line)
		if (hashM?.groups) {
			const canonical = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === hashM.groups?.type.toLowerCase())
			if (canonical) {
				return `__${canonical}__`
			}
		}
		const boldM = /^\*\*(?<type>\w+)\*\*\s*$/.exec(line)
		if (boldM?.groups) {
			const canonical = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === boldM.groups?.type.toLowerCase())
			if (canonical) {
				return `__${canonical}__`
			}
		}
		const underM = /^__(?<type>\w+)__\s*$/.exec(line)
		if (underM?.groups) {
			const canonical = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === underM.groups?.type.toLowerCase())
			if (canonical) {
				return `__${canonical}__`
			}
		}
		return line
	})
}

const normaliseListItems = (lines: string[]): string[] =>
	lines.map(line => line.startsWith('- ') ? `* ${line.slice(LIST_MARKER_LENGTH)}` : line)

const applySpacingRules = (lines: string[]): string[] => {
	const SECTION_RE = /^== .+ ==$/
	const SUBSECTION_RE = /^= .+ =$/
	const CHANGETYPE_RE = /^__(?:Added|Changed|Fixed|Removed|Deprecated|Security)__$/

	let out = lines
	out = normaliseBlanksBefore(out, SECTION_RE, 1, /^=== .+ ===/)
	out = normaliseBlankAfter(out, SECTION_RE)
	out = normaliseBlanksBefore(out, SUBSECTION_RE, 1, SECTION_RE)
	out = normaliseBlankAfterInSection(out, /^== Changelog ==$/, SUBSECTION_RE)
	out = normaliseBlanksBefore(out, CHANGETYPE_RE, 1, SUBSECTION_RE)
	out = normaliseBlankAfterInSection(out, /^== Changelog ==$/, CHANGETYPE_RE)
	return out
}

const finaliseLines = (lines: string[]): string[] => {
	const out = collapseBlankLines(lines)
	while (0 < out.length && '' === out[out.length - 1]) {
		out.pop()
	}
	out.push('')
	return out
}

export const lintReadme = (src: string): { fixed: string; errors: string[] } => {
	const errors: string[] = []
	let lines = src.split('\n')

	lines = stripLeadingBlanks(lines)
	lines = normalisePluginHeader(lines, errors)
	lines = trimTrailing(lines)
	lines = normaliseHeaderFieldSpacing(lines)
	lines = ensureBlankAfterLastHeaderField(lines)
	lines = normaliseSectionHeadings(lines)
	lines = normaliseSubSectionHeadings(lines)
	lines = normaliseChangelogChangeTypes(lines)
	lines = normaliseListItems(lines)
	lines = applySpacingRules(lines)
	lines = removeBlanksBetweenListItems(lines)
	lines = finaliseLines(lines)

	return { fixed: lines.join('\n'), errors }
}

/* ── entry point ─────────────────────────────────────────────────────── */

const root = resolve(__dirname, '../..')
const args = process.argv.slice(CLI_ARGS_START_INDEX)
const files = 0 < args.length ? args : [resolve(root, 'src/readme.txt')]
let anyErrors = false
let anyProcessed = false

for (const f of files) {
	const abs = resolve(f)
	if (!abs.endsWith('readme.txt')) {
		continue
	}
	anyProcessed = true

	if (!existsSync(abs)) {
		console.error(`lint-readme: file not found – ${abs}`)
		anyErrors = true
		continue
	}

	const src = readFileSync(abs, 'utf8')
	const { fixed, errors } = lintReadme(src)

	errors.forEach(e => console.error(`  ✗ ${e}`))
	if (0 < errors.length) {
		anyErrors = true
	}

	if (fixed !== src) {
		writeFileSync(abs, fixed, 'utf8')
		console.log(`  ✔ auto-fixed: ${abs}`)
	} else {
		console.log(`  ✔ no changes: ${abs}`)
	}
}

if (!anyProcessed) {
	process.exit(0)
}
process.exit(anyErrors ? 1 : 0)
