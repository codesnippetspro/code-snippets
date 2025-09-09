import { test, expect } from '@playwright/test';

test.describe('Code Snippets Admin Page @admin', () => {
  test('should display the Functions tab on the snippets admin page', async ({ page }) => {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
    
    await page.goto('/wp-admin/admin.php?page=snippets');
    
    await page.waitForLoadState('networkidle');
    
    const functionsTab = page.locator('text=Functions').first();
    
    await expect(functionsTab).toBeVisible();
  });
});
