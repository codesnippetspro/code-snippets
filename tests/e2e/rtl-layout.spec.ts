import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

// The admin screens under a right-to-left locale. Mirroring is driven by the
// direction multiplier and logical properties; what can still go wrong is a
// control pushed past the page edge or a row that no longer fits, so every
// screen is checked for those rather than for how it looks.
const SCREENS: { name: string; url: string; ready: string }[] = [
	{ name: 'Add Snippet', url: URLS.ADD_SNIPPET_ADMIN, ready: '.cm-editor' },
	{ name: 'Manage Snippets', url: URLS.SNIPPETS_ADMIN, ready: '.snippet-view-toggle' },
	{ name: 'Import', url: URLS.IMPORT_ADMIN, ready: '#wpbody-content' },
	{ name: 'Settings (editing tab)', url: `${URLS.SETTINGS_ADMIN}&section=editing`, ready: '#settings-sections-tabs' },
	{ name: 'Settings (advanced tab)', url: `${URLS.SETTINGS_ADMIN}&section=advanced`, ready: '#settings-sections-tabs' }
]

interface LayoutReport {
	dir: string
	bodyRtl: boolean
	multiplier: string
	pageOverflow: number
	offscreen: string[]
}

const inspect = (): LayoutReport => {
	const viewportWidth = document.documentElement.clientWidth
	const selector = ['a', 'button', 'input', 'select'].map(tag => `#wpbody-content ${tag}`).join(', ')
	const controls = Array.from(document.querySelectorAll<HTMLElement>(selector))

	const offscreen = controls
		.filter(element => null !== element.offsetParent)
		.map(element => ({ element, rect: element.getBoundingClientRect() }))
		.filter(({ rect }) => 0 < rect.width && (0 > rect.left || rect.right > viewportWidth + 1))
		.map(({ element, rect }) => {
			const name = `${element.tagName.toLowerCase()}.${element.className.split(' ')[0]}`
			return `${name} left=${Math.round(rect.left)} right=${Math.round(rect.right)}`
		})

	return {
		dir: document.documentElement.getAttribute('dir') ?? 'ltr',
		bodyRtl: document.body.classList.contains('rtl'),
		multiplier: getComputedStyle(document.documentElement).getPropertyValue('--cs-direction-multiplier').trim(),
		pageOverflow: document.documentElement.scrollWidth - viewportWidth,
		offscreen
	}
}

test.describe('Right-to-left layout', () => {
	for (const { name, url, ready } of SCREENS) {
		test(`${name} mirrors without spilling off the page`, async ({ page }) => {
			await page.setViewportSize({ width: 1360, height: 900 })
			await page.goto(url)
			await page.waitForSelector(ready)

			const report = await page.evaluate(inspect)
			test.skip('rtl' !== report.dir, 'The RTL locale is not available on this site, so there is nothing to check.')

			expect(report.bodyRtl, 'WordPress marks the body as RTL').toBe(true)
			expect(report.multiplier, 'the direction multiplier is flipped').toBe('-1')
			expect(report.pageOverflow, 'the page does not scroll sideways').toBeLessThanOrEqual(0)
			expect(report.offscreen, 'no control sits outside the viewport').toEqual([])
		})
	}
})
