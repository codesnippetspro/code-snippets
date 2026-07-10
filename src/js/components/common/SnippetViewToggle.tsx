import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { SnippetView } from '../../types/SnippetView'

export interface SnippetViewToggleProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
	className?: string
}

/**
 * Switch between the table and card snippet views, styled like the
 * snippet activation toggle. Checked means the card view is active.
 */
export const SnippetViewToggle: React.FC<SnippetViewToggleProps> = ({ snippetView, setSnippetView, className }) =>
	<div className={classnames('snippet-view-toggle', className)}>
		<span id="snippet-view-toggle-table-label" aria-hidden="true">
			{__('Table', 'code-snippets')}
		</span>

		<input
			id="snippet-view-toggle-switch"
			type="checkbox"
			role="switch"
			className="switch"
			checked={'card' === snippetView}
			aria-checked={'card' === snippetView}
			title={'card' === snippetView
				? __('Switch to table view', 'code-snippets')
				: __('Switch to card view', 'code-snippets')}
			aria-label={__('Display snippets as cards instead of a table', 'code-snippets')}
			onChange={event => setSnippetView(event.target.checked ? 'card' : 'table')}
		/>

		<span id="snippet-view-toggle-card-label" aria-hidden="true">
			{__('Cards', 'code-snippets')}
		</span>
	</div>
