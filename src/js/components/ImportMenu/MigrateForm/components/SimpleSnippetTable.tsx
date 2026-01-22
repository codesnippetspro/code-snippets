import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../../common/Button'
import { ImportCard } from '../../common/ImportCard'
import type { ImportableSnippet } from '../../../../hooks/useImportersAPI'

interface TableNavProps {
	which: 'top' | 'bottom'
	onSelectAll: VoidFunction
	onImport: VoidFunction
	isImporting: boolean
	isAllSelected: boolean
	selectedSnippets: Set<number>
}

const TableNav: React.FC<TableNavProps> = ({ which, onSelectAll, isAllSelected, onImport, selectedSnippets, isImporting }) =>
	<div className={`tablenav ${which}`}>
		<Button onClick={onSelectAll}>
			{isAllSelected
				? __('Deselect All', 'code-snippets')
				: __('Select All', 'code-snippets')}
		</Button>

		<Button
			primary
			onClick={onImport}
			disabled={0 === selectedSnippets.size || isImporting}
		>
			{isImporting
				? __('Importing…', 'code-snippets')
				: __('Import Selected', 'code-snippets')} ({selectedSnippets.size})
		</Button>
	</div>

export interface SimpleSnippetTableProps {
	snippets: ImportableSnippet[]
	selectedSnippets: Set<number>
	onSnippetToggle: (snippetId: number) => void
	onSelectAll: () => void
	onImport: () => void
	isImporting: boolean
}

export const SimpleSnippetTable: React.FC<SimpleSnippetTableProps> = ({
	snippets,
	selectedSnippets,
	onSnippetToggle,
	onSelectAll,
	onImport,
	isImporting
}) => {
	const isAllSelected = selectedSnippets.size === snippets.length && 0 < snippets.length

	return (
		<ImportCard className="migrate-snippets-table-card snippets-table-card">
			<div>
				<header>
					<h2>{__('Available snippets', 'code-snippets')} ({snippets.length})</h2>
					<p>{__('We found the following snippets:', 'code-snippets')}</p>
				</header>

				<TableNav which="top" {...{ onImport, isImporting, selectedSnippets, onSelectAll, isAllSelected }} />
			</div>

			<table className="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" className="check-column">
							<input type="checkbox" checked={isAllSelected} onChange={onSelectAll} />
						</th>
						<th scope="col" className="column-name">{__('Snippet name', 'code-snippets')}</th>
						<th scope="col" className="column-id">{__('ID', 'code-snippets')}</th>
					</tr>
				</thead>
				<tbody>
					{snippets.map(snippet =>
						<tr key={snippet.table_data.id}>
							<th scope="row" className="check-column">
								<input
									type="checkbox"
									checked={selectedSnippets.has(snippet.table_data.id)}
									onChange={() => onSnippetToggle(snippet.table_data.id)}
								/>
							</th>
							<td className="column-name">{snippet.table_data.title}</td>
							<td className="column-id">{snippet.table_data.id}</td>
						</tr>)}
				</tbody>
			</table>

			<TableNav which="bottom" {...{ onImport, isImporting, selectedSnippets, onSelectAll, isAllSelected }} />
		</ImportCard>
	)
}
