import { expect } from '@playwright/test'
import type { Locator } from '@playwright/test'

export const expectCanonicalCheckbox = async (checkbox: Locator): Promise<void> => {
	await expect(checkbox).toHaveCSS('width', '20px')
	await expect(checkbox).toHaveCSS('height', '20px')
	await expect(checkbox).toHaveCSS('box-sizing', 'border-box')
	await expect(checkbox).toHaveCSS('display', 'inline-grid')
	await expect(checkbox).toHaveCSS('vertical-align', 'middle')
	await expect(checkbox).toHaveCSS('border-top-style', 'solid')
	await expect(checkbox).toHaveCSS('border-radius', '5px')

	// Checked boxes fill with the accent colour, while unchecked boxes keep the
	// neutral control border used by the other form controls.
	await expect(checkbox).toHaveCSS(
		'border-top-color',
		await checkbox.isChecked() ? 'rgb(34, 113, 177)' : 'rgb(195, 196, 199)'
	)

	const borderWidth = await checkbox.evaluate(element =>
		Number.parseFloat(getComputedStyle(element).borderTopWidth))
	expect(borderWidth).toBeGreaterThanOrEqual(1)
	expect(borderWidth).toBeLessThanOrEqual(1.5)
}
