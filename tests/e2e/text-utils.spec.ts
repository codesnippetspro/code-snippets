import { expect, test } from '@playwright/test'
import { decodeEntities, stripTags } from '../../src/js/utils/text'
import type { Page } from '@playwright/test'

const stripTagsInPage = (page: Page, text: string): Promise<string> =>
	page.evaluate(stripTags, text)

test.describe('stripTags', () => {
	test('preserves separation between block elements', async ({ page }) => {
		expect(await stripTagsInPage(page, '<p>First</p><p>Second</p>')).toBe('First Second')
		expect(await stripTagsInPage(page, 'Line one<br>Line two')).toBe('Line one Line two')
		expect(await stripTagsInPage(page, '<ul><li>One</li><li>Two</li></ul>')).toBe('One Two')
	})

	test('preserves separation between blockquotes', async ({ page }) => {
		expect(await stripTagsInPage(page, '<blockquote>First</blockquote><blockquote>Second</blockquote>'))
			.toBe('First Second')
	})

	test('preserves separation between other block elements', async ({ page }) => {
		expect(await stripTagsInPage(page, '<address>First</address><address>Second</address>'))
			.toBe('First Second')
		expect(await stripTagsInPage(page, '<aside>First</aside><nav>Second</nav><main>Third</main>'))
			.toBe('First Second Third')
	})

	test('handles ">" inside quoted attribute values', async ({ page }) => {
		expect(await stripTagsInPage(page, '<p title="1 > 0">First</p><p>Second</p>')).toBe('First Second')
		expect(await stripTagsInPage(page, "<span title='a > b'>inline</span>")).toBe('inline')
	})

	test('handles malformed tags with unbalanced quotes', async ({ page }) => {
		expect(await stripTagsInPage(page, '<p title="unterminated>Visible</p>')).toBe('')
		expect(await stripTagsInPage(page, "<p title='unterminated>Visible</p>")).toBe('')
		expect(await stripTagsInPage(page, '<div class="a>One</div><p>Two</p>')).toBe('')
		expect(await stripTagsInPage(page, '<span data-x="broken>inline</span> text')).toBe('')
	})

	test('uses native parser semantics for malformed comparison text', async ({ page }) => {
		expect(await stripTagsInPage(page, 'x<y')).toBe('x')
		expect(await stripTagsInPage(page, 'x<y and z')).toBe('x')
		expect(await stripTagsInPage(page, 'x<p')).toBe('x')
	})

	test('strips unterminated-quote tag remnant at end of input', async ({ page }) => {
		expect(await stripTagsInPage(page, 'Visible <p title="broken')).toBe('Visible')
		expect(await stripTagsInPage(page, "Visible <span data-x='broken")).toBe('Visible')
		expect(await stripTagsInPage(page, 'keep <x till <p title="broken')).toBe('keep')
		expect(await stripTagsInPage(page, 'a <x"m<z"')).toBe('a')
		expect(await stripTagsInPage(page, 'a <x "q" ok')).toBe('a')
	})

	test('strips block remnants before generic remnants', async ({ page }) => {
		expect(await stripTagsInPage(page, '<span <div x> y')).toBe('y')
	})

	test('handles long malformed comparison text within a sanity bound', async ({ page }) => {
		const input = 'if (a<b && c<d) run(); '.repeat(16000)
		const started = performance.now()
		expect(await stripTagsInPage(page, input)).toBe('if (a')
		expect(performance.now() - started).toBeLessThan(5000)
	})

	test('removes inline tags without adding separators', async ({ page }) => {
		expect(await stripTagsInPage(page, 'Co<strong>de</strong> <em>snippets</em>')).toBe('Code snippets')
		expect(await stripTagsInPage(page, '<a href="https://example.com">link</a>')).toBe('link')
	})

	test('keeps inline markup inside a word joined', async ({ page }) => {
		expect(await stripTagsInPage(page, 'in<strong>line</strong>')).toBe('inline')
	})

	test('strips comments and PHP blocks', async ({ page }) => {
		expect(await stripTagsInPage(page, 'A<!-- hidden --><?php evil(); ?>B')).toBe('AB')
		expect(await stripTagsInPage(page, '<!--a--><!--b-->C<??><?PHP d?>')).toBe('C')
		expect(await stripTagsInPage(page, 'A<!-- unterminated')).toBe('A')
		expect(await stripTagsInPage(page, 'B<?php unterminated')).toBe('B')
	})

	test('does not leak comment content', async ({ page }) => {
		expect(await stripTagsInPage(page, '<!--')).toBe('')
		expect(await stripTagsInPage(page, 'a<!-- b -->c')).toBe('ac')
	})

	test('does not include script or style content', async ({ page }) => {
		expect(await stripTagsInPage(
			page,
			'<p>Visible</p><script>alert("hidden")</script><style>.hidden { display: none; }</style><p>Text</p>'
		)).toBe('Visible Text')
	})

	test('handles repeated unmatched comment and PHP openers within a sanity bound', async ({ page }) => {
		const timeStrip = async (opener: string, repeats: number): Promise<number> => {
			const input = opener.repeat(repeats)
			const started = performance.now()
			expect(await stripTagsInPage(page, input)).toBe('')

			return performance.now() - started
		}

		for (const opener of ['<!--x', '<?php x']) {
			expect(await timeStrip(opener, 16000)).toBeLessThan(5000)
		}
	})

	test('normalises whitespace', async ({ page }) => {
		expect(await stripTagsInPage(page, '  <div> spaced\n\nout </div> ')).toBe('spaced out')
		expect(await stripTagsInPage(page, 'plain text')).toBe('plain text')
	})

	test('decodes HTML entities after stripping tags', async ({ page }) => {
		expect(await stripTagsInPage(page, '<p>A &amp; B</p>')).toBe('A & B')
		expect(await stripTagsInPage(page, 'Fish&nbsp;&amp;&nbsp;chips')).toBe('Fish & chips')
		expect(await stripTagsInPage(page, 'It&#039;s &quot;quoted&quot;')).toBe('It\'s "quoted"')
		expect(await stripTagsInPage(page, 'one&hellip; <b>two</b>')).toBe('one… two')
	})

	test('decoded entities stay literal text, not markup', async ({ page }) => {
		expect(await stripTagsInPage(page, '&lt;script&gt;alert(1)&lt;/script&gt;'))
			.toBe('<script>alert(1)</script>')
		expect(await stripTagsInPage(page, '<p>&lt;b&gt;not bold&lt;/b&gt;</p>')).toBe('<b>not bold</b>')
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
