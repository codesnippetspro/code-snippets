import React from 'react'
import { __, _x, sprintf } from '@wordpress/i18n'
import type { UseSelection } from '../../../../hooks/useSelection'
import type { ImportableSnippetSchema } from '../../../../types/schema/ImportableSnippetSchema'

const DESC_MAX_LENGTH = 50

const truncateDescription = (description: string | undefined): string => {
	if (!description) {
		return __('No description', 'code-snippets')
	}

	return DESC_MAX_LENGTH < description.length
		// translators: %s: truncated snippet description.
		? sprintf(_x('%s…', 'import snippet description', 'code-snippets'), description.substring(0, DESC_MAX_LENGTH))
		: description
}

export interface SnippetSelectionTableProps {
	snippets: ImportableSnippetSchema[]
	selection: UseSelection<ImportableSnippetSchema, ImportableSnippetSchema['table_data']['id']>
}

export const SnippetSelectionTable: React.FC<SnippetSelectionTableProps> = ({
	snippets,
	selection: { selectedItems, isAllSelected, toggleItem, selectAll }
}) =>
	<table className="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col" className="check-column">
					<input
						id="cb-select-all-head"
						type="checkbox"
						aria-label={__('Select all snippets', 'code-snippets')}
						checked={isAllSelected}
						onChange={selectAll}
					/>
				</th>
				<th scope="col" className="column-name">{__('Name', 'code-snippets')}</th>
				<th scope="col" className="column-type">{__('Type', 'code-snippets')}</th>
				<th scope="col" className="column-desc">{__('Description', 'code-snippets')}</th>
				<th scope="col" className="column-tags">{__('Tags', 'code-snippets')}</th>
			</tr>
		</thead>
		<tbody>
			{snippets.map(snippet =>
				<tr key={snippet.table_data.id} className={`${snippet.table_data.type}-snippet`}>
					<th scope="row" className="check-column">
						<input
							id={`cb-select-${snippet.table_data.id}`}
							type="checkbox"
							aria-label={__('Select snippet', 'code-snippets')}
							checked={selectedItems.has(snippet.table_data.id)}
							onChange={() => toggleItem(snippet.table_data.id)}
						/>
					</th>
					<td className="column-name">
						<strong>{snippet.table_data.title}</strong>
						{snippet.source_file && <div>
							{sprintf(
								// translators: %s: source file name.
								_x('from %s', 'import snippet source file', 'code-snippets'),
								snippet.source_file
							)}
						</div>}
					</td>
					<td className="column-type"><span>{snippet.table_data.type}</span></td>
					<td className="column-desc">{truncateDescription(snippet.table_data.description)}</td>
					<td className="column-tags">{snippet.table_data.tags || '—'}</td>
				</tr>
			)}
		</tbody>
	</table>
