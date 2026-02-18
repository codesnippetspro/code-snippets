/// <reference types="node" />
import { join } from 'path'
import { defineConfig, devices } from '@playwright/test'

const WORKERS = 1

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
	testDir: '../e2e',
	snapshotPathTemplate: '{testDir}/{testFileDir}/{testFileName}-snapshots/{arg}-{platform}{ext}',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: 0,
	workers: process.env.CI ? WORKERS : WORKERS,
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
				storageState: join(__dirname, '../e2e/.auth/user.json')
			},
			dependencies: ['setup']
		},

		{
			name: 'chromium-db-snippets',
			use: {
				...devices['Desktop Chrome'],
				storageState: join(__dirname, '../e2e/.auth/user.json')
			},
			dependencies: ['setup'],
			testIgnore: /.*\.setup\.ts/
		},

		{
			name: 'chromium-file-based-snippets',
			use: {
				...devices['Desktop Chrome'],
				storageState: join(__dirname, '../e2e/.auth/user.json')
			},
			dependencies: ['setup', 'flat-files-setup'],
			testIgnore: /.*\.setup\.ts/
		}
	],

	timeout: 60000, // 60 seconds per test

	expect: {
		timeout: 30000, // 30 seconds for each expect assertion
		toHaveScreenshot: { maxDiffPixels: 100 }
	}
})
