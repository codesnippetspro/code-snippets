import { readFileSync } from 'fs'
import { expect, test } from '@playwright/test'
import { DEFAULT_E2E_SNIPPET_BASE_NAME, SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { SELECTORS, URLS } from './helpers/constants'

const importFile = (snippet: Record<string, unknown>) => ({
	name: 'code-snippets-export.json',
	mimeType: 'application/json',
	buffer: Buffer.from(JSON.stringify({ snippets: [snippet] }))
})

test.describe('Code Snippets Import', () => {
	test.beforeEach(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
	})

	test.afterEach(async () => {
		await SnippetsTestHelper.cleanupSnippetsByPrefix(DEFAULT_E2E_SNIPPET_BASE_NAME)
	})

	test('imports a selected JSON snippet as inactive', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName('Imported JSON')

		await page.goto(URLS.IMPORT_ADMIN)
		await page.getByRole('radio', { name: /Do not import any duplicate snippets/ }).check()
		await page.getByLabel('Select files to import').setInputFiles(importFile({
			id: 1,
			name: snippetName,
			desc: 'Imported from an E2E JSON export.',
			code: `// ${snippetName}`,
			tags: ['imported'],
			scope: 'global',
			priority: 10
		}))
		await page.getByRole('button', { name: 'Upload files' }).click()

		await expect(page.getByRole('heading', { name: 'Available snippets (1)' })).toBeVisible()
		await expect(page.getByRole('row', { name: new RegExp(snippetName) })).toBeVisible()
		await page.getByRole('checkbox', { name: 'Select all snippets' }).check()
		await page.getByRole('button', { name: 'Import Selected (1)' }).first().click()

		await expect(page.getByRole('heading', { name: 'Import successful' })).toBeVisible()
		await expect(page.locator('.import-result-message')).toContainText('Successfully imported 1 snippet.')
		await expect(page.locator('.import-result-display-card').getByRole('link', { name: 'All Snippets' })).toBeVisible()

		await page.goto(URLS.SNIPPETS_ADMIN)
		const importedRow = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
		await expect(importedRow).toBeVisible()
		await expect(importedRow.getByRole('switch')).not.toBeChecked()
	})

	test('re-imports an exported snippet from its downloaded JSON file', async ({ page }) => {
		test.setTimeout(60000)
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName('Export round trip')

		await SnippetsTestHelper.createSnippetViaCli({
			name: snippetName,
			description: 'A snippet restored from its exported file.',
			active: false,
			priority: 7,
			scope: 'front-end',
			tags: ['round-trip']
		})
		await page.goto(URLS.SNIPPETS_ADMIN)

		const sourceRow = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
		await sourceRow.hover()
		const download = await Promise.all([
			page.waitForEvent('download'),
			sourceRow.locator(SELECTORS.EXPORT_ACTION).first().click()
		]).then(([downloadEvent]) => downloadEvent)
		const downloadPath = await download.path()

		if (!downloadPath) {
			throw new Error('Export did not produce a local file path')
		}

		await SnippetsTestHelper.cleanupSnippetsByPrefix(snippetName)
		await page.goto(URLS.IMPORT_ADMIN)
		await page.getByLabel('Select files to import').setInputFiles({
			name: download.suggestedFilename(),
			mimeType: 'application/json',
			buffer: readFileSync(downloadPath)
		})
		await page.getByRole('button', { name: 'Upload files' }).click()
		await page.getByRole('checkbox', { name: 'Select all snippets' }).check()
		await page.getByRole('button', { name: 'Import Selected (1)' }).first().click()

		await expect(page.getByRole('heading', { name: 'Import successful' })).toBeVisible()
		await page.goto(URLS.SNIPPETS_ADMIN)
		const importedRow = page.locator(SELECTORS.SNIPPET_ROW).filter({ hasText: snippetName }).first()
		await expect(importedRow).toBeVisible()
		await expect(importedRow.getByRole('switch')).not.toBeChecked()
	})

	test('rejects an invalid upload with a clear error', async ({ page }) => {
		await page.goto(URLS.IMPORT_ADMIN)
		await page.getByLabel('Select files to import').setInputFiles({
			name: 'not-a-snippet.txt',
			mimeType: 'text/plain',
			buffer: Buffer.from('not a Code Snippets export')
		})
		await page.getByRole('button', { name: 'Upload files' }).click()

		await expect(page.getByRole('heading', { name: 'File upload error' })).toBeVisible()
		await expect(page.locator('.import-result-message')).toContainText('No valid snippets found')
	})

	test('skips and replaces duplicate snippets according to the selected policy', async ({ page }) => {
		const snippetName = SnippetsTestHelper.makeUniqueSnippetName('Imported duplicate')
		const duplicate = {
			id: 1,
			name: snippetName,
			code: `// replacement for ${snippetName}`,
			scope: 'global'
		}

		await SnippetsTestHelper.createSnippetViaCli({
			name: snippetName,
			code: `// original ${snippetName}`,
			active: false
		})

		await page.goto(URLS.IMPORT_ADMIN)
		await page.getByRole('radio', { name: /Do not import any duplicate snippets/ }).check()
		await page.getByLabel('Select files to import').setInputFiles(importFile(duplicate))
		await page.getByRole('button', { name: 'Upload files' }).click()
		await page.getByRole('checkbox', { name: 'Select all snippets' }).check()
		await page.getByRole('button', { name: 'Import Selected (1)' }).first().click()
		await expect(page.locator('.import-result-message')).toContainText('Successfully imported 0 snippets.')

		await page.goto(URLS.IMPORT_ADMIN)
		await page.getByRole('radio', { name: /Replace any existing snippets/ }).check()
		await page.getByLabel('Select files to import').setInputFiles(importFile(duplicate))
		await page.getByRole('button', { name: 'Upload files' }).click()
		await page.getByRole('checkbox', { name: 'Select all snippets' }).check()
		await page.getByRole('button', { name: 'Import Selected (1)' }).first().click()
		await expect(page.locator('.import-result-message')).toContainText('Successfully imported 1 snippet.')
	})
})
