/**
 * lint-changelog.ts
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

import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';

/* ── helpers ─────────────────────────────────────────────────────────── */

const KNOWN_CHANGE_TYPES = ['Added', 'Changed', 'Fixed', 'Removed', 'Deprecated', 'Security'];

function trimTrailing(lines: string[]): string[] {
	return lines.map(l => l.trimEnd());
}

function collapseBlankLines(lines: string[]): string[] {
	const out: string[] = [];
	let prevBlank = false;
	for (const l of lines) {
		const blank = l.trim() === '';
		if (blank && prevBlank) continue;
		out.push(l);
		prevBlank = blank;
	}
	return out;
}

/**
 * Ensure exactly `n` blank lines appear immediately before every line matching
 * `headingRe`. Lines matching `skipAfterRe` suppress spacing for the immediately
 * following heading (used to avoid a blank line between a ## and its first ###).
 */
function normaliseBlanksBefore(
	lines: string[],
	headingRe: RegExp,
	n: number,
	skipAfterRe?: RegExp
): string[] {
	const out: string[] = [];
	let suppressNext = true; // suppress before very first heading

	for (let i = 0; i < lines.length; i++) {
		const line = lines[i];

		if (skipAfterRe && skipAfterRe.test(line)) {
			suppressNext = true;
			out.push(line);
			continue;
		}

		if (headingRe.test(line)) {
			if (!suppressNext) {
				while (out.length > 0 && out[out.length - 1].trim() === '') out.pop();
				for (let b = 0; b < n; b++) out.push('');
			}
			out.push(line);
			suppressNext = false;
			continue;
		}

		if (line.trim() !== '') suppressNext = false;
		out.push(line);
	}
	return out;
}

/** Ensure exactly 1 blank line immediately after every line matching `headingRe`. */
function normaliseBlankAfter(lines: string[], headingRe: RegExp): string[] {
	const out: string[] = [];
	let i = 0;
	while (i < lines.length) {
		const line = lines[i];
		out.push(line);
		if (headingRe.test(line)) {
			i++;
			while (i < lines.length && lines[i].trim() === '') i++;
			if (i < lines.length) out.push('');
			continue;
		}
		i++;
	}
	return out;
}

/** Remove any blank lines immediately after lines matching `headingRe`. */
function removeBlankAfter(lines: string[], headingRe: RegExp): string[] {
	const out: string[] = [];
	let i = 0;
	while (i < lines.length) {
		const line = lines[i];
		out.push(line);
		if (headingRe.test(line)) {
			i++;
			// skip all blank lines following the heading
			while (i < lines.length && lines[i].trim() === '') i++;
			continue;
		}
		i++;
	}
	return out;
}

/* ── linter ─────────────────────────────────────────────────────────── */

