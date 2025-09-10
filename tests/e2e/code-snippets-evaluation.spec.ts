import { test, expect } from '@playwright/test';

const TEST_SNIPPET_NAME = 'E2E Admin Bar Hide Test';

test.describe('Code Snippets Evaluation', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets');
		await page.waitForLoadState('networkidle');
		await page.waitForSelector('#wpbody-content, .wrap, #wpcontent', { timeout: 10000 });
	});

	test('PHP snippet is evaluating correctly', async ({ page }) => {
		await page.waitForSelector('h1, .page-title', { timeout: 10000 });
		await page.click('.page-title-action');
		await page.waitForLoadState('networkidle');

		await page.waitForSelector('#title');
		await page.fill('#title', TEST_SNIPPET_NAME);

		await page.waitForSelector('.CodeMirror textarea');
		await page.fill('.CodeMirror textarea', "add_filter('show_admin_bar', '__return_false');");

		await page.click('text=Save and Activate');
		await expect(page.locator('#message.notice')).toContainText('Snippet created and activated');

		await page.goto('/');
		await page.waitForLoadState('networkidle');

		await expect(page.locator('#wpadminbar')).not.toBeVisible();

		const adminBarCount = await page.locator('#wpadminbar').count();
		expect(adminBarCount).toBe(0);
	});

	test.afterEach(async ({ page }) => {
		// Clean up
		await page.goto('/wp-admin/admin.php?page=snippets');
		await page.waitForLoadState('networkidle');

		const snippetExists = await page.locator(`text=${TEST_SNIPPET_NAME}`).count();
		if (snippetExists > 0) {
			await page.click(`text=${TEST_SNIPPET_NAME}`);
			await page.waitForLoadState('networkidle');

			await page.click('text=Delete');
			await page.click('button.components-button.is-destructive.is-primary');
		}
	});
});
