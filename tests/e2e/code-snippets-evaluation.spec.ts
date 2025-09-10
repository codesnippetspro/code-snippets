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

	test('HTML snippet is evaluating correctly', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: "<p>Hello World HTML snippet!</p>",
			type: 'HTML',
			location: 'SITE_FOOTER'
		});

		await helper.navigateToFrontend();
		await helper.expectTextVisible('Hello World HTML snippet!');
		await helper.expectElementCount('text=Hello World HTML snippet!', 1);
	});

	test.afterEach(async ({ page }) => {
		await helper.cleanupSnippet(TEST_SNIPPET_NAME);
	});
});
