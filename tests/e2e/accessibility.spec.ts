import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'
import { SELECTORS, TIMEOUTS, URLS } from './helpers/constants'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import type { Page } from '@playwright/test'

const A11Y_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']

const A11Y_SNIPPET_PREFIX = 'E2E A11y Test'

const runAxe = (page: Page) =>
	new AxeBuilder({ page })
		.withTags(A11Y_TAGS)
		// Skip rules that target areas owned by WordPress core admin chrome
		// rather than this plugin (skip-link target, default landmarks).
		.disableRules(['region', 'skip-link'])
		.analyze()

test.describe('Accessibility - Automated tests (axe-core, WCAG 2.1 AA)', () => {
	test('Manage snippets list has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Community cloud screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.COMMUNITY_CLOUD_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Add new snippet form has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Import snippets screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Snippets settings screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})

	test('Welcome screen has no detectable axe violations', async ({ page }) => {
		await page.goto(URLS.WELCOME_SCREEN_ADMIN)
		await page.waitForLoadState('networkidle')

		const results = await runAxe(page)
		expect(results.violations, JSON.stringify(results.violations, null, 2)).toEqual([])
	})
})

test.describe('Accessibility — Manage Snippets Screen', () => {
	let snippetName: string

	test.beforeAll(async () => {
		snippetName = SnippetsTestHelper.makeUniqueSnippetName(A11Y_SNIPPET_PREFIX)
		await SnippetsTestHelper.createSnippetViaCli({ name: snippetName, active: true })
	})

	test.afterAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(A11Y_SNIPPET_PREFIX)
	})

	test('Snippets table with sortable column use buttons with aria-sort', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const nameSortButton = page.locator('th.column-name .list-table-sort-button').first()
		await expect(nameSortButton).toBeVisible()

		const nameHeader = page.locator('th.column-name').first()
		const ariaSort = await nameHeader.getAttribute('aria-sort')
		expect(['ascending', 'descending', null].includes(ariaSort)).toBe(true)
	})

	test('Activation toggle has role="switch" and aria-checked', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const toggle = page.locator(`${SELECTORS.SNIPPET_ROW} input[role="switch"]`).first()
		await expect(toggle).toBeVisible()
		await expect(toggle).toHaveAttribute('role', 'switch')

		const ariaChecked = await toggle.getAttribute('aria-checked')
		expect(['true', 'false'].includes(ariaChecked ?? '')).toBe(true)
	})

	test('Activation toggle has an associated screen-reader label', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const toggle = page.locator(`${SELECTORS.SNIPPET_ROW} input[role="switch"]`).first()
		const toggleId = await toggle.getAttribute('id')
		expect(toggleId).toBeTruthy()

		const label = page.locator(`label[for="${toggleId}"]`)
		await expect(label).toHaveClass(/screen-reader-text/)
		await expect(label).not.toBeEmpty()
	})

	test('Bulk action select has an accessible label', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const bulkSelect = page.locator('#bulk-action-selector-top')
		await expect(bulkSelect).toBeVisible()

		const label = page.locator('label[for="bulk-action-selector-top"]')
		await expect(label).toHaveClass(/screen-reader-text/)
		await expect(label).not.toBeEmpty()
	})

	test('Search input has a screen-reader label', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const searchInput = page.locator(SELECTORS.SNIPPET_SEARCH_INPUT)
		await expect(searchInput).toBeVisible()

		const label = page.locator('label[for="snippets_search"]')
		await expect(label).toHaveClass(/screen-reader-text/)
		await expect(label).not.toBeEmpty()
	})

	test('Search region has an aria-label', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const searchRegion = page.locator('search[aria-label]')
		await expect(searchRegion).toBeVisible()
		await expect(searchRegion).toHaveAttribute('aria-label', /\S+/)
	})

	test('Type filter tabs use aria-current="page" for active tab', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const typeNav = page.locator('nav.snippet-type-tabs')
		await expect(typeNav).toHaveAttribute('aria-label', /\S+/)

		const activeTab = typeNav.locator('a[aria-current="page"]')
		await expect(activeTab).toHaveCount(1)
	})

	test('Status filter links use aria-current="page" for active status', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const activeStatus = page.locator('.subsubsub a.current[aria-current="page"]')
		await expect(activeStatus).toHaveCount(1)
	})

	test('Live region announces snippet count', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const liveRegion = page.locator('[role="status"][aria-live="polite"]')
		await expect(liveRegion).toBeAttached()
		await expect(liveRegion).toHaveText(/\d+ snippets? found/)
	})
})

