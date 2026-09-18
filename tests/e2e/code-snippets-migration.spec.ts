import { expect, test } from '@playwright/test'
import { URLS } from './helpers/constants'

const IMPORTER = {
	name: 'header-footer-code-manager',
	title: 'Header Footer Code Manager',
	is_active: true
}

const SNIPPETS = [
	{ id: 7, title: 'Legacy Header Code', table_data: { id: 7, title: 'Legacy Header Code' } },
	{ id: 8, title: 'Legacy Footer Code', table_data: { id: 8, title: 'Legacy Footer Code' } }
]

const isMigrationRequest = (url: URL): boolean =>
	url.pathname.includes('/import/plugins') || true === url.searchParams.get('rest_route')?.includes('/import/plugins')

const migrationRoute = (url: URL) => url.searchParams.get('rest_route') ?? url.pathname

const isSelectedImporterRoute = (url: URL) => migrationRoute(url).endsWith(`/import/plugins/${IMPORTER.name}`)

test.describe('Code Snippets Migration', () => {
	test('opens the migration tab from its URL state', async ({ page }) => {
		await page.route(isMigrationRequest, route => route.fulfill({ json: {} }))
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await expect(page.getByRole('heading', { level: 1, name: 'Import: Migrate from other plugins' })).toBeVisible()
		await expect(page.getByRole('combobox', { name: 'Select plugin' })).toBeVisible()
	})

	test('lists available importers', async ({ page }) => {
		await page.route(isMigrationRequest, route => route.fulfill({ json: { [IMPORTER.name]: IMPORTER } }))
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await expect(page.getByRole('option', { name: IMPORTER.title })).toBeEnabled()
	})

	test('identifies inactive importers without allowing their selection', async ({ page }) => {
		const inactiveImporter = { ...IMPORTER, name: 'insert-headers-footers', title: 'Insert Headers and Footers', is_active: false }
		await page.route(isMigrationRequest, route => route.fulfill({ json: { [inactiveImporter.name]: inactiveImporter } }))
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await expect(page.getByRole('option', { name: `${inactiveImporter.title} (Inactive)` })).toBeDisabled()
	})

	test('loads an importer and records it in the URL', async ({ page }) => {
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			return route.fulfill({ json: isSelectedImporterRoute(url) ? SNIPPETS : { [IMPORTER.name]: IMPORTER } })
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await expect(page.getByRole('heading', { name: 'Available snippets (2)' })).toBeVisible()
		expect(new URL(page.url()).searchParams.get('from')).toBe(IMPORTER.name)
	})

	test('shows an empty state when an importer has no snippets', async ({ page }) => {
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			return route.fulfill({ json: isSelectedImporterRoute(url) ? [] : { [IMPORTER.name]: IMPORTER } })
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await expect(page.getByRole('heading', { name: 'No snippets found' })).toBeVisible()
	})

	test('reports an importer fetch error', async ({ page }) => {
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			return route.fulfill(isSelectedImporterRoute(url)
				? { status: 500, json: { message: 'Importer unavailable' } }
				: { json: { [IMPORTER.name]: IMPORTER } })
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await expect(page.getByRole('heading', { name: 'Error loading snippets' })).toBeVisible()
	})

	test('shows automatic tag controls for migrated snippets', async ({ page }) => {
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			return route.fulfill({ json: isSelectedImporterRoute(url) ? SNIPPETS : { [IMPORTER.name]: IMPORTER } })
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await page.getByRole('checkbox', { name: 'Add tag automatically' }).check()
		await expect(page.getByRole('textbox', { name: 'Tag to add to imported snippets' })).toHaveValue(`imported-${IMPORTER.name}`)
	})

	test('selects all migrated snippets before importing', async ({ page }) => {
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			return route.fulfill({ json: isSelectedImporterRoute(url) ? SNIPPETS : { [IMPORTER.name]: IMPORTER } })
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await page.getByRole('button', { name: 'Select All' }).first().click()
		await expect(page.getByRole('button', { name: 'Import Selected (2)' }).first()).toBeEnabled()
		await page.getByRole('button', { name: 'Deselect All' }).first().click()
		await expect(page.getByRole('button', { name: 'Import Selected (0)' }).first()).toBeDisabled()
	})

	test('reports an unsuccessful migration', async ({ page }) => {
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			const path = migrationRoute(url)
			return route.fulfill({
				json: path.endsWith('/import') ? { imported: [] } : isSelectedImporterRoute(url) ? SNIPPETS : { [IMPORTER.name]: IMPORTER }
			})
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await page.getByRole('checkbox', { name: 'Select Legacy Header Code' }).check()
		await page.getByRole('button', { name: 'Import Selected (1)' }).first().click()
		await expect(page.getByRole('heading', { name: 'Error importing snippets' })).toBeVisible()
	})

	test('imports selected snippets with an automatic tag', async ({ page }) => {
		let importRequest: unknown
		await page.route(isMigrationRequest, route => {
			const url = new URL(route.request().url())
			const path = migrationRoute(url)

			if (path.endsWith('/import')) {
				importRequest = route.request().postDataJSON()
				return route.fulfill({ json: { imported: [7] } })
			}

			return route.fulfill({ json: isSelectedImporterRoute(url) ? SNIPPETS : { [IMPORTER.name]: IMPORTER } })
		})
		await page.goto(`${URLS.IMPORT_ADMIN}&tab=migrate`)

		await page.getByRole('combobox', { name: 'Select plugin' }).selectOption(IMPORTER.name)
		await page.getByRole('checkbox', { name: 'Add tag automatically' }).check()
		await page.getByRole('textbox', { name: 'Tag to add to imported snippets' }).fill('migrated')
		await page.getByRole('checkbox', { name: 'Select Legacy Header Code' }).check()
		await page.getByRole('button', { name: 'Import Selected (1)' }).first().click()

		await expect(page.getByRole('heading', { name: '1 snippets imported!' })).toBeVisible()
		expect(importRequest).toMatchObject({ ids: [7], auto_add_tags: true, tag_value: 'migrated' })
	})
})
