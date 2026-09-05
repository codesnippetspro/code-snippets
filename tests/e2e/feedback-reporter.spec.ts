import { expect, test } from '@playwright/test'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

const SNIPPETS_URL = '/wp-admin/admin.php?page=snippets'
const SETTINGS_URL = '/wp-admin/admin.php?page=snippets-settings&section=advanced'
const LAUNCHER = '.code-snippets-feedback-launcher'
const PANEL = '.code-snippets-feedback-modal'

/**
 * Switch the reporter on or off through the plugin's own settings API, so the stored value
 * and its cache stay in step however the option is shaped on this install.
 */
const setReporterEnabled = async (enabled: boolean): Promise<void> => {
	await wpCli([
		'eval',
		`Code_Snippets\\Settings\\update_setting('general', 'enable_feedback_reporter', ${enabled ? 'true' : 'false'});`
	])
}

const openPanel = async (page: Page): Promise<void> => {
	await page.goto(SNIPPETS_URL)
	await page.locator(LAUNCHER).click()
	await expect(page.locator(PANEL)).toBeVisible()
}

test.describe('Feedback reporter', () => {
	test.afterAll(async () => {
		await setReporterEnabled(false)
	})

	test('stays hidden until the setting is switched on', async ({ page }) => {
		await setReporterEnabled(false)
		await page.goto(SNIPPETS_URL)

		await expect(page.locator(LAUNCHER)).toHaveCount(0)
	})

	test('is offered on the Advanced settings tab', async ({ page }) => {
		await page.goto(SETTINGS_URL)

		await expect(page.locator('input[name*="enable_feedback_reporter"]')).toHaveCount(1)
	})

	test('opens and closes the panel once enabled', async ({ page }) => {
		await setReporterEnabled(true)
		await openPanel(page)

		await expect(page.getByRole('dialog')).toContainText('Send feedback')

		await page.keyboard.press('Escape')
		await expect(page.locator(PANEL)).toHaveCount(0)
	})

	test('asks for a longer title before sending anything', async ({ page }) => {
		await setReporterEnabled(true)
		await openPanel(page)

		let requested = false
		await page.route('**/code-snippets/v1/feedback', route => {
			requested = true
			return route.abort()
		})

		await page.getByLabel('What kind of feedback is this?').selectOption('feedback')
		await page.getByRole('button', { name: 'Send report' }).click()

		await expect(page.locator('.code-snippets-feedback-message')).toBeVisible()
		expect(requested).toBe(false)
	})

	test('confirms a report the service accepted', async ({ page }) => {
		await setReporterEnabled(true)
		await openPanel(page)

		await page.route('**/code-snippets/v1/feedback', route => route.fulfill({
			status: 200,
			contentType: 'application/json',
			body: JSON.stringify({ sent: true, reference: 'CS-2026', url: '' })
		}))

		await page.getByLabel('What kind of feedback is this?').selectOption('feedback')
		await page.getByLabel('Title').fill('The Conditions tab icons are hard to tell apart')
		await page.getByLabel('What is on your mind?').fill('The new Conditions tab reads much more clearly than the old one.')
		await page.getByRole('button', { name: 'Send report' }).click()

		await expect(page.locator('.code-snippets-feedback-success')).toContainText('Report sent')
		await expect(page.locator('.code-snippets-feedback-success')).toContainText('CS-2026')
	})
})
