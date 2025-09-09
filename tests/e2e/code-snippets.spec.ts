import { test, expect } from '@playwright/test';

const TEST_SNIPPET_NAME = 'E2E Test Snippet';

test.describe('Code Snippets Plugin', () => {
	test('Complete snippet workflow', async ({ page }) => {
		await test.step('Login to WordPress', async () => {
			await page.goto('/wp-login.php');
			await page.waitForSelector('#user_login');
			await page.fill('#user_login', 'admin');
			await page.fill('#user_pass', 'password');
			await page.click('#wp-submit');
			await page.waitForURL(/wp-admin/);
		});

		await test.step('Navigate to snippets page', async () => {
			await page.goto('/wp-admin/admin.php?page=snippets');
			await page.waitForLoadState('networkidle');
			await page.waitForSelector('#wpbody-content, .wrap, #wpcontent', { timeout: 10000 });

			const currentUrl = page.url();
			expect(currentUrl).toContain('page=snippets');
		});

		await test.step('Add a new snippet', async () => {
			await page.waitForSelector('h1, .page-title', { timeout: 10000 });

			await page.click('.page-title-action, .wrap .page-title-action');
			await page.waitForLoadState('networkidle');

			await page.waitForSelector('#title');
			await page.fill('#title', TEST_SNIPPET_NAME);

			await page.waitForSelector('.CodeMirror textarea');
			await page.fill('.CodeMirror textarea', 'echo "Hello World!";');

			await page.click('text=Save Snippet');
			await expect(page.locator('#message.notice')).toContainText('Snippet created');
		});

		await test.step('Activate the snippet', async () => {
			await page.goto('/wp-admin/admin.php?page=snippets');
			await page.waitForLoadState('networkidle');

			await page.waitForSelector(`text=${TEST_SNIPPET_NAME}`);
			await page.click(`text=${TEST_SNIPPET_NAME}`);
			await page.waitForLoadState('networkidle');

			await page.click('text=Save and Activate');
			await expect(page.locator('#message.notice p')).toContainText('Snippet updated and activated');
		});

		await test.step('Deactivate the snippet', async () => {
			await page.click('text=Save and Deactivate');
			await expect(page.locator('#message.notice p')).toContainText('Snippet updated and deactivated');
		});

		await test.step('Delete the snippet', async () => {
			await page.goto('/wp-admin/admin.php?page=snippets');
			await page.waitForLoadState('networkidle');

			await page.waitForSelector(`text=${TEST_SNIPPET_NAME}`);
			await page.click(`text=${TEST_SNIPPET_NAME}`);
			await page.waitForLoadState('networkidle');

			await page.click('text=Delete');
			await page.click('button.components-button.is-destructive.is-primary');
			await expect(page.locator('body')).not.toContainText(TEST_SNIPPET_NAME);
		});
	});
});
