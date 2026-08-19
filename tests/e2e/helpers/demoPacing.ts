import type { Page } from '@playwright/test'

export interface CalloutStep {
	title: string
	/** Milliseconds from the start of the walkthrough until this step appeared. */
	at: number
	/** Milliseconds the step stayed on screen. */
	shownFor: number
}

const SAMPLE_INTERVAL = 150

/**
 * Watch a walkthrough from play through to its closing panel, recording how
 * long each step of commentary stayed on screen.
 */
export const measureCalloutSteps = async (page: Page): Promise<CalloutStep[]> => {
	const seen: { title: string, at: number }[] = []
	const started = Date.now()

	while (0 === await page.locator('.demo-upsell').count()) {
		const title = await page.locator('.demo-callout__title').textContent().catch(() => null)

		if (title && seen.at(-1)?.title !== title) {
			seen.push({ title, at: Date.now() - started })
		}

		await page.waitForTimeout(SAMPLE_INTERVAL)
	}

	const total = Date.now() - started

	return seen.map((step, index) => ({
		...step,
		shownFor: (index + 1 < seen.length ? seen[index + 1].at : total) - step.at
	}))
}
