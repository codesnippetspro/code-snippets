import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'
import type { Page } from '@playwright/test'

const expectCurrentPanel = async (page: Page, label: string, panel: string) => {
	await expect(page.getByRole('button', { name: label })).toHaveAttribute('aria-current', 'page')
	await expect(page.getByRole('heading', { level: 1, name: label, exact: true })).toBeVisible()
	await expect(page.locator('.code-snippets-welcome-panel')).toHaveCount(1)
	await expect(page.locator('.code-snippets-welcome-panel')).toHaveClass(new RegExp(`\\b${panel}-panel\\b`))
}

const expectTabParam = (page: Page, tab: string) => {
	expect(new URL(page.url()).searchParams.getAll('tab')).toEqual([tab])
}

test('switches between one URL-addressable panel at a time', async ({ page }) => {
	await page.goto(URLS.WELCOME_ADMIN)

	const navigation = page.getByRole('navigation', { name: 'Welcome sections' })
	await expect(navigation.getByRole('button')).toHaveText(["What's new", 'Changelog', 'Resources'])
	await expect(page.locator('.code-snippets-welcome')).toHaveCSS('max-inline-size', '800px')
	expect(await navigation.locator('ul').evaluate(element => getComputedStyle(element).display)).toBe('flex')
	await expectCurrentPanel(page, "What's new", 'whats-new')

	await navigation.getByRole('button', { name: 'Changelog' }).click()
	await expect(page).toHaveURL(/[?&]tab=changelog(?:&|$)/)
	expectTabParam(page, 'changelog')
	await expectCurrentPanel(page, 'Changelog', 'changelog')

	await navigation.getByRole('button', { name: 'Resources' }).click()
	await expect(page).toHaveURL(/[?&]tab=resources(?:&|$)/)
	expectTabParam(page, 'resources')
	await expectCurrentPanel(page, 'Resources', 'resources')

	await navigation.getByRole('button', { name: "What's new" }).click()
	await expect(page).toHaveURL(/[?&]tab=whats-new(?:&|$)/)
	expectTabParam(page, 'whats-new')
	await expectCurrentPanel(page, "What's new", 'whats-new')
})

for (const { tab, label, panel } of [
	{ tab: 'whats-new', label: "What's new", panel: 'whats-new' },
	{ tab: 'changelog', label: 'Changelog', panel: 'changelog' },
	{ tab: 'resources', label: 'Resources', panel: 'resources' }
]) {
	test(`opens the ${tab} panel from its URL`, async ({ page }) => {
		await page.goto(`${URLS.WELCOME_ADMIN}&tab=${tab}`)

		await expectCurrentPanel(page, label, panel)
	})
}

test("falls back to What's new for an invalid tab", async ({ page }) => {
	await page.goto(`${URLS.WELCOME_ADMIN}&tab=unknown`)

	await expectCurrentPanel(page, "What's new", 'whats-new')
})

test('renders each changelog version as a card with consecutive heading levels', async ({ page }) => {
	await page.addInitScript(() => {
		const changelog = [{
			version: '4.1.0',
			date: '2026-11-03',
			entries: {
				Added: { core: ['Adds a test feature.'] },
				Fixed: { core: ['Fixes a test bug.'] }
			}
		}]
		let welcomeData = window.CODE_SNIPPETS_WELCOME

		Object.defineProperty(window, 'CODE_SNIPPETS_WELCOME', {
			configurable: true,
			get: () => welcomeData,
			set: (value: typeof window.CODE_SNIPPETS_WELCOME) => {
				welcomeData = { ...value, changelog }
			}
		})
	})
	await page.goto(`${URLS.WELCOME_ADMIN}&tab=changelog`)

	const changelog = page.locator('.code-snippets-changelog')
	const versions = changelog.locator(':scope > .code-snippets-changelog-version')
	const version = versions.first()
	const card = version.locator(':scope > .code-snippets-card')

	await expect(changelog.getByRole('heading', { name: 'Changelog' })).toHaveCount(0)
	await expect(changelog).toHaveCSS('border-top-style', 'none')
	await expect(changelog.locator(':scope > .code-snippets-changelog-entries')).toHaveCount(0)
	await expect(versions).toHaveCount(1)
	await expect(version.locator(':scope > h2')).toHaveText(/^Version /)
	await expect(card).toHaveCount(1)
	await expect(card).toHaveCSS('border-top-style', 'solid')
	await expect(card.getByRole('heading', { level: 3 }).first()).toBeVisible()
	await expect(version.getByRole('heading', { level: 4 })).toHaveCount(0)
})


test('renders news items as separate cards in two columns', async ({ page }) => {
	await page.addInitScript(() => {
		let welcomeData = window.CODE_SNIPPETS_WELCOME
		Object.defineProperty(window, 'CODE_SNIPPETS_WELCOME', {
			configurable: true,
			get: () => welcomeData,
			set: (value: typeof window.CODE_SNIPPETS_WELCOME) => {
				if (value) {
					welcomeData = {
						...value,
						hero: ['First', 'Second', 'Third'].map(name => ({
							name,
							follow_url: `https://example.com/${name}`,
							image_url: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
						}))
					}
				}
			}
		})
	})
	await page.goto(URLS.WELCOME_ADMIN)

	const cards = page.locator('.whats-new-panel > .code-snippets-card')
	await expect(cards).toHaveCount(3)
	await expect(cards.getByRole('heading', { level: 2 })).toHaveText(['First', 'Second', 'Third'])
	const boxes = await cards.evaluateAll(elements => elements.map(element => {
		const { x, y, width } = element.getBoundingClientRect()
		return { x, y, width }
	}))

	// One CSS pixel covers unavoidable browser sub-pixel rounding.
	expect(Math.abs(boxes[0].y - boxes[1].y)).toBeLessThanOrEqual(1)
	expect(Math.abs(boxes[0].width - boxes[1].width)).toBeLessThanOrEqual(1)
	expect(boxes[1].x).toBeGreaterThan(boxes[0].x + boxes[0].width)
	expect(Math.abs(boxes[0].x - boxes[2].x)).toBeLessThanOrEqual(1)
	expect(boxes[2].y).toBeGreaterThan(boxes[0].y)
})