export function lintChangelog(src: string): { fixed: string; errors: string[] } {
	const errors: string[] = [];
	let lines = src.split('\n');

	/* 1. File title */
	if (lines[0] !== '# Changelog') {
		if (/^#\s+changelog/i.test(lines[0])) {
			lines[0] = '# Changelog';
		} else {
			errors.push('CHANGELOG.md: First line must be "# Changelog"');
		}
	}

	lines = trimTrailing(lines);

	/* 2. Promote bold change-type labels to ### headings */
	lines = lines.map(line => {
		const m = line.match(/^\s*(?:\*\*|__)(\w+)(?:\*\*|__)\s*$/);
		if (m) {
			const n = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === m[1].toLowerCase());
			if (n) return `### ${n}`;
		}
		return line;
	});

	/* 3. Normalise release headers to ## [X.Y.Z] (YYYY-MM-DD) */
	lines = lines.map(line => {
		// Already bracketed: ## [X.Y.Z] (YYYY-MM-DD)
		const bracketed = line.match(/^## \[([^\]]+)\]\s*\((\d{4}-\d{2}-\d{2})\)/);
		if (bracketed) return `## [${bracketed[1]}] (${bracketed[2]})`;

		// Bracketed with date but missing closing paren (e.g. "## [1.2.3] (2021-01-01")
		const bracketedMissingClose = line.match(/^## \[([^\]]+)\]\s*\((\d{4}-\d{2}-\d{2})$/);
		if (bracketedMissingClose) return `## [${bracketedMissingClose[1]}] (${bracketedMissingClose[2]})`;

		// Bracketed but missing / malformed date
		const bracketedNoDate = line.match(/^## \[([^\]]+)\]/);
		if (bracketedNoDate) {
			errors.push(`CHANGELOG.md: Release header missing or malformed date: ${line}`);
			return line;
		}

		// Non-bracketed: ## X.Y.Z (YYYY-MM-DD)
		const plain = line.match(/^## (\d[^\s(]+)\s*(?:\((\d{4}-\d{2}-\d{2})\))?/);
		if (plain) {
			if (plain[2]) return `## [${plain[1]}] (${plain[2]})`;
			errors.push(`CHANGELOG.md: Release header missing date: ${line}`);
			return `## [${plain[1]}]`;
		}

		return line;
	});

	/* 4. Normalise ### section names */
	lines = lines.map(line => {
		const m = line.match(/^###\s+(.+)$/);
		if (!m) return line;
	let key = m[1].trim();
	let n = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === key.toLowerCase());
	// tolerate accidental plural: Fixeds -> Fixed
	if (!n && /s$/i.test(key)) {
		const singular = key.replace(/s$/i, '');
		n = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === singular.toLowerCase());
		if (n) key = singular;
	}
	return n ? `### ${n}` : `### ${key}`;
	});

	/* 5. Handle indented sub-list items ("  * ..." or "  - ...") BEFORE normalising
	 *    top-level bullets, so we can still detect the indentation.
	 *   - If the nearest preceding unindented `* ` line ends with `:`, convert to `  - `
	 *   - If that parent line does NOT end with `:`, append `:` to it, then convert
	 *   - If no unindented `* ` parent found in the same block → simple indent error,
	 *     de-indent to `* `
	 * A blank line breaks the parent–child relationship.
	 */
	{
		const out: string[] = [];
		for (let i = 0; i < lines.length; i++) {
			const line = lines[i];
			const indented = line.match(/^  [*-] (.*)$/);
			if (!indented) {
				out.push(line);
				continue;
			}
			const text = indented[1].trimEnd();
			// Scan backwards through out[] for nearest unindented `* ` parent (stop at blank line)
			let parentIdx = -1;
			for (let j = out.length - 1; j >= 0; j--) {
				if (out[j].trim() === '') break;
				if (/^\* /.test(out[j])) { parentIdx = j; break; }
			}
			if (parentIdx !== -1) {
				// Ensure parent ends with ':' (replace trailing '.' or ',' if present)
				const parentTrimmed = out[parentIdx].trimEnd();
				if (!parentTrimmed.endsWith(':')) {
					out[parentIdx] = parentTrimmed.replace(/[.,;]$/, '') + ':';
				}
				out.push(`  - ${text}`);
			} else {
				// No valid parent – simple indentation error, promote to top-level
				out.push(`* ${text}`);
			}
		}
		lines = out;
	}

	/* 5b. Normalise remaining top-level bullet markers to "* " */
	lines = lines.map(line => {
		const m = line.match(/^([*-]) (.*)$/);
		if (m) return `* ${m[2].trimEnd()}`;
		return line;
	});

	/* 6. Spacing rules */
	const RELEASE_RE = /^## /;
	const SECTION_RE = /^### /;
	const TITLE_RE = /^# Changelog/;

	// Ensure one blank line after the main title and after each release header
	lines = normaliseBlankAfter(lines, TITLE_RE);
	lines = normaliseBlanksBefore(lines, RELEASE_RE, 1, TITLE_RE);
	lines = normaliseBlankAfter(lines, RELEASE_RE);
	// Ensure exactly one blank before each section (###) but remove any blank lines
	// immediately after a section heading (we require no blank-after for ###).
	lines = normaliseBlanksBefore(lines, SECTION_RE, 1, RELEASE_RE);
	lines = removeBlankAfter(lines, SECTION_RE);

	/* 7. Collapse multiple blank lines, trailing newline */
	lines = collapseBlankLines(lines);
	while (lines.length > 0 && lines[lines.length - 1] === '') lines.pop();
	lines.push('');

	return { fixed: lines.join('\n'), errors };
}

/* ── entry point ─────────────────────────────────────────────────────── */

const root = resolve(__dirname, '../..');
const args = process.argv.slice(2);
const files = args.length > 0 ? args : [resolve(root, 'CHANGELOG.md')];
let anyErrors = false;
let anyProcessed = false;

for (const f of files) {
	const abs = resolve(f);
	if (!abs.endsWith('CHANGELOG.md')) continue;
	anyProcessed = true;

	if (!existsSync(abs)) { console.error(`lint-changelog: file not found – ${abs}`); anyErrors = true; continue; }

	const src = readFileSync(abs, 'utf8');
	const { fixed, errors } = lintChangelog(src);

	errors.forEach(e => console.error(`  ✗ ${e}`));
	if (errors.length > 0) anyErrors = true;

	if (fixed !== src) {
		writeFileSync(abs, fixed, 'utf8');
		console.log(`  ✔ auto-fixed: ${abs}`);
	} else {
		console.log(`  ✔ no changes: ${abs}`);
	}
}

if (!anyProcessed) process.exit(0);
process.exit(anyErrors ? 1 : 0);
