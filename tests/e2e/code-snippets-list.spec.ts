import { test, expect } from '@playwright/test';
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper';
import { SELECTORS } from './helpers/constants';

const TEST_SNIPPET_NAME = 'E2E List Test Snippet';

test.describe('Code Snippets List Page Actions', () => {
	let helper: SnippetsTestHelper;

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page);
		await helper.navigateToSnippetsAdmin();

		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: 'echo "Test snippet for list actions";'
		});
		await helper.navigateToSnippetsAdmin();
	});

	test.afterEach(async ({ page }) => {
		try {
			await helper.cleanupSnippet(TEST_SNIPPET_NAME);
		} catch {}
	});

	test('Can toggle snippet activation from list page', async ({ page }) => {
		const snippetRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);
		const toggleSwitch = snippetRow.locator('a.snippet-activation-switch');

		await expect(toggleSwitch).toHaveAttribute('title', 'Deactivate');

		await toggleSwitch.click();
		await page.waitForLoadState('networkidle');

		const updatedRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);
		const updatedToggle = updatedRow.locator('a.snippet-activation-switch');
		await expect(updatedToggle).toHaveAttribute('title', 'Activate');

		await updatedToggle.click();
		await page.waitForLoadState('networkidle');

		const reactivatedRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);
		const reactivatedToggle = reactivatedRow.locator('a.snippet-activation-switch');
		await expect(reactivatedToggle).toHaveAttribute('title', 'Deactivate');
	});

	test('Can access edit from list page', async ({ page }) => {
		const snippetRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);

		await snippetRow.locator(SELECTORS.EDIT_ACTION).click();

		await expect(page).toHaveURL(/page=edit-snippet/);
		await expect(page.locator('#title')).toHaveValue(TEST_SNIPPET_NAME);
	});

	test('Can clone snippet from list page', async ({ page }) => {
		const snippetRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);

		await snippetRow.locator(SELECTORS.CLONE_ACTION).click();
		await page.waitForLoadState('networkidle');

		await expect(page).toHaveURL(/page=snippets/);

		await helper.expectTextVisible(`${TEST_SNIPPET_NAME} [CLONE]`);

		const clonedRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME} [CLONE]")`);

		page.on('dialog', dialog => {
			expect(dialog.type()).toBe('confirm');
			dialog.accept();
		});

		await clonedRow.locator(SELECTORS.DELETE_ACTION).click();
		await page.waitForLoadState('networkidle');
	});

	test('Can delete snippet from list page', async ({ page }) => {
		const snippetRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);

		page.on('dialog', dialog => {
			expect(dialog.type()).toBe('confirm');
			dialog.accept();
		});

		await snippetRow.locator(SELECTORS.DELETE_ACTION).click();
		await page.waitForLoadState('networkidle');

		await expect(page).toHaveURL(/page=snippets/);
		await helper.expectElementCount(`tr:has-text("${TEST_SNIPPET_NAME}")`, 0);
	});

	test('Can export snippet from list page', async ({ page }) => {
		const snippetRow = page.locator(`tr:has-text("${TEST_SNIPPET_NAME}")`);

		const downloadPromise = page.waitForEvent('download');

		await snippetRow.locator(SELECTORS.EXPORT_ACTION).click();

		const download = await downloadPromise;
		expect(download.suggestedFilename()).toMatch(/\.json$/);
	});
});
