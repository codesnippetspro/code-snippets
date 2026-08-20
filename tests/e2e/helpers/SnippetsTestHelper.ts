import { expect } from '@playwright/test'
import { BUTTONS, MESSAGES, SELECTORS, SNIPPET_LOCATIONS, SNIPPET_TYPES, TIMEOUTS, URLS } from './constants'
import { wpCli } from './wpCli'
import type { Page } from '@playwright/test'

const escapeRegExp = (value: string): string => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

const META_OR_CONTROL_A = 'darwin' === process.platform ? 'Meta+A' : 'Control+A'

const RANDOM_RADIX = 36
const RANDOM_SLICE_START = 2
const RANDOM_SLICE_END = 7
const CLICK_RETRIES = 3
const SAVE_CONFIRM_RETRIES = 3
const AT_LEAST_ONE = 1

const getErrorMessage = (error: unknown): string => {
	if (error instanceof Error) {
		return error.message
	}
	return String(error)
}

export interface SnippetFormOptions {
	name: string
	code: string
	type?: keyof typeof SNIPPET_TYPES
	location?: keyof typeof SNIPPET_LOCATIONS
}

export interface CreateSnippetCliOptions {
	name: string;
	active: boolean;
	conditionId?: number;
	tags?: readonly string[];
	type?: 'php' | 'html' | 'css' | 'js' | 'cond';
}

export const DEFAULT_E2E_SNIPPET_BASE_NAME = 'E2E Snippet Test'

export class SnippetsTestHelper {
	constructor(private page: Page) { }

	static makeUniqueSnippetName(baseName: string = DEFAULT_E2E_SNIPPET_BASE_NAME): string {
		return `${baseName} ${Date.now()}-${Math.random().toString(RANDOM_RADIX).slice(RANDOM_SLICE_START, RANDOM_SLICE_END)}`
	}

	static async setAdminBarQuickNavSettings(options: { enabled: boolean; perPage: number }): Promise<void> {
		const php = `
			\\Code_Snippets\\Settings\\update_setting('general', 'enable_admin_bar', ${options.enabled ? 'true' : 'false'});
			\\Code_Snippets\\Settings\\update_setting('general', 'admin_bar_snippet_limit', ${options.perPage});
		`

		await wpCli(['eval', php])
	}

	static async setSnippetsPerPage(perPage: number): Promise<void> {
		const php = `
			$user = get_user_by('login', 'admin');
			$user_id = $user ? $user->ID : 1;
			update_user_option($user_id, 'snippets_per_page', ${perPage});
		`

		await wpCli(['eval', php])
	}

	static async resetSnippetsPerPage(): Promise<void> {
		const php = `
			$user = get_user_by('login', 'admin');
			$user_id = $user ? $user->ID : 1;
			delete_user_option($user_id, 'snippets_per_page');
		`

		await wpCli(['eval', php])
	}

	static async createSnippetViaCli(options: CreateSnippetCliOptions): Promise<number> {
		const type = options.type ?? 'php'
		let scope = 'global'
		switch (type) {
			case 'html':
				scope = 'content'
				break
			case 'css':
				scope = 'site-css'
				break
			case 'js':
				scope = 'site-footer-js'
				break
			case 'cond':
				scope = 'condition'
				break
		}

		const code = 'html' === type ? `<p>${options.name}</p>\n` : `// ${options.name}\n`

		const php = `
			$snippet = new \\Code_Snippets\\Model\\Snippet([
				'name' => ${JSON.stringify(options.name)},
				'desc' => '',
				'code' => ${JSON.stringify(code)},
				'scope' => ${JSON.stringify(scope)},
				'active' => ${options.active ? 'true' : 'false'},
				'condition_id' => ${options.conditionId ?? 0},
				'tags' => ${JSON.stringify(options.tags ?? [])},
			]);
			$snippet = \\Code_Snippets\\save_snippet($snippet);
			echo $snippet->id;
		`

		return Number(await wpCli(['eval', php]))
	}

