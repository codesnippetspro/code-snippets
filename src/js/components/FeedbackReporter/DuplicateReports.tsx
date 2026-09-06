import React from 'react'
import { __ } from '@wordpress/i18n'
import type { DuplicateReport } from '../../types/Feedback'

export interface DuplicateReportsProps {
	reports: DuplicateReport[]
}

export const DuplicateReports: React.FC<DuplicateReportsProps> = ({ reports }) =>
	0 < reports.length &&
		<div className="code-snippets-feedback-duplicates">
			<p>{__('Existing reports that look similar', 'code-snippets')}</p>
			<ul>
				{reports.map(report =>
					<li key={report.url}>
						<a href={report.url} target="_blank" rel="noopener noreferrer">{report.title}</a>
					</li>
				)}
			</ul>
		</div>
