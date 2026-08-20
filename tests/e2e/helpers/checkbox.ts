import { expect } from '@playwright/test'
import type { Locator } from '@playwright/test'

/**
 * Assert that a checkbox uses the plugin styling rather than the WordPress
 * default, which is the only way these rules can regress: the size proves the
 * shared rules applied at all, and the border color proves the checked state
 * still resolves.
 */
export const expectCanonicalCheckbox = async (checkbox: Locator): Promise<void> => {
	await expect(checkbox).toHaveCSS('width', '20px')
	await expect(checkbox).toHaveCSS(
		'border-top-color',
		await checkbox.isChecked() ? '#2271b1' : '#c3c4c7'
	)
}