test.describe('Accessibility — Add/Edit Snippet Screen', () => {
	test('Title input has an associated label', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		const label = page.locator('label[for="title"]')
		await expect(label).toBeAttached()
		await expect(label).not.toBeEmpty()
	})

	test('Code editor region has role="application" and aria-label', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		const editorRegion = page.locator('.snippet-editor[role="application"]')
		await expect(editorRegion).toBeVisible()
		await expect(editorRegion).toHaveAttribute('aria-label', /\S+/)
	})

	test('Code editor has escape-to-tab-out instructions as screen-reader text', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		const editorRegion = page.locator('.snippet-editor[role="application"]')
		const describedById = await editorRegion.getAttribute('aria-describedby')
		expect(describedById).toBeTruthy()

		const instructions = page.locator(`#${describedById}`)
		await expect(instructions).toBeAttached()
		await expect(instructions).toHaveClass(/screen-reader-text/)
		await expect(instructions).toHaveText(/Escape/)
	})

	test('Snippet content label associates with the code field', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		await expect(page.locator('label[for="snippet-code"]')).toBeVisible()
		await expect(page.locator('#snippet-code')).toBeVisible()
	})

	test('Snippet type selector is keyboard-operable', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		const typeSelect = page.locator('#snippet-type-select-input')
		await typeSelect.focus()
		await expect(typeSelect).toBeFocused()

		await page.keyboard.press('Space')
		const menu = page.locator('.code-snippets-select-type .code-snippets-select__menu')
		await expect(menu).toBeVisible()
	})

	test('Save button has an accessible name', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		const saveButton = page.locator('role=button[name="Save Snippet"]')
		await expect(saveButton).toBeVisible()
		await expect(saveButton).toBeEnabled()
	})

	test('Save and Activate button has an accessible name', async ({ page }) => {
		await page.goto(URLS.ADD_SNIPPET_ADMIN)
		await page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })

		const saveActivateButton = page.locator('role=button[name="Save and Activate"]')
		await expect(saveActivateButton).toBeVisible()
		await expect(saveActivateButton).toBeEnabled()
	})
})

test.describe('Accessibility — Import Screen', () => {
	test('Import source tabs are keyboard-navigable buttons', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForSelector('.import-snippets-menu', { timeout: TIMEOUTS.DEFAULT })

		const nav = page.locator('nav[aria-label="Import sources"]')
		await expect(nav).toBeVisible()

		const tabs = nav.locator('button.nav-tab')
		const tabCount = await tabs.count()
		expect(tabCount).toBeGreaterThanOrEqual(2)

		await tabs.first().focus()
		await expect(tabs.first()).toBeFocused()
	})

	test('Active import tab has the nav-tab-active class', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForSelector('.import-snippets-menu', { timeout: TIMEOUTS.DEFAULT })

		const activeTab = page.locator('nav[aria-label="Import sources"] button.nav-tab-active')
		await expect(activeTab).toHaveCount(1)
	})

	test('Snippets import screen has a keyboard-focusable upload file control', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForSelector('.import-snippets-menu', { timeout: TIMEOUTS.DEFAULT })

		const fileInput = page.locator('.upload-drop-zone-file-input')
		await fileInput.focus()

		await expect(fileInput).toBeFocused()
	})

	test('Upload drop zone label is associated with the file input', async ({ page }) => {
		await page.goto(URLS.IMPORT_SNIPPETS_ADMIN)
		await page.waitForSelector('.import-snippets-menu', { timeout: TIMEOUTS.DEFAULT })

		const dropZoneLabel = page.locator('label.upload-drop-zone')
		await expect(dropZoneLabel).toBeVisible()

		const forAttribute = await dropZoneLabel.getAttribute('for')
		expect(forAttribute).toBeTruthy()

		const fileInput = page.locator(`#${forAttribute}`)
		await expect(fileInput).toHaveAttribute('type', 'file')
	})
})

