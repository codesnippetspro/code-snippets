import { expect, test } from '@playwright/test'
import { decodeEntities, stripTags } from '../../src/js/utils/text'

// Pure unit coverage for the text utilities used to render remote cloud
// descriptions; runs in the Playwright runner without a browser page.
test.describe('stripTags', () => {
	test('preserves separation between block elements', () => {
		expect(stripTags('<p>First</p><p>Second</p>')).toBe('First Second')
		expect(stripTags('Line one<br>Line two')).toBe('Line one Line two')
		expect(stripTags('<ul><li>One</li><li>Two</li></ul>')).toBe('One Two')
	})

	test('handles ">" inside quoted attribute values', () => {
		expect(stripTags('<p title="1 > 0">First</p><p>Second</p>')).toBe('First Second')
		expect(stripTags("<span title='a > b'>inline</span>")).toBe('inline')
	})

	test('handles malformed tags with unbalanced quotes', () => {
		expect(stripTags('<p title="unterminated>Visible</p>')).toBe('Visible')
		expect(stripTags("<p title='unterminated>Visible</p>")).toBe('Visible')
		expect(stripTags('<div class="a>One</div><p>Two</p>')).toBe('One Two')
		expect(stripTags('<span data-x="broken>inline</span> text')).toBe('inline text')
	})

	test('preserves plain comparison text at end of input', () => {
		expect(stripTags('x<y')).toBe('x<y')
		expect(stripTags('x<y and z')).toBe('x<y and z')
		expect(stripTags('x<p')).toBe('x<p')
	})

	test('strips unterminated-quote tag remnant at end of input', () => {
		expect(stripTags('Visible <p title="broken')).toBe('Visible')
		expect(stripTags("Visible <span data-x='broken")).toBe('Visible')
		expect(stripTags('keep <x till <p title="broken')).toBe('keep <x till')
		expect(stripTags('a <x"m<z"')).toBe('a <x"m')
		expect(stripTags('a <x "q" ok')).toBe('a <x "q" ok')
	})

	test('strips block remnants before generic remnants', () => {
		expect(stripTags('<span <div x> y')).toBe('<span y')
	})

	test('scales linearly on repeated comparison text', () => {
		const input = 'if (a<b && c<d) run(); '.repeat(16000)
		const started = performance.now()
		expect(stripTags(input)).toBe(input.trim())
		expect(performance.now() - started).toBeLessThan(2000)
	})

	test('removes inline tags without adding separators', () => {
		expect(stripTags('Co<strong>de</strong> <em>snippets</em>')).toBe('Code snippets')
		expect(stripTags('<a href="https://example.com">link</a>')).toBe('link')
	})

	test('strips comments and PHP blocks', () => {
		expect(stripTags('A<!-- hidden --><?php evil(); ?>B')).toBe('AB')
		expect(stripTags('<!--a--><!--b-->C<??><?PHP d?>')).toBe('C')
		expect(stripTags('A<!-- unterminated')).toBe('A<!-- unterminated')
		expect(stripTags('B<?php unterminated')).toBe('B<?php unterminated')
	})

	test('scales linearly on repeated unmatched comment and PHP openers', () => {
		const timeStrip = (opener: string, repeats: number): number => {
			const input = opener.repeat(repeats)
			let best = Infinity

			for (let attempt = 0; 3 > attempt; attempt++) {
				const started = performance.now()
				expect(stripTags(input)).toBe(input)
				best = Math.min(best, performance.now() - started)
			}

			return best
		}

		for (const opener of ['<!--x', '<?php x']) {
			const base = timeStrip(opener, 4000)
			const scaled = timeStrip(opener, 16000)

			expect(scaled).toBeLessThan(Math.max(8 * base, 50))
		}
	})

	test('normalises whitespace', () => {
		expect(stripTags('  <div> spaced\n\nout </div> ')).toBe('spaced out')
		expect(stripTags('plain text')).toBe('plain text')
	})

	test('decodes HTML entities after stripping tags', () => {
		expect(stripTags('<p>A &amp; B</p>')).toBe('A & B')
		expect(stripTags('Fish&nbsp;&amp;&nbsp;chips')).toBe('Fish & chips')
		expect(stripTags('It&#039;s &quot;quoted&quot;')).toBe('It\'s "quoted"')
		expect(stripTags('one&hellip; <b>two</b>')).toBe('one… two')
	})

	test('decoded entities stay literal text, not markup', () => {
		expect(stripTags('&lt;script&gt;alert(1)&lt;/script&gt;')).toBe('<script>alert(1)</script>')
		expect(stripTags('<p>&lt;b&gt;not bold&lt;/b&gt;</p>')).toBe('<b>not bold</b>')
	})
})

test.describe('decodeEntities', () => {
	test('decodes named, decimal, and hexadecimal entities', () => {
		expect(decodeEntities('A &amp; B')).toBe('A & B')
		expect(decodeEntities('&#65;&#x42;&#X43;')).toBe('ABC')
		expect(decodeEntities('&copy; &trade; &rsquo;')).toBe('© ™ ’')
	})

	test('does not double-decode', () => {
		expect(decodeEntities('&amp;lt;')).toBe('&lt;')
		expect(decodeEntities('&amp;amp;')).toBe('&amp;')
	})

	test('leaves unknown and malformed sequences untouched', () => {
		expect(decodeEntities('&unknownentity;')).toBe('&unknownentity;')
		expect(decodeEntities('a && b')).toBe('a && b')
		expect(decodeEntities('&amp')).toBe('&amp')
		expect(decodeEntities('&#0;&#x110000;&#xd800;')).toBe('&#0;&#x110000;&#xd800;')
	})
})
