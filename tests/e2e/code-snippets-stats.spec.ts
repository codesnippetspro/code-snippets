import { expect, test } from '@playwright/test'
import { SnippetsTestHelper } from './helpers/SnippetsTestHelper'
import { URLS } from './helpers/constants'
import { wpCli } from './helpers/wpCli'
import type { Page } from '@playwright/test'

const forceLicenseState = (page: Page, isLicensed: boolean) =>
	page.addInitScript(licensed => {
		let value: { isLicensed?: boolean } | undefined

		Object.defineProperty(window, 'CODE_SNIPPETS', {
			configurable: true,
			get: () => value,
			set: (incoming: { isLicensed?: boolean } | undefined) => {
				value = incoming ? { ...incoming, isLicensed: licensed } : incoming
			}
		})
	}, isLicensed)

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

const clearStatsChartViews = async () => {
	await wpCli(['eval', "delete_option( 'code_snippets_stats' );"])
}

test.describe('Stats screen', () => {
	test.beforeEach(async () => {
		await clearSnippets()
		await clearStatsChartViews()
	})

	test.afterEach(async () => {
		await clearSnippets()
		await clearStatsChartViews()
	})

	test('opens a zero-data dashboard from the upper toolbar', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN)
		await page.locator('.code-snippets-toolbar-upper').getByRole('link', { name: 'Stats', exact: true }).click()
		const activationChart = page.locator('[data-stats-chart="activation"]')
		const totalChart = page.locator('[data-stats-chart="total"]')

		await expect(page).toHaveURL(/page=code-snippets-stats/)
		await expect(page.getByRole('heading', { name: 'Stats' })).toBeVisible()
		await expect(page.locator('.stats-chart-card').first()).toHaveAttribute('data-stats-chart', 'total')
		expect(await page.locator('[data-stats-chart]').evaluateAll(charts =>
			charts.map(chart => chart.getAttribute('data-stats-chart')))).toEqual(
			['total', 'type', 'activation', 'conditions', 'location', 'tags'])
		await expect(totalChart.locator('.stats-number-chart-value')).toHaveText('0')
		await expect(totalChart.locator('.stats-number-chart-label')).toHaveText('Total snippets')
		await expect(totalChart.locator('.stats-chart-view-toggle')).toHaveCount(0)
		await expect(totalChart.locator('.stats-bar-chart')).toHaveCount(0)
		await expect(totalChart.locator('.stats-pie-chart')).toHaveCount(0)
		await expect(page.getByRole('heading', { name: 'Snippet type' })).toBeVisible()
		await expect(page.getByText('PHP', { exact: true })).toBeVisible()
		await expect(page.getByText('Conditions', { exact: true })).toBeVisible()
		await expect(activationChart.locator('.stats-pie-chart.is-empty')).toBeVisible()
		await expect(activationChart.locator('.stats-pie-chart-legend')).toContainText('Active')
		await expect(activationChart.locator('.stats-pie-chart-legend')).toContainText('Inactive')
		await expect(page.getByRole('link', { name: 'Create new Snippet' })).toHaveCount(0)
	})

	test('blurs condition usage and links to Pro when unlicensed', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-stats'))
		const conditionUsage = page.locator('.stats-chart-lock')
		const pieContent = page.locator('[data-stats-chart="conditions"] .stats-pie-chart-content')

		await expect(conditionUsage).toHaveClass(/is-locked/)
		await expect(pieContent).toHaveCSS('filter', 'blur(10px)')
		const goProLink = conditionUsage.getByRole('link', { name: 'Go Pro' })
		await expect(goProLink).toHaveAttribute('href', 'https://codesnippets.pro/pricing/')
		const [contentBox, linkBox] = await Promise.all([pieContent.boundingBox(), goProLink.boundingBox()])
		expect(contentBox).not.toBeNull()
		expect(linkBox).not.toBeNull()
		const contentCenterX = (contentBox?.x ?? 0) + (contentBox?.width ?? 0) / 2
		const contentCenterY = (contentBox?.y ?? 0) + (contentBox?.height ?? 0) / 2
		const linkCenterX = (linkBox?.x ?? 0) + (linkBox?.width ?? 0) / 2
		const linkCenterY = (linkBox?.y ?? 0) + (linkBox?.height ?? 0) / 2

		expect(Math.abs(linkCenterX - contentCenterX)).toBeLessThan(2)
		expect(Math.abs(linkCenterY - contentCenterY)).toBeLessThan(2)
	})

	test('blurs condition usage in list view when unlicensed', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-stats'))
		const conditionsChart = page.locator('[data-stats-chart="conditions"]')

		await conditionsChart.getByRole('button', { name: 'List view' }).click()
		await expect(conditionsChart.locator('.stats-bar-chart')).toHaveCSS('filter', 'blur(10px)', { timeout: 3000 })
		await expect(conditionsChart.getByRole('link', { name: 'Go Pro' })).toHaveAttribute('href', 'https://codesnippets.pro/pricing/')
	})

	test('shows condition usage without a Pro overlay when licensed', async ({ page }) => {
		await forceLicenseState(page, true)
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-stats'))
		const conditionUsage = page.locator('.stats-chart-lock')
		const pieContent = page.locator('[data-stats-chart="conditions"] .stats-pie-chart-content')

		await expect(conditionUsage).not.toHaveClass(/is-locked/)
		await expect(pieContent).toHaveCSS('filter', 'none')
		await expect(conditionUsage.getByRole('link', { name: 'Go Pro' })).toHaveCount(0)
	})

	test('shows current snippet distributions', async ({ page }) => {
		const conditionId = await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Active Conditions',
			active: true,
			type: 'cond'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Active PHP',
			active: true,
			conditionId,
			type: 'php'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Inactive HTML',
			active: false,
			type: 'html'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Active CSS',
			active: true,
			type: 'css'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Inactive JavaScript',
			active: false,
			type: 'js'
		})
		await page.goto(URLS.STATS_ADMIN)
		const activationPie = page.locator('[data-stats-chart="activation"] .stats-pie-chart')
		const conditionsChart = page.locator('[data-stats-chart="conditions"]')

		await expect(page.getByRole('heading', { name: 'Stats' })).toBeVisible()
		await expect(page.locator('[data-stats-chart="total"] .stats-number-chart-value')).toHaveText('5')
		await expect(page.getByRole('heading', { name: 'Snippet type' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Activation status' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Condition usage' })).toBeVisible()
		await expect(page.getByRole('heading', { name: 'Location' })).toBeVisible()
		await expect(page.getByText('Conditions', { exact: true })).toBeVisible()
		expect(await activationPie.evaluate(element => element.style.background)).toContain('60%')
		await expect(conditionsChart).toHaveAttribute('data-view', 'pie')

		const conditionLegend = conditionsChart.locator('.stats-pie-chart-legend')
		const withConditions = conditionLegend.locator('li').filter({
			hasText: /^Uses conditions/
		})
		const withoutConditions = conditionLegend.locator('li').filter({
			hasText: /^Does not use conditions/
		})

		await expect(withConditions.locator('span')).toHaveText('Uses conditions')
		await expect(withConditions.locator('strong')).toHaveText('1')
		await expect(withoutConditions.locator('span')).toHaveText('Does not use conditions')
		await expect(withoutConditions.locator('strong')).toHaveText('4')
	})

	test('shows snippet scope counts in the location chart', async ({ page }) => {
		await SnippetsTestHelper.createSnippetViaCli({ name: 'Stats Global One', active: true })
		await SnippetsTestHelper.createSnippetViaCli({ name: 'Stats Global Two', active: true })
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Admin Scope',
			active: true,
			scope: 'admin'
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Front-end Scope',
			active: true,
			scope: 'front-end'
		})

		await page.goto(URLS.STATS_ADMIN)
		const locationChart = page.locator('[data-stats-chart="location"]')

		for (const [label, count] of [
			['Run everywhere', '2'],
			['Only run in administration area', '1'],
			['Only run on site front-end', '1']
		]) {
			const entry = locationChart.locator('li').filter({ hasText: label })

			await expect(entry).toHaveCount(1)
			await expect(entry.locator('strong')).toHaveText(count)
		}
	})

	test('switches used tags between bar and cloud views', async ({ page }) => {
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Shared and Alpha Tags',
			active: true,
			tags: ['Shared', 'Alpha']
		})
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Shared Tag',
			active: true,
			tags: ['Shared']
		})

		await page.goto(URLS.STATS_ADMIN)
		const tagsChart = page.locator('[data-stats-chart="tags"]')

		await expect(page.getByRole('heading', { name: 'Tags' })).toBeVisible()
		await expect(tagsChart).toHaveAttribute('data-view', 'bar')
		await expect(tagsChart.locator('.stats-bar-chart')).toContainText('Shared')
		await expect(tagsChart.getByRole('button', { name: 'Tags cloud view' })).toBeVisible()

		const response = page.waitForResponse(request =>
			'POST' === request.request().method() && request.url().includes('/preferences/stats-chart-views')
		)
		await tagsChart.getByRole('button', { name: 'Tags cloud view' }).click()
		await response

		await expect(tagsChart).toHaveAttribute('data-view', 'cloud')
		const tagCloud = tagsChart.locator('.stats-tags-cloud')
		const sharedTag = tagCloud.locator('li').filter({ hasText: /^Shared/ })
		const alphaTag = tagCloud.locator('li').filter({ hasText: /^Alpha/ })

		await expect(sharedTag).toHaveText('Shared (2 snippets)')
		await expect(alphaTag).toHaveText('Alpha (1 snippet)')
		expect(await sharedTag.evaluate(element => Number.parseFloat(getComputedStyle(element).fontSize)))
			.toBeGreaterThan(await alphaTag.evaluate(element => Number.parseFloat(getComputedStyle(element).fontSize)))
		await expect(tagsChart.locator('.stats-bar-chart')).toHaveCount(0)

		await page.reload()
		await expect(tagsChart).toHaveAttribute('data-view', 'cloud')
	})

	test('links chart entries to their filtered snippet lists', async ({ page, baseURL }) => {
		await SnippetsTestHelper.createSnippetViaCli({
			name: 'Stats Tagged Snippet',
			active: true,
			tags: ['sample']
		})

		await page.goto(URLS.STATS_ADMIN)

		const manageUrl = (query: string) => new URL(`${URLS.SNIPPETS_ADMIN}${query}`, baseURL).toString()

		const typeChart = page.locator('[data-stats-chart="type"]')
		const activationChart = page.locator('[data-stats-chart="activation"]')
		const tagsChart = page.locator('[data-stats-chart="tags"]')

		for (const [label, type] of [
			['PHP', 'php'],
			['HTML', 'html'],
			['CSS', 'css'],
			['JS', 'js'],
			['Conditions', 'cond']
		]) {
			await expect(typeChart.getByRole('link', { name: label })).toHaveAttribute(
				'href', manageUrl(`&subpage=snippets&type=${type}`))
		}

		for (const status of ['active', 'inactive']) {
			await expect(activationChart.getByRole('link', { name: new RegExp(`^${status}$`, 'i') })).toHaveAttribute(
				'href', manageUrl(`&subpage=snippets&status=${status}`))
		}

		const tagLink = tagsChart.getByRole('link', { name: 'sample' })
		const textColor = await page.evaluate(() => {
			const element = document.body.appendChild(document.createElement('span'))
			element.style.color = 'var(--cs-color-text)'
			const color = getComputedStyle(element).color
			element.remove()
			return color
		})

		await expect(tagLink).toHaveAttribute('href', manageUrl('&tag=sample'))
		await expect(tagLink).toHaveCSS('color', textColor)
		await expect(tagLink).toHaveCSS('text-decoration-line', 'none')
	})

	test('opens the matching filtered list from a Stats chart entry', async ({ page }) => {
		const name = 'Stats Chart Link Snippet'
		await SnippetsTestHelper.createSnippetViaCli({
			name,
			active: true,
			tags: ['chart-link']
		})
		await page.goto(URLS.STATS_ADMIN)
		await page.locator('[data-stats-chart="tags"]').getByRole('link', { name: 'chart-link' }).click()

		await expect(page).toHaveURL(/page=snippets.*tag=chart-link/)
		await expect(page.locator('.wp-list-table tbody tr').filter({ hasText: name })).toBeVisible()
	})

	test('switches and restores each Stats chart view', async ({ page }) => {
		await page.goto(URLS.SNIPPETS_ADMIN.replace('page=snippets', 'page=code-snippets-stats'))

		const typeChart = page.locator('[data-stats-chart="type"]')
		const activationChart = page.locator('[data-stats-chart="activation"]')
		const conditionsChart = page.locator('[data-stats-chart="conditions"]')
		const locationChart = page.locator('[data-stats-chart="location"]')

		await expect(typeChart).toHaveAttribute('data-view', 'bar')
		await expect(typeChart.locator('.stats-bar-chart')).toBeVisible()
		await expect(activationChart).toHaveAttribute('data-view', 'pie')
		await expect(activationChart.locator('.stats-pie-chart-legend')).toBeVisible()
		await expect(conditionsChart).toHaveAttribute('data-view', 'pie')
		await expect(conditionsChart.locator('.stats-pie-chart-legend')).toBeVisible()
		await expect(locationChart).toHaveAttribute('data-view', 'bar')

		const switchView = async (chart: typeof typeChart, view: 'Pie' | 'Bar') => {
			const response = page.waitForResponse(request =>
				'POST' === request.request().method() && request.url().includes('/preferences/stats-chart-views')
			)

			await chart.getByRole('button', { name: 'Pie' === view ? 'Chart view' : 'List view' }).click()
			await response
		}

		await switchView(typeChart, 'Pie')
		await expect(typeChart).toHaveAttribute('data-view', 'pie')
		await expect(typeChart.locator('.stats-pie-chart')).toBeVisible()
		await expect(typeChart.locator('.stats-pie-chart-legend')).toContainText('PHP')

		await switchView(activationChart, 'Bar')
		await expect(activationChart).toHaveAttribute('data-view', 'bar')
		await expect(activationChart.locator('.stats-bar-chart')).toContainText('Active')
		await expect(activationChart.locator('.stats-bar-chart')).toContainText('Inactive')

		await switchView(conditionsChart, 'Bar')
		await expect(conditionsChart).toHaveAttribute('data-view', 'bar')
		await expect(conditionsChart.locator('.stats-bar-chart')).toContainText('Uses conditions')
		await expect(conditionsChart.locator('.stats-bar-chart')).toContainText('Does not use conditions')

		await switchView(locationChart, 'Pie')
		await expect(locationChart).toHaveAttribute('data-view', 'pie')
		await expect(locationChart.locator('.stats-pie-chart-legend')).toHaveCount(1)

		await page.reload()
		await expect(typeChart).toHaveAttribute('data-view', 'pie')
		await expect(activationChart).toHaveAttribute('data-view', 'bar')
		await expect(conditionsChart).toHaveAttribute('data-view', 'bar')
		await expect(locationChart).toHaveAttribute('data-view', 'pie')
	})

	test('restores a chart view when saving the preference fails', async ({ page }) => {
		await page.goto(URLS.STATS_ADMIN)
		const conditionsChart = page.locator('[data-stats-chart="conditions"]')

		await expect(conditionsChart).toHaveAttribute('data-view', 'pie')

		await page.route('**/preferences/stats-chart-views', async route => {
			await route.fulfill({ status: 500, body: JSON.stringify({ message: 'Save failed' }) })
		})

		await conditionsChart.getByRole('button', { name: 'List view' }).click()

		await expect(conditionsChart).toHaveAttribute('data-view', 'pie')
	})

	test('keeps the latest chart views when an earlier save fails', async ({ page }) => {
		await page.goto(URLS.STATS_ADMIN)
		const typeChart = page.locator('[data-stats-chart="type"]')
		const activationChart = page.locator('[data-stats-chart="activation"]')
		let rejectFirstRequest: (() => void) | undefined
		let signalFirstRequest: () => void
		const firstRequestStarted = new Promise<void>(resolve => {
			signalFirstRequest = resolve
		})

		await page.route('**/preferences/stats-chart-views', async route => {
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

		await typeChart.getByRole('button', { name: 'Chart view' }).click()
		await firstRequestStarted

		const successfulResponse = page.waitForResponse(response =>
			'POST' === response.request().method() && 200 === response.status()
		)
		await activationChart.getByRole('button', { name: 'List view' }).click()
		await successfulResponse

		if (undefined === rejectFirstRequest) {
			throw new Error('The first chart preference request was not intercepted.')
		}

		rejectFirstRequest()

		await expect(typeChart).toHaveAttribute('data-view', 'pie')
		await expect(activationChart).toHaveAttribute('data-view', 'bar')
	})
})