test.describe('Accessibility — Settings Screen', () => {
	test('Settings tabs are keyboard-navigable', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)
		await page.waitForLoadState('networkidle')

		const tabs = page.locator('.nav-tab-wrapper .nav-tab')
		const tabCount = await tabs.count()
		expect(tabCount).toBeGreaterThanOrEqual(2)

		await tabs.first().focus()
		await expect(tabs.first()).toBeFocused()

		await page.keyboard.press('Tab')
		await expect(tabs.nth(1)).toBeFocused()
	})

	test('Active settings tab is visually indicated', async ({ page }) => {
		await page.goto(URLS.SETTINGS_ADMIN)
		await page.waitForLoadState('networkidle')

		const activeTab = page.locator('.nav-tab-wrapper .nav-tab-active')
		await expect(activeTab).toHaveCount(1)
	})
})

test.describe('Accessibility — Modal Focus Management', () => {
	let snippetName: string

	test.beforeAll(async () => {
		snippetName = SnippetsTestHelper.makeUniqueSnippetName(A11Y_SNIPPET_PREFIX)
		await SnippetsTestHelper.createSnippetViaCli({ name: snippetName, active: true })
	})

	test.afterAll(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(A11Y_SNIPPET_PREFIX)
	})

	test('Delete confirmation dialog receives focus when opened', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const snippetRow = page.locator(
			`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`
		).first()
		await snippetRow.hover()

		const trashButton = snippetRow.locator('.row-actions button:has-text("Trash")')
		await trashButton.click()

		const modal = page.locator('.components-modal__frame')
		await expect(modal).toBeVisible()

		const focusedElement = page.locator(':focus')
		const isInsideModal = await focusedElement.evaluate(
			(el, modalSelector) => !!el.closest(modalSelector),
			'.components-modal__frame'
		)
		expect(isInsideModal).toBe(true)
	})

	test('Delete confirmation dialog closes on Escape', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const snippetRow = page.locator(
			`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`
		).first()
		await snippetRow.hover()

		const trashButton = snippetRow.locator('.row-actions button:has-text("Trash")')
		await trashButton.click()

		const modal = page.locator('.components-modal__frame')
		await expect(modal).toBeVisible()

		await page.keyboard.press('Escape')
		await expect(modal).not.toBeVisible()
	})
})

test.describe('Accessibility — Toolbar Navigation', () => {
	test('Toolbar renders two nav landmarks with distinct aria-label values', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const toolbar = page.locator('.code-snippets-toolbar')
		await expect(toolbar).toBeVisible()

		const navs = toolbar.locator('nav[aria-label]')
		const count = await navs.count()
		expect(count).toBe(2)

		const firstLabel = await navs.nth(0).getAttribute('aria-label')
		const secondLabel = await navs.nth(1).getAttribute('aria-label')

		expect(firstLabel).toBeTruthy()
		expect(secondLabel).toBeTruthy()
		expect(firstLabel).not.toEqual(secondLabel)
	})

	test('Toolbar nav links with external targets have rel="noopener noreferrer"', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })

		const externalLinks = page.locator('.code-snippets-toolbar a[target="_blank"]')
		const linkCount = await externalLinks.count()

		for (let i = 0; i < linkCount; i++) {
			const rel = await externalLinks.nth(i).getAttribute('rel')
			expect(rel).toContain('noopener')
			expect(rel).toContain('noreferrer')
		}
	})
})
