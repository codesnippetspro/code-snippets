import { test as setup } from '@playwright/test';

setup('authenticate', async ({ page }) => {
  console.log('Starting authentication setup...');
  
  await page.goto('/wp-login.php');
  console.log('Navigated to login page:', page.url());
  
  await page.waitForSelector('#user_login');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'password');
  await page.click('#wp-submit');
  
  await page.waitForURL(/wp-admin/);
  console.log('Successfully logged in, current URL:', page.url());
  
  // Verify we can access admin
  await page.goto('/wp-admin/');
  const adminTitle = await page.title();
  console.log('Admin page title:', adminTitle);
  
  await page.context().storageState({ path: 'auth.json' });
  console.log('Authentication state saved to auth.json');
});
