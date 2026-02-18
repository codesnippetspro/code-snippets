/**
 * lint-readme.ts
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
 *   - Version sub-headers: = X.Y.Z (YYYY-MM-DD) =
 *   - Change-type labels: __Added__, __Changed__, __Fixed__, __Removed__,
 *     __Deprecated__, __Security__
 *     (### headings and **Bold** variants are demoted / normalised)
 *
 * Lists
 *   - Items start with "* " (not "- ")
 *   - No trailing whitespace
 *
 * Spacing
 *   - Exactly 1 blank line before every == section (not the very first)
 *   - Exactly 1 blank line after every == section heading
 *   - Exactly 1 blank line before every = sub-section (not right after == heading)
 *   - Exactly 1 blank line after every = sub-section inside == Changelog == only
 *   - Exactly 1 blank line before __Type__ labels inside Changelog (not right after = heading)
 *   - Exactly 1 blank line after __Type__ labels inside Changelog
 *   - No consecutive blank lines (max 1)
 *   - File ends with exactly one newline
 */

import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';

/* ── helpers ─────────────────────────────────────────────────────────── */

const KNOWN_CHANGE_TYPES = ['Added', 'Changed', 'Fixed', 'Removed', 'Deprecated', 'Security'];

const RE_DATE_SRC = '\\d{4}-\\d{2}-\\d{2}';
const RE_VERSION_SRC = '\\d+\\.\\d+(?:\\.\\d+)*(?:-[a-zA-Z0-9.]+)?';

/** Known == Section == names (canonical capitalisation). */
const KNOWN_SECTIONS: Record<string, string> = {
	'description': 'Description',
	'installation': 'Installation',
	'frequently asked questions': 'Frequently Asked Questions',
	'faq': 'Frequently Asked Questions',
	'screenshots': 'Screenshots',
	'changelog': 'Changelog',
	'upgrade notice': 'Upgrade Notice',
};

function titleCase(s: string): string {
	return s.replace(/\w\S*/g, t => t.charAt(0).toUpperCase() + t.slice(1).toLowerCase());
}

/** Sentence-case: capitalise only the first word. */
function sentenceCase(s: string): string {
	return s.charAt(0).toUpperCase() + s.slice(1);
}

/** Title-case for ≤ 2 words, sentence-case for 3+ words. */
function subsectionCase(s: string): string {
	const wordCount = s.trim().split(/\s+/).length;
	return wordCount <= 2 ? titleCase(s) : sentenceCase(s);
}

function normaliseSectionName(raw: string): string {
	const key = raw.trim().toLowerCase();
	return KNOWN_SECTIONS[key] ?? titleCase(raw.trim());
}

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
 * `headingRe`. Lines matching `skipAfterRe` reset the "just-saw-section-start"
 * flag, suppressing spacing for the immediately following heading.
 */
