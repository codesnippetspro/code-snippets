import React from 'react'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { ScreenMetaSlot } from '../common/ScreenMetaSlot'
import { Toolbar } from '../common/Toolbar'
import { InsightsDashboard } from './InsightsDashboard'

export const InsightsMenu: React.FC = () => {
	const summary = window.CODE_SNIPPETS_INSIGHTS

	return (
		<>
			<Toolbar />

			<ScreenMetaSlot />

			{summary && (
				<div className="code-snippets-insights">
					<WithRestAPIContext>
						<InsightsDashboard summary={summary} />
					</WithRestAPIContext>
				</div>)}
		</>
	)
}
