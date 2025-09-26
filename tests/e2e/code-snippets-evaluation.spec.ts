import { test, expect } from '@playwright/test';
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper';
import { SELECTORS } from './helpers/constants';

const TEST_SNIPPET_NAME = 'E2E Snippet Test';

declare global {
	interface Window {
		customHeadJSTest?: string;
		testHeadFunction?: () => string;
		customFooterJSTest?: string;
		testFooterFunction?: () => string;
		footerDOMTest?: string;
	}
}

const BODY_CLASS_TEST_CODE = `
	add_filter('admin_body_class', function($classes) {
		return $classes . ' custom-admin-class';
	});

	add_filter('body_class', function($classes) {
		$classes[] = 'custom-frontend-class';
		return $classes;
	});
`;

test.describe('Code Snippets Evaluation', () => {
	let helper: SnippetsTestHelper;

	test.beforeEach(async ({ page }) => {
		helper = new SnippetsTestHelper(page);
		await helper.navigateToSnippetsAdmin();
	});


	test('PHP snippet is evaluating correctly', async () => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: "add_filter('show_admin_bar', '__return_false');"
		});

		await helper.navigateToFrontend();
		await helper.expectElementNotVisible(SELECTORS.ADMIN_BAR);
		await helper.expectElementCount(SELECTORS.ADMIN_BAR, 0);
	});

	test('PHP Snippet runs everywhere', async ({ page }) => {
		await helper.createAndActivateSnippet({
		  name: TEST_SNIPPET_NAME,
		  location: 'EVERYWHERE',
		  code: BODY_CLASS_TEST_CODE
		});

		await page.goto('/wp-admin/');
		await expect(page.locator('body')).toHaveClass(/custom-admin-class/);

		await helper.navigateToFrontend();
		await expect(page.locator('body')).toHaveClass(/custom-frontend-class/);
	});

	test('PHP Snippet runs only in Admin', async ({ page }) => {
		await helper.createAndActivateSnippet({
		  name: TEST_SNIPPET_NAME,
		  location: 'ADMIN_ONLY',
		  code: BODY_CLASS_TEST_CODE
		});

		await page.goto('/wp-admin/');
		await expect(page.locator('body')).toHaveClass(/custom-admin-class/);

		await helper.navigateToFrontend();
		await expect(page.locator('body')).not.toHaveClass(/custom-frontend-class/);
	});

	test('PHP Snippet runs only in Frontend', async ({ page }) => {
		await helper.createAndActivateSnippet({
		  name: TEST_SNIPPET_NAME,
		  location: 'FRONTEND_ONLY',
		  code: BODY_CLASS_TEST_CODE
		});

		await page.goto('/wp-admin/');
		await expect(page.locator('body')).not.toHaveClass(/custom-admin-class/);

		await helper.navigateToFrontend();
		await expect(page.locator('body')).toHaveClass(/custom-frontend-class/);
	});

	test('HTML snippet is evaluating correctly in footer', async () => {
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

	test('HTML snippet is evaluating correctly in header', async () => {
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

	test('HTML snippet works with shortcode in editor', async ({ page }) => {
		const snippetId = await createHtmlSnippetForEditor();

		const pageUrl = await createPageWithShortcode(snippetId);

		await verifyShortcodeRendersCorrectly(page, pageUrl);

		async function createHtmlSnippetForEditor(): Promise<string> {
			await helper.createAndActivateSnippet({
				name: TEST_SNIPPET_NAME,
				code: "<div class='custom-snippet-content'><h3>Custom HTML Content</h3><p>This content was inserted via shortcode!</p></div>",
				type: 'HTML',
				location: 'IN_EDITOR'
			});

			const currentUrl = page.url();
			const urlMatch = currentUrl.match(/[?&]id=(\d+)/);
			expect(urlMatch).toBeTruthy();
			return urlMatch![1];
		}

		async function createPageWithShortcode(snippetId: string): Promise<string> {
			const { exec } = require('child_process');
			const util = require('util');
			const execAsync = util.promisify(exec);

			const shortcode = `[code_snippet id=${snippetId} format name="${TEST_SNIPPET_NAME}"]`;
			const pageContent = `<p>Page content before shortcode.</p>
${shortcode}
<p>Page content after shortcode.</p>`;

			try {
				const createPageCmd = `npx wp-env run cli wp post create --post_type=page --post_title="Test Page for Snippet Shortcode" --post_content='${pageContent}' --post_status=publish --porcelain`;
				const { stdout } = await execAsync(createPageCmd);
				const pageId = stdout.trim();
				const getUrlCmd = `npx wp-env run cli wp post url ${pageId}`;
				const { stdout: pageUrl } = await execAsync(getUrlCmd);
				return pageUrl.trim();
			} catch (error) {
				console.error('Failed to create page via WP-CLI:', error);
				throw error;
			}
		}

		async function verifyShortcodeRendersCorrectly(page: any, pageUrl: string): Promise<void> {
			await page.goto(pageUrl);

			await expect(page.locator('.custom-snippet-content')).toBeVisible();
			await expect(page.locator('.custom-snippet-content h3')).toContainText('Custom HTML Content');
			await expect(page.locator('.custom-snippet-content p')).toContainText('This content was inserted via shortcode!');
			await helper.expectTextVisible('Page content before shortcode.');
			await helper.expectTextVisible('Page content after shortcode.');
		}
	});

	test('CSS snippet is evaluating correctly on site front-end', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: `
				.custom-css-test-element {
					background-color: rgb(255, 0, 0);
					color: rgb(255, 255, 255);
					padding: 10px;
					font-size: 16px;
				}
			`,
			type: 'CSS',
			location: 'CSS_FRONTEND_ONLY'
		});

		await helper.navigateToFrontend();
		await helper.createTestElement('custom-css-test-element', 'CSS Test Element');
		
		await helper.verifyStylesApplied('.custom-css-test-element', {
			backgroundColor: 'rgb(255, 0, 0)',
			color: 'rgb(255, 255, 255)',
			padding: '10px'
		});

		// Verify CSS is not loaded in admin
		await page.goto('/wp-admin/');
		await helper.createTestElement('custom-css-test-element', 'CSS Test Element Admin');
		
		await helper.verifyStylesNotApplied('.custom-css-test-element', {
			backgroundColor: 'rgb(255, 0, 0)'
		});
	});

	test('CSS snippet is evaluating correctly in administration area', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: `
				.custom-admin-css-test {
					background-color: rgb(0, 0, 255);
					color: rgb(255, 255, 255);
					border: 2px solid rgb(255, 255, 0);
				}
			`,
			type: 'CSS',
			location: 'CSS_ADMIN_ONLY'
		});

		await page.goto('/wp-admin/');
		await helper.createTestElement('custom-admin-css-test', 'Admin CSS Test Element');

		await helper.verifyStylesApplied('.custom-admin-css-test', {
			backgroundColor: 'rgb(0, 0, 255)',
			color: 'rgb(255, 255, 255)'
		});

		const border = await helper.getComputedStyle('.custom-admin-css-test', 'border');
		expect(border).toContain('rgb(255, 255, 0)');

		// Verify CSS is not loaded on frontend
		await helper.navigateToFrontend();
		await helper.createTestElement('custom-admin-css-test', 'Frontend CSS Test Element');
		
		await helper.verifyStylesNotApplied('.custom-admin-css-test', {
			backgroundColor: 'rgb(0, 0, 255)'
		});
	});

	test('JavaScript snippet is evaluating correctly in site <head> section', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: `
				window.customHeadJSTest = 'loaded-in-head';
				
				document.addEventListener('DOMContentLoaded', function() {
					const testDiv = document.createElement('div');
					testDiv.id = 'js-head-test-element';
					testDiv.textContent = 'JavaScript Head Test Loaded';
					testDiv.style.display = 'none';
					document.body.appendChild(testDiv);
				});
				
				window.testHeadFunction = function() {
					return 'head-function-works';
				};
			`,
			type: 'JS',
			location: 'SITE_HEADER'
		});

		await helper.navigateToFrontend();
		
		await helper.verifyGlobalVariable('customHeadJSTest', 'loaded-in-head');
		await helper.verifyGlobalFunction('testHeadFunction', 'head-function-works');

		await expect(page.locator('#js-head-test-element')).toBeAttached();
		await expect(page.locator('#js-head-test-element')).toContainText('JavaScript Head Test Loaded');
	});

	test('JavaScript snippet is evaluating correctly in site footer', async ({ page }) => {
		await helper.createAndActivateSnippet({
			name: TEST_SNIPPET_NAME,
			code: `
				window.customFooterJSTest = 'loaded-in-footer';
				
				const footerTestDiv = document.createElement('div');
				footerTestDiv.id = 'js-footer-test-element';
				footerTestDiv.textContent = 'JavaScript Footer Test Loaded';
				footerTestDiv.style.color = 'rgb(255, 0, 0)';
				footerTestDiv.style.display = 'none';
				document.body.appendChild(footerTestDiv);
				
				window.testFooterFunction = function() {
					return 'footer-function-works';
				};
				
				window.footerDOMTest = document.body ? 'dom-available' : 'dom-not-available';
			`,
			type: 'JS',
			location: 'SITE_FOOTER'
		});

		await helper.navigateToFrontend();
		
		await helper.verifyGlobalVariable('customFooterJSTest', 'loaded-in-footer');
		await helper.verifyGlobalFunction('testFooterFunction', 'footer-function-works');
		await helper.verifyGlobalVariable('footerDOMTest', 'dom-available');

		await expect(page.locator('#js-footer-test-element')).toBeAttached();
		await expect(page.locator('#js-footer-test-element')).toContainText('JavaScript Footer Test Loaded');

		const elementColor = await helper.getComputedStyle('#js-footer-test-element', 'color');
		expect(elementColor).toBe('rgb(255, 0, 0)');
	});

	test.afterEach(async () => {
		await helper.cleanupSnippet(TEST_SNIPPET_NAME);
	});
});
