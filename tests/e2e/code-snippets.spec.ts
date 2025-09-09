import { test, expect } from '@playwright/test';

const TEST_SNIPPET_NAME = 'E2E Test Snippet';

test.describe('Code Snippets Admin Page @admin', () => {
	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=snippets');
		await page.waitForLoadState('networkidle');
		
		// Debug: Check if we're actually logged in and on the right page
		const currentUrl = page.url();
		console.log('Current URL:', currentUrl);
		
		// If we're redirected to login, something is wrong with auth
		if (currentUrl.includes('wp-login.php')) {
			throw new Error('Authentication failed - redirected to login page');
		}
	});

	test('Can access admin page', async ({ page }) => {
		// Simple test to verify we can access the admin
		const title = await page.title();
		console.log('Page title:', title);
		
		// Check if we're actually on the snippets page
		const url = page.url();
		console.log('Current URL:', url);
		
		// Verify the page has some expected content
		const bodyText = await page.textContent('body');
		const hasSnippetsContent = bodyText?.includes('Snippets') || bodyText?.includes('snippet');
		console.log('Has snippets content:', hasSnippetsContent);
		
		expect(url).toContain('page=snippets');
	});

  	test('Can add a new snippet', async ({ page }) => {
		// Debug: Check what's actually on the page
		const pageContent = await page.textContent('body');
		console.log('Page content preview:', pageContent?.substring(0, 500));
		
		// Check if Add New button exists
		const addNewButton = page.locator('text=Add New');
		const buttonCount = await addNewButton.count();
		console.log('Add New button count:', buttonCount);
		
		// Also check for alternative button text
		const addNewVariants = [
			'text=Add New',
			'text=Add New Snippet', 
			'text=New Snippet',
			'[href*="action=add"]',
			'button:has-text("Add")',
			'button:has-text("New")'
		];
		
		for (const selector of addNewVariants) {
			const count = await page.locator(selector).count();
			console.log(`Selector "${selector}" count:`, count);
		}
		
		if (buttonCount === 0) {
			// Take a screenshot for debugging
			await page.screenshot({ path: 'debug-no-add-new.png' });
			throw new Error('Add New button not found on page');
		}
		
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