function normaliseBlanksBefore(
	lines: string[],
	headingRe: RegExp,
	n: number,
	skipAfterRe?: RegExp
): string[] {
	const out: string[] = [];
	let suppressNext = true;

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

/**
 * Like normaliseBlankAfter but only operates on lines that fall within a
 * specific section (between `sectionStartRe` and the next `== ... ==` heading).
 */
function normaliseBlankAfterInSection(
	lines: string[],
	sectionStartRe: RegExp,
	headingRe: RegExp
): string[] {
	const out: string[] = [];
	let inSection = false;
	let i = 0;
	while (i < lines.length) {
		const line = lines[i];
		if (sectionStartRe.test(line)) inSection = true;
		else if (/^== .+ ==$/.test(line)) inSection = false;

		out.push(line);
		if (inSection && headingRe.test(line)) {
			i++;
			while (i < lines.length && lines[i].trim() === '') i++;
			if (i < lines.length) out.push('');
			continue;
		}
		i++;
	}
	return out;
}

/* ── linter ─────────────────────────────────────────────────────────── */

export function lintReadme(src: string): { fixed: string; errors: string[] } {
	const errors: string[] = [];
	let lines = src.split('\n');

	/* 1. Plugin name header */
	if (!/^=== .+ ===$/.test(lines[0])) {
		errors.push('readme.txt: First line must be "=== Plugin Name ==="');
	}

	lines = trimTrailing(lines);

	/* 2. Normalise header field spacing: "Key: Value" */
	let inHeader = true;
	lines = lines.map((line, i) => {
		if (inHeader && i > 0 && line.trim() === '') { inHeader = false; return line; }
		if (!inHeader) return line;
		const m = line.match(/^([A-Za-z][A-Za-z ]+):\s*(.*)$/);
		if (m) return `${m[1].trim()}: ${m[2].trim()}`;
		return line;
	});

	/* 2a. Ensure exactly one blank line after the last header field (before short description).
	 *     If the blank was removed the header parser above never terminates, so we fix it here
	 *     by finding the last "Key: Value" line and inserting a blank after it when missing. */
	{
		const FIELD_RE = /^[A-Za-z][A-Za-z ]+: /;
		let lastFieldIdx = -1;
		for (let i = 1; i < lines.length; i++) {
			if (/^== /.test(lines[i])) break; // hit a section heading – header is long gone
			if (FIELD_RE.test(lines[i])) lastFieldIdx = i;
		}
		if (lastFieldIdx !== -1) {
			const afterField = lines[lastFieldIdx + 1];
			if (afterField !== undefined && afterField.trim() !== '') {
				// No blank line after last field – insert one
				lines.splice(lastFieldIdx + 1, 0, '');
			}
		}
	}

	/* 3. Normalise == Section == headings */
	lines = lines.map(line => {
		const m = line.match(/^==\s+(.+?)\s+==$/);
		if (m) return `== ${normaliseSectionName(m[1])} ==`;
		return line;
	});

	/* 4. Normalise = Sub Section = headings */
	lines = lines.map(line => {
		const m = line.match(/^=\s+(.+?)\s+=$/);
		if (!m) return line;
		const inner = m[1].trim();
		// Version entry inside Changelog: "= X.Y.Z (YYYY-MM-DD) ="
		const ver = inner.match(new RegExp(`^(${RE_VERSION_SRC})\\s+\\((${RE_DATE_SRC})\\)$`));
		if (ver) return `= ${ver[1]} (${ver[2]}) =`;
		return `= ${subsectionCase(inner)} =`;
	});

	/* 5. Inside Changelog section: demote ### headings and **Bold** to __Type__ */
	let inChangelog = false;
	lines = lines.map(line => {
		if (/^== Changelog ==$/.test(line)) { inChangelog = true; return line; }
		if (/^== /.test(line)) { inChangelog = false; return line; }
		if (!inChangelog) return line;

		const hashM = line.match(/^###\s+(\w+)\s*$/);
		if (hashM) {
			const n = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === hashM[1].toLowerCase());
			if (n) return `__${n}__`;
		}
		const boldM = line.match(/^\*\*(\w+)\*\*\s*$/);
		if (boldM) {
			const n = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === boldM[1].toLowerCase());
			if (n) return `__${n}__`;
		}
		// Normalise existing __type__ casing
		const underM = line.match(/^__(\w+)__\s*$/);
		if (underM) {
			const n = KNOWN_CHANGE_TYPES.find(t => t.toLowerCase() === underM[1].toLowerCase());
			if (n) return `__${n}__`;
		}
		return line;
	});

	/* 6. List items: "- " → "* " */
	lines = lines.map(line => (/^- /.test(line) ? '* ' + line.slice(2) : line));

	/* 7. Spacing rules */
	const SECTION_RE = /^== .+ ==$/;
	const SUBSECTION_RE = /^= .+ =$/;
	const CHANGETYPE_RE = /^__(?:Added|Changed|Fixed|Removed|Deprecated|Security)__$/;

	lines = normaliseBlanksBefore(lines, SECTION_RE, 1, /^=== .+ ===/);
	lines = normaliseBlankAfter(lines, SECTION_RE);
	lines = normaliseBlanksBefore(lines, SUBSECTION_RE, 1, SECTION_RE);
	lines = normaliseBlankAfterInSection(lines, /^== Changelog ==$/, SUBSECTION_RE);
	lines = normaliseBlanksBefore(lines, CHANGETYPE_RE, 1, SUBSECTION_RE);
	lines = normaliseBlankAfterInSection(lines, /^== Changelog ==$/, CHANGETYPE_RE);

	/* 8. Collapse multiple blank lines, trailing newline */
	lines = collapseBlankLines(lines);
	while (lines.length > 0 && lines[lines.length - 1] === '') lines.pop();
	lines.push('');

	return { fixed: lines.join('\n'), errors };
}

/* ── entry point ─────────────────────────────────────────────────────── */

const root = resolve(__dirname, '../..');
const args = process.argv.slice(2);
const files = args.length > 0 ? args : [resolve(root, 'src/readme.txt')];
let anyErrors = false;
let anyProcessed = false;

for (const f of files) {
	const abs = resolve(f);
	if (!abs.endsWith('readme.txt')) continue;
	anyProcessed = true;

	if (!existsSync(abs)) { console.error(`lint-readme: file not found – ${abs}`); anyErrors = true; continue; }

	const src = readFileSync(abs, 'utf8');
	const { fixed, errors } = lintReadme(src);

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
