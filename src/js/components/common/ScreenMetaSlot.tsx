import React, { useEffect } from 'react'

const SLOT_ID = 'snippets-screen-meta-slot'

export interface ScreenMetaSlotProps {
	hidden?: boolean
}

/**
 * Relocates the WordPress Screen Options and Help tabs (and their panels)
 * from above the page content into this slot, so they appear directly below
 * the page's subnavigation bar. The slot div is rendered empty by React and
 * never reconciled with children, so adopting the foreign nodes is safe.
 * The nodes are returned to the top of the page when the slot unmounts.
 *
 * Stays mounted (with `hidden`) rather than being conditionally rendered by
 * the caller: unmounting hands the adopted nodes back to their native,
 * visible position at the top of the page instead of hiding them.
 */
export const ScreenMetaSlot: React.FC<ScreenMetaSlotProps> = ({ hidden = false }) => {
	useEffect(() => {
		const slot = document.getElementById(SLOT_ID)
		const meta = document.getElementById('screen-meta')
		const links = document.getElementById('screen-meta-links')

		if (!slot || !meta || !links) {
			return undefined
		}

		const parent = meta.parentElement
		slot.append(meta, links)

		return () => {
			parent?.prepend(meta, links)
		}
	}, [])

	return <div id={SLOT_ID} className="snippets-screen-meta-slot" hidden={hidden} />
}
