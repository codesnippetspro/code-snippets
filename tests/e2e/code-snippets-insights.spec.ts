import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { URLS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'

const clearSnippets = async () => {
	const php = `
		global $wpdb;
		$tables = [ \\Code_Snippets\\code_snippets()->db->get_table_name( false ) ];

		if ( is_multisite() ) {
			$tables[] = \\Code_Snippets\\code_snippets()->db->get_table_name( true );
		}

		foreach ( $tables as $table ) {
			$wpdb->query( "DELETE FROM {$table}" );
		}
	`

	await wpCli(['eval', php])
}

const clearInsightsChartViews = async () => {
	await wpCli(['eval', "delete_option( 'code_snippets_insights_preferences' );"])
}

test.describe('Insights screen', () => {
	test.beforeEach(async () => {
		await clearSnippets()
		await clearInsightsChartViews()
	})

	test.afterEach(async () => {
		await clearSnippets()
		await clearInsightsChartViews()
	})

	test('opens a zero-data dashboard from the upper toolbar', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.getByRole('link', { name: 'Insights', exact: true }).click()
		const activationChart = page.locator('[data-insights-chart="activation"]')

		await expect(page).toHaveURL(/page=code-snippets-insights/)
		await expect(page.getByRole('heading', { name: 'Insights' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Snippet type' })).toBeVisible()
		await expect(page.getByText('PHP', { exact: true })).toBeVisible()
		await expect(page.getByText('Conditions', { exact: true })).toBeVisible()
		await expect(activationChart.locator('.insights-pie-chart.is-empty')).toBeVisible()
		await expect(activationChart.locator('.insights-pie-chart-legend')).toContainText('Active')
		await expect(activationChart.locator('.insights-pie-chart-legend')).toContainText('Inactive')
		await expect(page.getByRole('link', { name: 'Create new Snippet' })).toHaveCount(0)
	})

	test('shows current snippet distributions', async ({ page }) => {
		const conditionId = await SnippetsTestHelper.createSnippetViaCli({
			name: 'Insights Active Conditions',
			active: true,
			type: 'cond'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Insights Active PHP',
			active: true,
			conditionId,
			type: 'php'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Insights Inactive HTML',
			active: false,
			type: 'html'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Insights Active CSS',
			active: true,
			type: 'css'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Insights Inactive JavaScript',
			active: false,
			type: 'js'
		})
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-insights'))
		const activationPie = page.locator('[data-insights-chart="activation"] .insights-pie-chart')

		await expect(page.getByRole('heading', { name: 'Insights' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Snippet type' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Activation status' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Location' })).toBeVisible()
		await expect(page.getByText('Conditions', { exact: true })).toBeVisible()
		expect(await activationPie.evaluate(element => element.style.background)).toContain('60%')
	})

	test('switches and restores each Insights chart view', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-insights'))

		const typeChart = page.locator('[data-insights-chart="type"]')
		const activationChart = page.locator('[data-insights-chart="activation"]')
		const locationChart = page.locator('[data-insights-chart="location"]')

		await expect(typeChart).toHaveAttribute('data-view', 'bar')
		await expect(typeChart.locator('.insights-bar-chart')).toBeVisible()
		await expect(activationChart).toHaveAttribute('data-view', 'pie')
		await expect(activationChart.locator('.insights-pie-chart-legend')).toBeVisible()
		await expect(locationChart).toHaveAttribute('data-view', 'bar')

		const switchView = async (chart: typeof typeChart, view: 'Pie' | 'Bar') => {
			const response = page.waitForResponse(request =>
				'POST' === request.request().method() && request.url().includes('/preferences/insights-chart-views')
			)

			await chart.getByRole('button', { name: `${view} chart view` }).click()
			await response
		}

		await switchView(typeChart, 'Pie')
		await expect(typeChart).toHaveAttribute('data-view', 'pie')
		await expect(typeChart.locator('.insights-pie-chart')).toBeVisible()
		await expect(typeChart.locator('.insights-pie-chart-legend')).toContainText('PHP')

		await switchView(activationChart, 'Bar')
		await expect(activationChart).toHaveAttribute('data-view', 'bar')
		await expect(activationChart.locator('.insights-bar-chart')).toContainText('Active')
		await expect(activationChart.locator('.insights-bar-chart')).toContainText('Inactive')

		await switchView(locationChart, 'Pie')
		await expect(locationChart).toHaveAttribute('data-view', 'pie')
		await expect(locationChart.locator('.insights-pie-chart-legend')).toHaveCount(1)

		await page.reload()
		await expect(typeChart).toHaveAttribute('data-view', 'pie')
		await expect(activationChart).toHaveAttribute('data-view', 'bar')
		await expect(locationChart).toHaveAttribute('data-view', 'pie')
	})

	test('restores a chart view when saving the preference fails', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-insights'))
		const typeChart = page.locator('[data-insights-chart="type"]')

		await expect(typeChart).toHaveAttribute('data-view', 'bar')

		await page.route('**/preferences/insights-chart-views', async route => {
			await route.fulfill({ status: 500, body: JSON.stringify({ message: 'Save failed' }) })
		})

		await typeChart.getByRole('button', { name: 'Pie chart view' }).click()

		await expect(typeChart).toHaveAttribute('data-view', 'bar')
	})

	test('keeps the latest chart views when an earlier save fails', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-insights'))
		const typeChart = page.locator('[data-insights-chart="type"]')
		const activationChart = page.locator('[data-insights-chart="activation"]')
		let rejectFirstRequest: (() => void) | undefined
		let signalFirstRequest: () => void
		const firstRequestStarted = new Promise<void>(resolve => {
			signalFirstRequest = resolve
		})

		await page.route('**/preferences/insights-chart-views', async route => {
			const { views } = <{ views: { type: string, activation: string } }> route.request().postDataJSON()

			if ('pie' === views.type && 'pie' === views.activation) {
				await new Promise<void>(resolve => {
					rejectFirstRequest = resolve
					signalFirstRequest()
				})
				await route.fulfill({ status: 500, body: JSON.stringify({ message: 'Save failed' }) })
				return
			}

			await route.fulfill({ status: 200, body: JSON.stringify({ views }) })
		})

		await typeChart.getByRole('button', { name: 'Pie chart view' }).click()
		await firstRequestStarted

		const successfulResponse = page.waitForResponse(response =>
			'POST' === response.request().method() && 200 === response.status()
		)
		await activationChart.getByRole('button', { name: 'Bar chart view' }).click()
		await successfulResponse

		if (undefined === rejectFirstRequest) {
			throw new Error('The first chart preference request was not intercepted.')
		}

		rejectFirstRequest()

		await expect(typeChart).toHaveAttribute('data-view', 'pie')
		await expect(activationChart).toHaveAttribute('data-view', 'bar')
	})
})
