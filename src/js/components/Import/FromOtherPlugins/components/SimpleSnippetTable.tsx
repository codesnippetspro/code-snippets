import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../../common/Button'
import type { ImportableSnippet } from '../../../../hooks/useImportersAPI'
import { ImportCard } from '../../shared'

interface SimpleSnippetTableProps {
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
	const isAllSelected = selectedSnippets.size === snippets.length && snippets.length > 0

	return (
		<ImportCard className="snippets-table-container">
			<div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
				<div>
					<h2 style={{ margin: '0' }}>{__('Available Snippets', 'code-snippets')} ({snippets.length})</h2>
					<p style={{ margin: '0.5em 0 1em 0' }}>{__('We found the following snippets.', 'code-snippets')}</p>
				</div>
				<div>
					<Button onClick={onSelectAll} style={{ marginRight: '10px' }}>
						{isAllSelected
							? __('Deselect All', 'code-snippets')
							: __('Select All', 'code-snippets')
						}
					</Button>
					<Button 
						primary
						onClick={onImport}
						disabled={selectedSnippets.size === 0 || isImporting}
					>
						{isImporting 
							? __('Importing...', 'code-snippets')
							: __('Import Selected', 'code-snippets')} ({selectedSnippets.size})
					</Button>
				</div>
			</div>

			<table className="wp-list-table widefat fixed striped" style={{ borderRadius: '5px' }}>
				<thead>
					<tr>
						<th scope="col" className="check-column" style={{ padding: '8px 0' }}>
							<input
								type="checkbox"
								checked={isAllSelected}
								onChange={onSelectAll}
							/>
						</th>
						<th scope="col">{__('Snippet Name', 'code-snippets')}</th>
						<th scope="col" style={{ textAlign: 'end', width: '50px' }}>{__('ID', 'code-snippets')}</th>
					</tr>
				</thead>
				<tbody>
					{snippets.map(snippet => (
						<tr key={snippet.table_data.id}>
							<th scope="row" className="check-column">
								<input
									type="checkbox"
									checked={selectedSnippets.has(snippet.table_data.id)}
									onChange={() => onSnippetToggle(snippet.table_data.id)}
								/>
							</th>
							<td>{snippet.table_data.title}</td>
							<td style={{ textAlign: 'end', width: '50px' }}>{snippet.table_data.id}</td>
						</tr>
					))}
				</tbody>
			</table>
			
			<div style={{ textAlign: 'end', marginTop: '1em' }}>
				<Button onClick={onSelectAll} style={{ marginRight: '10px' }}>
					{isAllSelected
						? __('Deselect All', 'code-snippets')
						: __('Select All', 'code-snippets')
					}
				</Button>
				<Button 
					primary
					onClick={onImport}
					disabled={selectedSnippets.size === 0 || isImporting}
				>
					{isImporting 
						? __('Importing...', 'code-snippets')
						: __('Import Selected', 'code-snippets')} ({selectedSnippets.size})
				</Button>
			</div>
		</ImportCard>
	)
}
