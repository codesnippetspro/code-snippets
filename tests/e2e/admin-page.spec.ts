import { test, expect } from '@playwright/test';

test.describe('Code Snippets Admin Page @admin', () => {
  test('should display the Functions tab on the snippets admin page', async ({ page }) => {
    // First, log in to WordPress
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
    
    // Navigate to the Code Snippets admin page
    await page.goto('/wp-admin/admin.php?page=snippets');
    
    // Wait for the page to load
    await page.waitForLoadState('networkidle');
    
    // Look for the Functions tab - just check if it exists
    const functionsTab = page.locator('text=Functions').first();
    
    // Check that the tab exists and is visible
    await expect(functionsTab).toBeVisible();
  });
});
