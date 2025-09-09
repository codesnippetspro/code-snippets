import { test, expect } from '@playwright/test';

const TEST_SNIPPET_NAME = 'E2E Test Snippet';

test.describe('Code Snippets Plugin', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets');
		await page.waitForLoadState('networkidle');
		await page.waitForSelector('#wpbody-content, .wrap, #wpcontent', { timeout: 10000 });
	});

	test('Can access snippets admin page', async ({ page }) => {
		const currentUrl = page.url();
		expect(currentUrl).toContain('page=snippets');

		await expect(page.locator('h1, .page-title')).toBeVisible();
	});

	test('Can add a new snippet', async ({ page }) => {
		await page.waitForSelector('h1, .page-title', { timeout: 10000 });
		await page.click('.page-title-action');
		await page.waitForLoadState('networkidle');

		await page.waitForSelector('#title');
		await page.fill('#title', TEST_SNIPPET_NAME);

		await page.waitForSelector('.CodeMirror textarea');
		await page.fill('.CodeMirror textarea', 'echo "Hello World!";');

		await page.click('text=Save Snippet');
		await expect(page.locator('#message.notice')).toContainText('Snippet created');
	});

	test('Can activate and deactivate a snippet', async ({ page }) => {
		await page.waitForSelector(`text=${TEST_SNIPPET_NAME}`);
		await page.click(`text=${TEST_SNIPPET_NAME}`);
		await page.waitForLoadState('networkidle');

		await page.click('text=Save and Activate');
		await expect(page.locator('#message.notice p')).toContainText('Snippet updated and activated');

		await page.click('text=Save and Deactivate');
		await expect(page.locator('#message.notice p')).toContainText('Snippet updated and deactivated');
	});

	test('Can delete a snippet', async ({ page }) => {
		await page.waitForSelector(`text=${TEST_SNIPPET_NAME}`);
		await page.click(`text=${TEST_SNIPPET_NAME}`);
		await page.waitForLoadState('networkidle');

		await page.click('text=Delete');
		await page.click('button.components-button.is-destructive.is-primary');
		await expect(page.locator('body')).not.toContainText(TEST_SNIPPET_NAME);
	});
});
