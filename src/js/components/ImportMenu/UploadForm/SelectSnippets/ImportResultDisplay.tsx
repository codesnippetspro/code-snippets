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
	step: 'upload' | 'select'
}

export interface ImportResultDisplayProps {
	success: boolean
	message: ReactNode
	warnings?: string[]
	step: 'upload' | 'select'
}

export const ImportResultDisplay: React.FC<ImportResultDisplayProps> = ({ success, message, warnings, step }) =>
	<ImportCard className="import-result-display-card">
		<div className={`import-result import-result-${success ? 'success' : 'failure'}`}>
			<div className="import-result-icon" aria-hidden="true">
				<span>{success ? '✓' : '✕'}</span>
			</div>

			<div>
				<h2>
					{'upload' === step && (success
						? __('File upload successful', 'code-snippets')
						: __('File upload error', 'code-snippets'))}

					{'select' === step && (success
						? __('Import successful', 'code-snippets')
						: __('Import error', 'code-snippets'))}
				</h2>

				<p className="import-result-message">{message}</p>

				{success && (
					<p className="import-result-link">
						{createInterpolateElement(
							__('Go to <a>All Snippets</a> to activate your imported snippets.', 'code-snippets'),
							{
								// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
								a: <a href={window.CODE_SNIPPETS?.urls.manage} />
							}
						)}
					</p>)}

				{warnings && 0 < warnings.length && (
					<div className="import-result-warnings">
						<h3>{__('Warnings:', 'code-snippets')}</h3>
						<ul>
							{warnings.map(warning => <li key={warning}>{warning}</li>)}
						</ul>
					</div>)}
			</div>
		</div>
	</ImportCard>
