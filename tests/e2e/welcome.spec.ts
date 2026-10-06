import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

test.describe('What’s New screen', () => {
	test('renders the Resources and Updates screen', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		await expect(page.getByRole('heading', { level: 1, name: 'Resources and Updates' })).toBeVisible()
		await expect(page.locator('.code-snippets-welcome')).toBeVisible()
	})

	test('links the Latest changes section to the full changelog in a new tab', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		const changelog = page.locator('.code-snippets-changelog')
		const link = changelog.getByRole('link', { name: 'View changelog' })
		await expect(changelog.getByRole('heading', { name: 'Latest changes' })).toBeVisible()
		await expect(link).toHaveAttribute('href', 'https://wordpress.org/plugins/code-snippets/changelog')
		await expect(link).toHaveAttribute('target', '_blank')
	})

	test('protects the changelog link from sending referrer information', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		await expect(page.locator('.code-snippets-changelog').getByRole('link', { name: 'View changelog' }))
			.toHaveAttribute('rel', 'noreferrer')
	})

	test('provides toolbar links to Insights and Import', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		const mainLinks = page.getByRole('navigation', { name: 'Main links' })
		const insights = new URL(await mainLinks.getByRole('link', { name: 'Insights' }).getAttribute('href') ?? '')
		const importSnippets = new URL(await mainLinks.getByRole('link', { name: 'Import' }).getAttribute('href') ?? '')

		expect(`${insights.pathname}${insights.search}`).toBe(URLS.INSIGHTS_ADMIN)
		expect(`${importSnippets.pathname}${importSnippets.search}`).toBe(URLS.IMPORT_ADMIN)
	})

	test('gives the hero image a meaningful text alternative', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		await expect(page.locator('.code-snippets-hero').getByRole('img', { name: 'Latest news image' })).toBeVisible()
	})

	test('opens the hero article in a protected new tab', async ({ page }) => {
		await page.goto(URLS.WELCOME_ADMIN)

		const heroLink = page.locator('.code-snippets-hero').getByRole('link', { name: /Read more/ })
		await expect(heroLink).toHaveAttribute('target', '_blank')
		await expect(heroLink).toHaveAttribute('rel', 'noopener noreferrer')
	})
})
