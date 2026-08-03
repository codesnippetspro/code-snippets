import React from 'react'
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { Toolbar } from '../common/Toolbar'
import { InsightsDashboard } from './InsightsDashboard'

export const InsightsMenu: React.FC = () => {
	const summary = window.CODE_SNIPPETS_INSIGHTS

	return (
		<>
			<Toolbar />
			{summary && <div className="code-snippets-insights">
				<WithRestAPIContext>
					<InsightsDashboard summary={summary} />
				</WithRestAPIContext>
			</div>}
		</>
	)
}
