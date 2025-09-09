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
			
			// Debug: Check what buttons are available
			const buttonCount = await page.locator('.page-title-action').count();
			console.log('Page title action buttons found:', buttonCount);
			
			if (buttonCount === 0) {
				// Try alternative selectors
				const altButtons = await page.locator('a[href*="action=add"], button:has-text("Add"), a:has-text("Add New")').count();
				console.log('Alternative add buttons found:', altButtons);
				
				// Take screenshot for debugging
				await page.screenshot({ path: 'debug-no-add-button.png' });
				throw new Error('No Add New button found');
			}
			
			await page.click('.page-title-action');
			await page.waitForLoadState('networkidle');
			
			// Debug: Check where we are after clicking
			const currentUrl = page.url();
			console.log('URL after clicking Add New:', currentUrl);
			
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
