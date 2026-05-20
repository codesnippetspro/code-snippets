import React, { useEffect, useMemo } from 'react'
import { fetchQueryParam } from '../../utils/urls'
import { Toolbar } from '../common/Toolbar'
import { CommunityCloud } from './CommunityCloud/CommunityCloud'
import { SnippetsTable } from './SnippetsTable'

const repositionTableOptionsSettings = () => {
	const screenOptionsForm = document.getElementById('adv-settings')
	const tableOptions = screenOptionsForm?.querySelector<HTMLFieldSetElement>('fieldset.table-options-prefs')
	// Locate the first column-visibility fieldset that is NOT the table-options one.
	// This relies on WordPress core rendering #adv-settings with .metabox-prefs fieldsets.
	// Verified against WP 6.5+ (core/Screen_Options). If WP changes this structure the
	// reordering will silently no-op, which is acceptable — it is a cosmetic improvement only.
	const columns = Array.from(
		screenOptionsForm?.querySelectorAll<HTMLFieldSetElement>('fieldset.metabox-prefs') ?? []
	).find(fieldset => !fieldset.classList.contains('table-options-prefs'))

	if (screenOptionsForm && tableOptions && columns) {
		screenOptionsForm.insertBefore(tableOptions, columns)
	}
}

export const ManageMenu = () => {
	const subpage = useMemo(() => fetchQueryParam('subpage'), [])

	useEffect(() => {
		if ('cloud-community' !== subpage) {
			repositionTableOptionsSettings()
		}
	}, [subpage])

	return (
		<>
			<Toolbar />

			{'cloud-community' === subpage
				? <CommunityCloud />
				: <SnippetsTable />}
		</>
	)
}
