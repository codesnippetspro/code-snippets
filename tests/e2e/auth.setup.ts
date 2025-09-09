import { test as setup, expect } from '@playwright/test';
import * as path from 'path';

const authFile = path.join(__dirname, '.auth/user.json');

setup('authenticate', async ({ page }) => {
	await page.goto('/wp-login.php');	
	await page.waitForSelector('#user_login');

	await page.fill('#user_login', 'admin');
	await page.fill('#user_pass', 'password');

	await page.click('#wp-submit');

	await page.waitForURL(/wp-admin/);
	await page.waitForSelector('#wpbody-content, #adminmenu');

	await expect(page.locator('#adminmenu')).toBeVisible();

	await page.context().storageState({ path: authFile });
});
