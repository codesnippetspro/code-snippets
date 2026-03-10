import React, { useEffect, useMemo } from 'react'
import { fetchQueryParam } from '../../utils/urls'
import { Toolbar } from '../common/Toolbar'
import { CommunityCloud } from './CommunityCloud/CommunityCloud'
import { SnippetsTable } from './SnippetsTable'

export const ManageMenu = () => {
	const subpage = useMemo(() => fetchQueryParam('subpage'), [])

	useEffect(() => {
		if ('cloud-community' === subpage) {
			return
		}

		const screenOptionsForm = document.getElementById('adv-settings')
		const tableOptions = screenOptionsForm?.querySelector<HTMLFieldSetElement>('fieldset.table-options-prefs')
		const columns = Array.from(
			screenOptionsForm?.querySelectorAll<HTMLFieldSetElement>('fieldset.metabox-prefs') ?? []
		).find(fieldset => !fieldset.classList.contains('table-options-prefs'))

		if (screenOptionsForm && tableOptions && columns) {
			screenOptionsForm.insertBefore(tableOptions, columns)
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