	static async cleanupSnippetsByPrefix(prefix: string): Promise<void> {
		const php = `
			global $wpdb;
			$prefix = ${JSON.stringify(prefix)};
			$like = $wpdb->esc_like( $prefix ) . '%';
			$targets = [ [ false, \\Code_Snippets\\code_snippets()->db->get_table_name( false ) ] ];

			if ( is_multisite() ) {
				$targets[] = [ true, \\Code_Snippets\\code_snippets()->db->get_table_name( true ) ];
			}

			foreach ( $targets as $target ) {
				[ $network, $table ] = $target;
				$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE name LIKE %s", $like ) );
				foreach ( $ids as $id ) {
					\\Code_Snippets\\delete_snippet( intval( $id ), (bool) $network );
				}
			}
		`

		await wpCli(['eval', php])
	}

	static async isProLicensed(): Promise<boolean> {
		try {
			const output = await wpCli(['snippet', 'license-status', '--format=json'])
			const status = <{ is_licensed?: string }>JSON.parse(output)
			return 'Yes' === status.is_licensed
		} catch {
			return false
		}
	}

	private async clickButton(name: RegExp, options: { force?: boolean } = {}): Promise<void> {
		const force = options.force ?? true

		for (let attempt = 0; CLICK_RETRIES > attempt; attempt++) {
			try {
				const buttons = this.page.getByRole('button', { name })
				const count = await buttons.count()

				for (let i = 0; i < Math.max(count, AT_LEAST_ONE); i++) {
					const candidate = 0 === count ? buttons.first() : buttons.nth(i)
					const visible = await candidate.isVisible().catch(() => false)
					if (!visible && 0 !== count) {
						continue
					}
					await candidate.click({ timeout: TIMEOUTS.DEFAULT, force })
					return
				}

				// Fallback: attempt to click the first match even if not considered "visible".
				await buttons.first().click({ timeout: TIMEOUTS.DEFAULT, force })
				return
			} catch (error: unknown) {
				const message = getErrorMessage(error)
				if (!message.includes('not attached to the DOM') && !message.includes('Target closed')) {
					throw error
				}
			}
		}

		throw new Error(`Failed to click button: ${name}`)
	}

	private async setCodeMirrorValue(value: string): Promise<void> {
		const didSetViaApi = await this.page
			.evaluate(newValue => {
				const wrapper = document.querySelector<HTMLElement>('.CodeMirror')
				const cm = (<{ CodeMirror?: unknown }><unknown>wrapper).CodeMirror

				if (!cm || 'object' !== typeof cm) {
					return false
				}

				const { setValue, refresh } = <{ setValue?: unknown; refresh?: unknown }>cm

				if ('function' !== typeof setValue) {
					return false
				}

				setValue.call(cm, newValue)

				if ('function' === typeof refresh) {
					refresh.call(cm)
				}

				return true
			}, value)
			.catch(() => false)

		if (didSetViaApi) {
			return
		}

		const editor = this.page.locator('.CodeMirror').first()
		await expect(editor).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await editor.click()
		await this.page.keyboard.press(META_OR_CONTROL_A)
		await this.page.keyboard.type(value)
	}

	private async selectSnippetLocation(location: keyof typeof SNIPPET_LOCATIONS): Promise<void> {
		const locationLabel = SNIPPET_LOCATIONS[location]

		const locationSelect = this.page.locator(SELECTORS.LOCATION_SELECT)
		await expect(locationSelect).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await locationSelect.click()

		const listbox = this.page.getByRole('listbox').first()
		await expect(listbox).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
		await listbox
			.getByRole('option', { name: new RegExp(escapeRegExp(locationLabel), 'i') })
			.click()

		await expect(this.page.locator(SELECTORS.LOCATION_SELECT)).toContainText(locationLabel)
	}

