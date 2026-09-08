import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const EXPECTED_H1_HEADING_COUNT = 1

const SCREENS: { name: string; url: string }[] = [
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN },
	{ name: 'Manage Snippets', url: URLS.SNIPPETS_ADMIN },
	{ name: 'Cloud Community', url: URLS.CLOUD_COMMUNITY_ADMIN },
	{ name: 'Cloud Library', url: URLS.CLOUD_LIBRARY_ADMIN },
	{ name: 'Blueprints', url: URLS.BLUEPRINTS_ADMIN },
	{ name: 'AI Agent', url: URLS.AI_AGENT_ADMIN },
	{ name: 'Insights', url: URLS.INSIGHTS_ADMIN },
	{ name: 'Import', url: URLS.IMPORT_ADMIN },
	{ name: 'Settings', url: URLS.SETTINGS_ADMIN },
	{ name: 'Welcome', url: URLS.WELCOME_ADMIN }
]

test.describe('Admin screen headings', () => {
	for (const screen of SCREENS) {
		test(`${screen.name} renders one h1 heading`, async ({ page }) => {
			await page.goto(screen.url)

			await expect(page.getByRole('heading', { level: 1 })).toHaveCount(EXPECTED_H1_HEADING_COUNT)
		})

		test(`${screen.name} does not skip heading levels`, async ({ page }) => {
			await page.goto(screen.url)

			const headingLevels = await page.getByRole('heading').evaluateAll(headings =>
				headings.map(heading => Number(heading.getAttribute('aria-level') ?? heading.tagName.slice(1)))
			)

			for (let index = 1; index < headingLevels.length; index++) {
				expect(
					headingLevels[index],
					`${screen.name} skips from h${headingLevels[index - 1]} to h${headingLevels[index]}`
				).toBeLessThanOrEqual(headingLevels[index - 1] + 1)
			}
		})
	}
})
