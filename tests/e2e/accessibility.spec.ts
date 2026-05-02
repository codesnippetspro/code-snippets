import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const A11Y_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']

const runAxe = (page: Page) =>
	new AxeBuilder({ page })
		.withTags(A11Y_TAGS)
		// Skip rules that target areas owned by WordPress core admin chrome
		// rather than this plugin (skip-link target, default landmarks).
		.disableRules(['region', 'skip-link'])
		.analyze()

test.describe('Accessibility (axe-core, WCAG 2.1 AA)', () => {
	test('Welcome screen has no detectable axe violations', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=code-snippets-welcome')
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Manage snippets list has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Add new snippet form has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Import snippets screen has no detectable axe violations', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=import-code-snippets')
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})
})
