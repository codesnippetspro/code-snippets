import React, { useEffect } from 'react'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { revealLocationHash } from '../../utils/urls'
import { ScreenMetaSlot } from '../common/ScreenMetaSlot'
import { Toolbar } from '../common/Toolbar'
import { StatsDashboard } from './StatsDashboard'

export const StatsMenu: React.FC = () => {
	const summary = window.CODE_SNIPPETS_STATS

	useEffect(revealLocationHash, [])

	return (
		<>
			<Toolbar />

			<ScreenMetaSlot />

			{summary && (
				<div className="code-snippets-stats">
					<WithRestAPIContext>
						<StatsDashboard summary={summary} />
					</WithRestAPIContext>
				</div>)}
		</>
	)
}
