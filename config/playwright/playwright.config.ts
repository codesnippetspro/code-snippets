/// <reference types="node" />
import { join } from 'path'
import { defineConfig, devices } from '@playwright/test'

const WORKERS = 1

const CI_RETRIES = 2
const LOCAL_RETRIES = 1

const TEST_TIMEOUT_SECONDS = 60
const ASSERT_TIMEOUT_SECONDS = 30

const MILLISECONDS_IN_SECOND = 1000

const baseTestsDir = join(__dirname, '..', '..', 'tests')
const storageState =  join(baseTestsDir, 'e2e/.auth/user.json')
const rtlSpecs = /rtl-layout\.spec\.ts/

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
	testDir: join(baseTestsDir, 'e2e'),
	snapshotPathTemplate: '{testDir}/{testFileDir}/{testFileName}-snapshots/{arg}-{platform}{ext}',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? CI_RETRIES : LOCAL_RETRIES,
	workers: WORKERS,
	reporter: process.env.CI
		? [
			['line'],
			['html'],
			['json', { outputFile: join(process.cwd(), 'test-results', 'results.json') }],
			['junit', { outputFile: join(process.cwd(), 'test-results', 'results.xml') }]
		]
		: [
			['html'],
			['json', { outputFile: join(process.cwd(), 'test-results', 'results.json') }],
			['junit', { outputFile: join(process.cwd(), 'test-results', 'results.xml') }]
		],
	use: {
		baseURL: 'http://localhost:8888',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure'
	},

	projects: [
		{
			name: 'setup',
			testMatch: /auth\.setup\.ts/
		},
		{
			name: 'flat-files-setup',
			testMatch: /flat-files\.setup\.ts/,
			use: {
				...devices['Desktop Chrome'],
				storageState
			},
			dependencies: ['setup']
		},

		{
			name: 'chromium-db-snippets',
			use: {
				...devices['Desktop Chrome'],
				storageState
			},
			dependencies: ['setup'],
			testIgnore: [/.*\.setup\.ts/, /.*\.teardown\.ts/, rtlSpecs]
		},

		{
			name: 'chromium-file-based-snippets',
			use: {
				...devices['Desktop Chrome'],
				storageState
			},
			dependencies: ['setup', 'flat-files-setup'],
			testIgnore: [/.*\.setup\.ts/, /.*\.teardown\.ts/, rtlSpecs]
		},

		// The RTL specs run with the test user on a right-to-left locale. The
		// setup installs the language pack and switches the user; the teardown
		// switches back, so the other projects never see the site mirrored.
		{
			name: 'rtl-setup',
			testMatch: /rtl\.setup\.ts/,
			use: {
				...devices['Desktop Chrome'],
				storageState
			},
			dependencies: ['setup']
		},
		{
			name: 'rtl-teardown',
			testMatch: /rtl\.teardown\.ts/
		},
		{
			name: 'chromium-rtl',
			testMatch: rtlSpecs,
			use: {
				...devices['Desktop Chrome'],
				storageState
			},
			dependencies: ['setup', 'rtl-setup'],
			teardown: 'rtl-teardown'
		}
	],

	timeout: TEST_TIMEOUT_SECONDS * MILLISECONDS_IN_SECOND,

	expect: {
		timeout: ASSERT_TIMEOUT_SECONDS * MILLISECONDS_IN_SECOND,
		toHaveScreenshot: { maxDiffPixels: 100 }
	}
})
