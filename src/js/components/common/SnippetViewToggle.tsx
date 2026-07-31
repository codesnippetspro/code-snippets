import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { CardViewIcon, TableViewIcon } from './icons/ViewIcons'
import type { SnippetView } from '../../types/SnippetView'

export interface SnippetViewToggleProps {
	snippetView: SnippetView
	setSnippetView: (view: SnippetView) => void
	className?: string
}

export const SnippetViewToggle: React.FC<SnippetViewToggleProps> = ({ snippetView, setSnippetView, className }) =>
	<div
		className={classnames('snippet-view-toggle', className)}
		role="group"
		aria-label={__('Snippet view', 'code-snippets')}
	>
		<button
			type="button"
			className={classnames('snippet-view-toggle-option', { 'active-view': 'card' === snippetView })}
			aria-pressed={'card' === snippetView}
			title={__('Switch to card view', 'code-snippets')}
			onClick={() => setSnippetView('card')}
		>
			<CardViewIcon aria-hidden="true" />
			<span className="screen-reader-text">{__('Card view', 'code-snippets')}</span>
		</button>

		<button
			type="button"
			className={classnames('snippet-view-toggle-option', { 'active-view': 'table' === snippetView })}
			aria-pressed={'table' === snippetView}
			title={__('Switch to table view', 'code-snippets')}
			onClick={() => setSnippetView('table')}
		>
			<TableViewIcon aria-hidden="true" />
			<span className="screen-reader-text">{__('Table view', 'code-snippets')}</span>
		</button>
	</div>
