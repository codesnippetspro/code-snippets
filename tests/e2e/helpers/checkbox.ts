import { expect } from '@playwright/test'
import type { Locator } from '@playwright/test'

/**
 * Assert that a checkbox uses the plugin styling rather than the WordPress
 * default, which is the only way these rules can regress: the size proves the
 * shared rules applied at all, and the border colour proves the checked state
 * still resolves.
 */
export const expectCanonicalCheckbox = async (checkbox: Locator): Promise<void> => {
	await expect(checkbox).toHaveCSS('width', '20px')
	await expect(checkbox).toHaveCSS(
		'border-top-color',
		await checkbox.isChecked() ? 'rgb(34, 113, 177)' : 'rgb(195, 196, 199)'
	)
}
