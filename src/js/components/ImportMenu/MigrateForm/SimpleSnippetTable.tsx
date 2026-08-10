import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { ImportCard } from '../common/ImportCard'
import type { UseSelection } from '../../../hooks/useSelection'
import type { ImportableSnippet } from './WithMigrationData'

interface TableNavProps extends SimpleSnippetTableProps {
	which: 'top' | 'bottom'
}

const TableNav: React.FC<TableNavProps> = ({ which, onImport, isImporting, selection }) =>
	<div className={`tablenav ${which}`}>
		<Button onClick={selection.selectAll}>
			{selection.isAllSelected
				? __('Deselect All', 'code-snippets')
				: __('Select All', 'code-snippets')}
		</Button>

		<Button
			primary
			onClick={onImport}
			disabled={0 === selection.selectedItems.size || isImporting}
		>
			{isImporting
				? __('Importing…', 'code-snippets')
				// translators: %d: number of selected snippets.
				: sprintf(__('Import Selected (%d)', 'code-snippets'), selection.selectedItems.size)}
		</Button>
	</div>

export interface SimpleSnippetTableProps {
	selection: UseSelection<ImportableSnippet, number>
	onImport: VoidFunction
	isImporting: boolean
}

export const SimpleSnippetTable: React.FC<SimpleSnippetTableProps> = props => {
	const { selection: { availableItems, selectedItems, selectAll, isAllSelected, toggleItem } } = props

	return (
		<ImportCard className="migrate-snippets-table-card snippets-table-card">
			<header>
				<h3>{sprintf(
					// translators: %d: number of available snippets.
					__('Available snippets (%d)', 'code-snippets'),
					availableItems.length
				)}</h3>
				<p>{__('We found the following snippets:', 'code-snippets')}</p>
			</header>

			<TableNav which="top" {...props} />

			<table className="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col" className="check-column">
							<input
								type="checkbox"
								checked={isAllSelected}
								onChange={selectAll}
								aria-label={__('Select all snippets', 'code-snippets')}
							/>
						</th>
						<th scope="col" className="column-name">{__('Snippet name', 'code-snippets')}</th>
						<th scope="col" className="column-id">{__('ID', 'code-snippets')}</th>
					</tr>
				</thead>
				<tbody>
					{availableItems.map(snippet =>
						<tr key={snippet.table_data.id}>
							<th scope="row" className="check-column">
								<input
									type="checkbox"
									checked={selectedItems.has(snippet.table_data.id)}
									onChange={() => toggleItem(snippet.table_data.id)}
									aria-label={sprintf(
										// translators: %s: snippet name.
										__('Select %s', 'code-snippets'),
										snippet.table_data.title
									)}
								/>
							</th>
							<td className="column-name">{snippet.table_data.title}</td>
							<td className="column-id">{snippet.table_data.id}</td>
						</tr>)}
				</tbody>
			</table>
			<TableNav which="bottom" {...props} />
		</ImportCard>
	)
}
