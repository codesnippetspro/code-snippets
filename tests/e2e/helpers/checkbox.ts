import { expect } from '@playwright/test'
import type { Locator } from '@playwright/test'

export const expectCanonicalCheckbox = async (checkbox: Locator): Promise<void> => {
	await expect(checkbox).toHaveCSS('width', '20px')
	await expect(checkbox).toHaveCSS('height', '20px')
	await expect(checkbox).toHaveCSS('box-sizing', 'border-box')
	await expect(checkbox).toHaveCSS('border-top-style', 'solid')
	await expect(checkbox).toHaveCSS('border-top-color', 'rgb(34, 113, 177)')
	await expect(checkbox).toHaveCSS('border-radius', '5px')

	const borderWidth = await checkbox.evaluate(element =>
		Number.parseFloat(getComputedStyle(element).borderTopWidth))
	expect(borderWidth).toBeGreaterThanOrEqual(1)
	expect(borderWidth).toBeLessThanOrEqual(1.5)
}
