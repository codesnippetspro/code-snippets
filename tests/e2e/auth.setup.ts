import { test as setup } from '@playwright/test';

setup('authenticate', async ({ page }) => {
  await page.goto('/wp-login.php');
  await page.waitForSelector('#user_login');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'password');
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);
  
  await page.context().storageState({ path: 'auth.json' });
});
