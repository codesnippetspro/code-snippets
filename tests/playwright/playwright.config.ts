/// <reference types="node" />
import { join } from 'path'
import { defineConfig, devices } from '@playwright/test'

const RETRIES = 2
const WORKERS = 1

/**
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
	testDir: '../e2e',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? RETRIES : 0,
	workers: process.env.CI ? WORKERS : undefined,
	reporter: [
		['html'],
		['json', { outputFile: 'test-results/results.json' }],
		['junit', { outputFile: 'test-results/results.xml' }]
	],
	use: {
		baseURL: 'http://localhost:8888',
		trace: 'on-first-retry',
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

	timeout: 30000,

	expect: {
		timeout: 10000,
		toHaveScreenshot: { maxDiffPixels: 100 }
	}
})
