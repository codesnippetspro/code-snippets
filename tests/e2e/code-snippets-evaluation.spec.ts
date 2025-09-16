import { test, expect } from '@playwright/test';
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper';
import { SELECTORS } from './helpers/constants';

const TEST_SNIPPET_NAME = 'E2E Admin Bar Hide Test';

test.describe('Code Snippets Evaluation', () => {
	let helper: SnippetsTestHelper;

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page);
		await helper.navigateToSnippetsAdmin();
	});

	test('PHP snippet is evaluating correctly', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: "add_filter('show_admin_bar', '__return_false');"
		});

		await helper.navigateToFrontend();
		await helper.expectElementNotVisible(SELECTORS.ADMIN_BAR);
		await helper.expectElementCount(SELECTORS.ADMIN_BAR, 0);
	});

	test('PHP Snippet runs only in Admin', async ({ page }) => {
		await helper.createAndActivateSnippet({
		  name: 'Admin Only Body Class Test',
		  location: 'ADMIN_ONLY',
		  code: `
			add_filter('admin_body_class', function($classes) {
			  return $classes . ' custom-admin-class';
			});

			add_filter('body_class', function($classes) {
			  $classes[] = 'custom-frontend-class';
			  return $classes;
			});
		  `
		});

		await page.goto('/wp-admin/');
		await expect(page.locator('body')).toHaveClass(/custom-admin-class/);

		await helper.navigateToFrontend();
		await expect(page.locator('body')).not.toHaveClass(/custom-frontend-class/);
	});

	test('PHP Snippet runs only in Frontend', async ({ page }) => {
		await helper.createAndActivateSnippet({
		  name: 'Frontend Only Body Class Test',
		  location: 'FRONTEND_ONLY',
		  code: `
			add_filter('admin_body_class', function($classes) {
			  return $classes . ' custom-admin-class';
			});

			add_filter('body_class', function($classes) {
			  $classes[] = 'custom-frontend-class';
			  return $classes;
			});
		  `
		});

		await page.goto('/wp-admin/');
		await expect(page.locator('body')).not.toHaveClass(/custom-admin-class/);

		await helper.navigateToFrontend();
		await expect(page.locator('body')).toHaveClass(/custom-frontend-class/);
	});

	test('HTML snippet is evaluating correctly in footer', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: "<p>Hello World HTML snippet in footer!</p>",
			type: 'HTML',
			location: 'SITE_FOOTER'
		});

		await helper.navigateToFrontend();
		await helper.expectTextVisible('Hello World HTML snippet in footer!');
		await helper.expectElementCount('text=Hello World HTML snippet in footer!', 1);
	});

	test('HTML snippet is evaluating correctly in header', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: "<p>Hello World HTML snippet in header!</p>",
			type: 'HTML',
			location: 'SITE_HEADER'
		});

		await helper.navigateToFrontend();
		await helper.expectTextVisible('Hello World HTML snippet in header!');
		await helper.expectElementCount('text=Hello World HTML snippet in header!', 1);
	});

	test.afterEach(async ({ page }) => {
		await helper.cleanupSnippet(TEST_SNIPPET_NAME);
	});
});
