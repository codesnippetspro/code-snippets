import React from 'react'
import { __ } from '@wordpress/i18n'
import type { ImportableSnippet } from '../../../../hooks/useFileUploadAPI'

interface SnippetSelectionTableProps {
	snippets: ImportableSnippet[]
	selectedSnippets: Set<number | string>
	isAllSelected: boolean
	onSnippetToggle: (snippetId: number | string) => void
	onSelectAll: () => void
}

export const SnippetSelectionTable: React.FC<SnippetSelectionTableProps> = ({
	snippets,
	selectedSnippets,
	isAllSelected,
	onSnippetToggle,
	onSelectAll
}) => {
	const getTypeColor = (type: string): string => {
		switch (type) {
			case 'css': return '#9B59B6'
			case 'js': return '#FFEB3B'
			case 'html': return '#EF6A36'
			default: return '#1D97C6'
		}
	}

	const truncateDescription = (description: string | undefined): string => {
		const desc = description || __('No description', 'code-snippets')
		return desc.length > 50 ? desc.substring(0, 50) + '...' : desc
	}

	return (
		<table className="wp-list-table widefat fixed striped" style={{ borderRadius: '5px', tableLayout: 'fixed' }}>
			<thead>
				<tr>
					<th scope="col" className="check-column" style={{ padding: '8px 0', width: '40px' }}>
						<input
							type="checkbox"
							checked={isAllSelected}
							onChange={onSelectAll}
						/>
					</th>
					<th scope="col" style={{ width: '200px' }}>{__('Name', 'code-snippets')}</th>
					<th scope="col" style={{ width: '90px', textAlign: 'center' }}>{__('Type', 'code-snippets')}</th>
					<th scope="col" style={{ width: 'auto' }}>{__('Description', 'code-snippets')}</th>
					<th scope="col" style={{ width: '120px' }}>{__('Tags', 'code-snippets')}</th>
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
						<td>
							<strong>{snippet.table_data.title}</strong>
							{snippet.source_file && (
								<div style={{ fontSize: '12px', color: '#666', marginTop: '2px' }}>
									from {snippet.source_file}
								</div>
							)}
						</td>
						<td style={{ width: '90px', textAlign: 'center' }}>
							<span style={{
								backgroundColor: getTypeColor(snippet.table_data.type),
								color: 'white',
								padding: '3px 6px',
								fontSize: '10px',
								textTransform: 'uppercase',
								borderRadius: '3px'
							}}>
								{snippet.table_data.type}
							</span>
						</td>
						<td>
							{truncateDescription(snippet.table_data.description)}
						</td>
						<td>{snippet.table_data.tags || '—'}</td>
					</tr>
				))}
			</tbody>
		</table>
	)
}
