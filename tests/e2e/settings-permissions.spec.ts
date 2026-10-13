import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

test.describe('Settings permissions', () => {
	test('does not allow an Editor to access Settings', async ({ browser, page }) => {
		const username = `cs-e2e-editor-${Date.now()}`
		const password = 'e2e-editor-password'
		let userCreated = false

		await page.goto(URLS.WP_ADMIN)
		const baseUrl = new URL(page.url()).origin
		const editorContext = await browser.newContext()
		const editorPage = await editorContext.newPage()

		try {
			await wpCli([
				'user',
				'create',
				username,
				`${username}@example.test`,
				'--role=editor',
				`--user_pass=${password}`
			])
			userCreated = true
			await editorPage.goto(`${baseUrl}${URLS.WP_LOGIN}`)
			await editorPage.getByLabel('Username or Email Address').fill(username)
			await editorPage.getByRole('textbox', { name: 'Password' }).fill(password)
			await editorPage.getByRole('button', { name: 'Log In' }).click()

			await editorPage.goto(`${baseUrl}${URLS.SETTINGS_ADMIN}`)
			await expect(editorPage.getByText('Sorry, you are not allowed to access this page.')).toBeVisible()
		} finally {
			await editorContext.close()
			if (userCreated) {
				await wpCli(['user', 'delete', username, '--yes'])
			}
		}
	})
})
