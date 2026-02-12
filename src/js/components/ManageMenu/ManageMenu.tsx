import React, { useMemo } from 'react'
import { fetchQueryParam } from '../../utils/urls'
import { Toolbar } from '../common/Toolbar'
import { CommunityCloud } from './CommunityCloud/CommunityCloud'
import { SnippetsTable } from './SnippetsTable'

export const ManageMenu = () => {
	const subpage = useMemo(() => fetchQueryParam('subpage'), [])

	return (
		<>
			<Toolbar />

			{'cloud-community' === subpage
				? <CommunityCloud />
				: <SnippetsTable />}
		</>
	)
}
