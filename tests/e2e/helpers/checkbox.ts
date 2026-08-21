import { expect } from '@playwright/test'
import type { Locator } from '@playwright/test'

// Check for both HEX or RGB values, since the browser may return either
// depending on the environment.
const BORDER_COLOR_CHECKED = /^(?:#2271b1|rgb\(34, 113, 177\))$/
const BORDER_COLOR_UNCHECKED = /^(?:#c3c4c7|rgb\(195, 196, 199\))$/

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
		await checkbox.isChecked() ? BORDER_COLOR_CHECKED : BORDER_COLOR_UNCHECKED
	)
}
