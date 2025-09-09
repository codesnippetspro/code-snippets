import { test, expect } from '@playwright/test';

const TEST_SNIPPET_NAME = 'E2E Test Snippet';

test.describe('Code Snippets Admin Page @admin', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets');
		await page.waitForLoadState('networkidle');
	});

  	test('Can add a new snippet', async ({ page }) => {
		await page.click('text=Add New');
		await page.waitForLoadState('networkidle');
		
		// Wait for the form to be ready
		await page.waitForSelector('#title');
		await page.fill('#title', TEST_SNIPPET_NAME);
		
		// Wait for CodeMirror to be ready
		await page.waitForSelector('.CodeMirror textarea');
		await page.fill('.CodeMirror textarea', 'echo "Hello World!";');
		
		await page.click('text=Save Snippet');
		await expect(page.locator('#message.notice')).toContainText('Snippet created');
	});

  	test('Can activate and deactivate a snippet', async ({ page }) => {
		// Wait for the snippet to exist before clicking
		await page.waitForSelector(`text=${TEST_SNIPPET_NAME}`);
		await page.click(`text=${TEST_SNIPPET_NAME}`);
		await page.waitForLoadState('networkidle');
		
		await page.click('text=Save and Activate');
		await expect(page.locator('#message.notice p')).toContainText('Snippet updated and activated.');
		await page.click('text=Save and Deactivate');
		await expect(page.locator('#message.notice p')).toContainText('Snippet updated and deactivated');
	});

  	test('Can delete a snippet', async ({ page }) => {
		// Wait for the snippet to exist before clicking
		await page.waitForSelector(`text=${TEST_SNIPPET_NAME}`);
		await page.click(`text=${TEST_SNIPPET_NAME}`);
		await page.waitForLoadState('networkidle');
		
		await page.click('text=Delete');
		await page.click('button.components-button.is-destructive.is-primary'); // Confirm dialog
		await expect(page.locator('body')).not.toContainText(TEST_SNIPPET_NAME);
	});
});
