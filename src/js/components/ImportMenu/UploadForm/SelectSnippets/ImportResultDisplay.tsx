import React from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { ImportCard } from '../../common/ImportCard'
import type { ReactNode } from 'react'

export interface ImportResult {
	success: boolean
	message: string
	imported?: number
	warnings?: string[]
}

export interface ImportResultDisplayProps {
	success: boolean
	message: ReactNode
	warnings?: string[]
}

export const ImportResultDisplay: React.FC<ImportResultDisplayProps> = ({ success, message, warnings }) =>
	<ImportCard className="import-result-display-card">
		<div className={`import-result import-result-${success ? 'success' : 'failure'}`}>
			<div className="import-result-icon">
				<span>{success ? '✓' : '✕'}</span>
			</div>

			<div>
				<h3>{success
					? __('Import Successful!', 'code-snippets')
					: __('Import Failed', 'code-snippets')}</h3>

				<p className="import-result-message">{message}</p>

				{success &&
					<p className="import-result-link">
						{createInterpolateElement(
							__('Go to <a>All Snippets</a> to activate your imported snippets.', 'code-snippets'),
							{ a: <a href={window.CODE_SNIPPETS?.urls.manage} /> }
						)}
					</p>}

				{warnings && 0 < warnings.length &&
					<div className="import-result-warnings">
						<h4>{__('Warnings:', 'code-snippets')}</h4>
						<ul>
							{warnings.map(warning => <li key={warning}>{warning}</li>)}
						</ul>
					</div>}
			</div>
		</div>
	</ImportCard>