	/**
	 * Navigate to the Code Snippets admin page.
	 *
	 * The snippet view preference persists server-side per user, so an earlier
	 * card-view test can leave the manage page rendering cards. Callers of this
	 * helper expect the table, so switch back whenever cards are active.
	 */
	async navigateToSnippetsAdmin(): Promise<void> {
		await this.page.goto(URLS.SNIPPETS_ADMIN)

		const viewToggle = this.page.getByRole('button', { name: 'Table view' })
		await viewToggle.waitFor({ timeout: TIMEOUTS.DEFAULT })

		if (0 === await this.page.locator(SELECTORS.SNIPPETS_TABLE).count()) {
			await viewToggle.click()
		}

		await this.page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })
	}

	/**
   * Filter the snippets table to a specific snippet name.
   */
	async filterSnippetsByName(snippetName: string): Promise<void> {
		await this.page.fill(SELECTORS.SNIPPET_SEARCH_INPUT, snippetName)
	}

	/**
   * Navigate to frontend
   */
	async navigateToFrontend(): Promise<void> {
		await this.page.goto(URLS.FRONTEND)
		await this.page.waitForSelector('body', { timeout: TIMEOUTS.DEFAULT })
	}

	/**
	 * Click the "Add New" button to start creating a snippet
	 */
	async clickAddNewSnippet(): Promise<void> {
		await this.page.goto(URLS.ADD_SNIPPET_ADMIN)
		await this.page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })
	}

	/**
	 * Fill the snippet form with the provided options
	 */
	async fillSnippetForm(options: SnippetFormOptions): Promise<void> {
		await this.page.waitForSelector(SELECTORS.TITLE_INPUT)
		await this.page.fill(SELECTORS.TITLE_INPUT, options.name)

		if (options.type && 'PHP' !== options.type) {
			const snippetTypeSelect = this.page.locator(SELECTORS.SNIPPET_TYPE_SELECT)
			await snippetTypeSelect.click()

			// React Select renders options in a listbox; scope the click to options to avoid matching
			// other UI strings like "Skip to main content".
			const listbox = this.page.getByRole('listbox')
			const optionLabel = SNIPPET_TYPES[options.type]

			await listbox.getByRole('option', { name: new RegExp(escapeRegExp(optionLabel), 'i') }).click()
		}

		await this.page.waitForSelector(SELECTORS.CODE_MIRROR_TEXTAREA)
		await this.setCodeMirrorValue(options.code)

		if (options.location) {
			await this.selectSnippetLocation(options.location)
		}
	}

	/**
	 * Save the snippet with the specified action
	 */
	async saveSnippet(action: 'save' | 'save_and_activate' | 'save_and_deactivate' = 'save'): Promise<void> {
		if ('save_and_activate' === action) {
			const activateButton = this.page.locator(BUTTONS.SAVE_AND_ACTIVATE).first()
			if (await activateButton.isVisible().catch(() => false)) {
				await this.clickSaveAndConfirm(/^Save and Activate$/i)
				return
			}

			// Fallback: toggle status to active and save.
			const inactiveToggle = this.page.getByRole('checkbox', { name: /^Inactive$/ }).first()
			if (await inactiveToggle.isVisible().catch(() => false)) {
				await inactiveToggle.click({ timeout: TIMEOUTS.DEFAULT, force: true })
			}
			await this.clickSaveAndConfirm(/^Save Snippet$/i)
			return
		}

		if ('save_and_deactivate' === action) {
			// New UI deactivates via Status toggle + "Save Snippet".
			const activeToggle = this.page.getByRole('checkbox', { name: /^Active$/ }).first()
			if (await activeToggle.isVisible().catch(() => false)) {
				await activeToggle.click({ timeout: TIMEOUTS.DEFAULT, force: true })
			} else {
				const statusToggle = this.page.getByRole('checkbox', { name: /Active|Inactive/ }).first()
				if (await statusToggle.isVisible().catch(() => false)) {
					await statusToggle.click({ timeout: TIMEOUTS.DEFAULT, force: true })
				}
			}
			await this.clickSaveAndConfirm(/^Save Snippet$/i)
			return
		}

		await this.clickSaveAndConfirm(/^Save Snippet$/i)
	}

	private async clickSaveAndConfirm(name: RegExp): Promise<void> {
		for (let attempt = 0; SAVE_CONFIRM_RETRIES > attempt; attempt++) {
			await this.clickButton(name)

			const settled = await this.page.locator(SELECTORS.SAVE_SETTLED_NOTICE).first()
				.waitFor({ state: 'visible', timeout: TIMEOUTS.DEFAULT })
				.then(() => true)
				.catch(() => false)

			if (settled) {
				return
			}

			const buttonStillPresent = await this.page.getByRole('button', { name }).first()
				.isVisible()
				.catch(() => false)

			if (!buttonStillPresent) {
				return
			}
		}
	}

	/**
   * Expect a success message with the specified text
   */
	async expectSuccessMessage(expectedMessage: string | RegExp): Promise<void> {
		await expect(this.page.locator(SELECTORS.SUCCESS_MESSAGE)).toContainText(expectedMessage)
	}

	/**
	 * Open an existing snippet by name
	 */
	async openSnippet(snippetName: string): Promise<void> {
		await this.page.goto(URLS.SNIPPETS_ADMIN)
		await this.page.waitForSelector(SELECTORS.SNIPPETS_TABLE, { timeout: TIMEOUTS.DEFAULT })
		await this.filterSnippetsByName(snippetName)

		const row = this.page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
		await expect(row).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		await row.locator(SELECTORS.SNIPPET_NAME_LINK).click()
		await this.page.waitForSelector(SELECTORS.TITLE_INPUT, { timeout: TIMEOUTS.DEFAULT })
	}

	/**
	 * Delete a snippet (assumes you're already on the snippet edit page)
	 */
	async deleteSnippet(): Promise<void> {
		await this.page.locator(BUTTONS.DELETE).first().click()

		// Some UIs show a React dialog, others navigate immediately.
		const dialog = this.page.locator('[role="dialog"]').filter({ hasText: /Are you sure\?/i })
		const dialogVisible = await dialog
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SHORT })
			.then(() => true)
			.catch(() => false)

		if (dialogVisible) {
			await Promise.all([
				this.page.waitForURL(/page=snippets/, { timeout: TIMEOUTS.DEFAULT }),
				dialog.locator('button:has-text("Trash"), button:has-text("Delete")').first().click()
			])
		} else {
			await this.page.waitForURL(/page=snippets/, { timeout: TIMEOUTS.DEFAULT })
		}

		await expect(this.page).toHaveURL(/page=snippets/)
		await expect(this.page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
	}

	/**
   * Delete a snippet by name from the snippets list page.
   */
	async deleteSnippetFromList(snippetName: string): Promise<void> {
		await this.navigateToSnippetsAdmin()
		await this.filterSnippetsByName(snippetName)

		const row = this.page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		const rowVisible = await row
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SHORT })
			.then(() => true)
			.catch(() => false)

		if (!rowVisible) {
			return
		}

		await row.locator(SELECTORS.DELETE_ACTION).first().click()

		// After trashing, it may still show depending on current filter; navigate to trash to ensure it's gone.
		const trashedLink = this.page.locator('a[href*="status=trashed"]').first()
		const trashedLinkVisible = await trashedLink
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SHORT })
			.then(() => true)
			.catch(() => false)

		if (!trashedLinkVisible) {
			return
		}

		await trashedLink.click()
		await expect(this.page).toHaveURL(/status=trashed/, { timeout: TIMEOUTS.DEFAULT })

		const trashedRow = this.page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${snippetName}"))`)
			.first()

		const trashedVisible = await trashedRow
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SHORT })
			.then(() => true)
			.catch(() => false)

		if (!trashedVisible) {
			return
		}

		await trashedRow.locator('button:has-text("Delete Permanently")').click()

		const dialog = this.page.locator('[role="dialog"]').filter({ hasText: /Are you sure\?/i })
		const dialogVisible = await dialog
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SHORT })
			.then(() => true)
			.catch(() => false)

		if (dialogVisible) {
			await dialog.locator('button:has-text("Delete")').click()
		}

		await expect(this.page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible({ timeout: TIMEOUTS.DEFAULT })
	}

	/**
   * Clean up all snippets by name (navigate to admin, find snippets, delete them)
   */
	async cleanupSnippet(snippetName: string): Promise<void> {
		// Prefer WP-CLI cleanup for speed and determinism. Use plugin operations so
		// file-based execution stays in sync (flat files update via hooks).
		try {
			await SnippetsTestHelper.cleanupSnippetsByPrefix(snippetName)
		} catch {
			// Cleanup should never fail the test run.
		}
	}

	/**
	 * Verify the current URL contains the snippets admin page
	 */
	async expectToBeOnSnippetsAdminPage(): Promise<void> {
		const currentUrl = this.page.url()
		expect(currentUrl).toContain('page=snippets')
		await expect(this.page.locator(SELECTORS.SNIPPETS_TABLE)).toBeVisible()
	}

	/**
	 * Expect an element to have a specific count
	 */
	async expectElementCount(selector: string, expectedCount: number): Promise<void> {
		const count = await this.page.locator(selector).count()
		expect(count).toBe(expectedCount)
	}

	/**
	 * Expect text to be visible on the page
	 */
	async expectTextVisible(text: string): Promise<void> {
		await expect(this.page.locator(`text=${text}`)).toBeVisible()
	}
	async expectTextBeforeElement(text: string, selector: string): Promise<void> {
		const precedes = await this.page.evaluate(
			({ text, selector }) => {
				const node = document.evaluate(
					`//p[contains(text(),"${text}")]`,
					document,
					null,
					XPathResult.FIRST_ORDERED_NODE_TYPE,
					null
				).singleNodeValue

				const reference = document.querySelector(selector)

				if (!node || !reference) {
					return null
				}

				return !!(reference.compareDocumentPosition(node) & Node.DOCUMENT_POSITION_PRECEDING)
			},
			{ text, selector }
		)

		expect(precedes).toBe(true)
	}

	async expectTextAfterElement(text: string, selector: string): Promise<void> {
		const follows = await this.page.evaluate(
			({ text, selector }) => {
				const node = document.evaluate(
					`//p[contains(text(),"${text}")]`,
					document,
					null,
					XPathResult.FIRST_ORDERED_NODE_TYPE,
					null
				).singleNodeValue

				const reference = document.querySelector(selector)

				if (!node || !reference) {
					return null
				}

				return !!(reference.compareDocumentPosition(node) & Node.DOCUMENT_POSITION_FOLLOWING)
			},
			{ text, selector }
		)

		expect(follows).toBe(true)
	}

	/**
	 * Create a complete snippet with save and activate
	 */
	async createAndActivateSnippet(options: SnippetFormOptions): Promise<void> {
		await this.clickAddNewSnippet()
		await this.fillSnippetForm(options)
		await this.saveSnippet('save_and_activate')
		await this.expectSuccessMessage(MESSAGES.SNIPPET_CREATED_AND_ACTIVATED)

		// Ensure activation is actually persisted by toggling from the list screen.
		await this.navigateToSnippetsAdmin()
		await this.filterSnippetsByName(options.name)
		const row = this.page
			.locator(`${SELECTORS.SNIPPET_ROW}:has(a${SELECTORS.SNIPPET_NAME_LINK}:has-text("${options.name}"))`)
			.first()
		await expect(row).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		const toggleCell = row.locator('td').first()
		const toggleSwitch = toggleCell.getByRole('switch').first()
		await expect(toggleSwitch).toBeVisible({ timeout: TIMEOUTS.DEFAULT })

		const isChecked = await toggleSwitch.isChecked().catch(() => false)
		if (!isChecked) {
			await toggleSwitch.click({ timeout: TIMEOUTS.DEFAULT, force: true })
			await expect(toggleSwitch).toBeChecked({ timeout: TIMEOUTS.DEFAULT })
		}

		await expect(toggleSwitch).toHaveAccessibleName(/Deactivate/i, { timeout: TIMEOUTS.DEFAULT })
	}

	/**
	 * Create a snippet without activating
	 */
	async createSnippet(options: SnippetFormOptions): Promise<void> {
		await this.clickAddNewSnippet()
		await this.fillSnippetForm(options)
		await this.saveSnippet('save')
		await this.expectSuccessMessage(MESSAGES.SNIPPET_CREATED)
	}
}
