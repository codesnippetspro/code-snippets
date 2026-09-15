import { expect, test } from '@playwright/test'

test.describe('Snippets REST API authentication', () => {
	test('rejects creating a snippet without a REST nonce', async ({ page }) => {
		await page.goto('/wp-admin/')

		const response = await page.evaluate(async () => {
			const request = await fetch('/?rest_route=/code-snippets/v1/snippets', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					name: 'Unauthorised E2E snippet',
					code: '// This must not be saved.',
					scope: 'global',
					network: false
				})
			})

			const body: unknown = JSON.parse(await request.text())

			return {
				status: request.status,
				body
			}
		})

		expect(response.status).toBe(401)
		expect(response.body).toMatchObject({ code: 'rest_forbidden' })
	})
})
