/**
 * Relocate the WordPress Screen Options and Help tabs (and their panels)
 * from above the page content into the slot rendered directly below the
 * settings section subnavigation bar.
 */
export const relocateScreenMeta = () => {
	const slot = document.getElementById('snippets-screen-meta-slot')
	const meta = document.getElementById('screen-meta')
	const links = document.getElementById('screen-meta-links')

	if (slot && meta && links) {
		slot.append(meta, links)
	}
}
