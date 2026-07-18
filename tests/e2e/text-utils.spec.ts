import { expect, test } from '@playwright/test'
import { stripTags } from '../../src/js/utils/text'

// Pure unit coverage for the text utilities used to render remote cloud
// descriptions; runs in the Playwright runner without a browser page.
test.describe('stripTags', () => {
	test('preserves separation between block elements', () => {
		expect(stripTags('<p>First</p><p>Second</p>')).toBe('First Second')
		expect(stripTags('Line one<br>Line two')).toBe('Line one Line two')
		expect(stripTags('<ul><li>One</li><li>Two</li></ul>')).toBe('One Two')
	})

	test('removes inline tags without adding separators', () => {
		expect(stripTags('Co<strong>de</strong> <em>snippets</em>')).toBe('Code snippets')
		expect(stripTags('<a href="https://example.com">link</a>')).toBe('link')
	})

	test('strips comments and PHP blocks', () => {
		expect(stripTags('A<!-- hidden --><?php evil(); ?>B')).toBe('AB')
	})

	test('normalises whitespace', () => {
		expect(stripTags('  <div> spaced\n\nout </div> ')).toBe('spaced out')
		expect(stripTags('plain text')).toBe('plain text')
	})
})
