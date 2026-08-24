import { __, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import { getSnippetType, isCloudSnippetDownloadable } from '../../../utils/snippets/snippets'
import { stripTags, truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudSnippetDownloadButton } from '../../common/cloud/CloudSnippetDownloadButton'
import { CloudStatusBadge } from '../../common/cloud/CloudStatusBadge'
import { CloudSnippetPreviewModal } from '../../common/snippets/SnippetPreviewModal'
import { useCloudSearch } from './WithCloudSearchContext'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { Dispatch, SetStateAction } from 'react'

interface CloudSnippetRowProps {
	snippet: CloudSnippetSchema
}

const CloudSnippetRow: React.FC<CloudSnippetRowProps> = ({ snippet }) => {
	const { doSearch } = useCloudSearch()
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<>
			<td className="column-name column-primary">
				<strong>
					<button
						type="button"
						className="cloud-table-name-button"
						title={__('Preview this snippet', 'code-snippets')}
						onClick={() => setIsPreviewOpen(true)}
					>
						{snippet.name}
					</button>
				</strong>
			</td>

			<td className="column-type"><Badge name={getSnippetType(snippet)} /></td>

			<td className="column-status"><CloudStatusBadge status={snippet.status} /></td>

			<td className="column-desc">
				<div className="cloud-table-description">{truncateChars(stripTags(snippet.description))}</div>
			</td>

			<td className="column-actions">
				<div className="cloud-snippet-action-buttons">
					<Button secondary onClick={() => setIsPreviewOpen(true)}>
						{__('Preview', 'code-snippets')}
					</Button>

					<CloudSnippetDownloadButton snippet={snippet} onDownloaded={doSearch} />
				</div>

				{isPreviewOpen && (
					<CloudSnippetPreviewModal snippet={snippet} setIsOpen={setIsPreviewOpen} onDownloaded={doSearch} />)}
			</td>
		</>
	)
}

interface TableHeadingCheckboxProps {
	availableIds: CloudSnippetSchema['id'][]
	selectedIds: Set<CloudSnippetSchema['id']>
	setSelectedIds: Dispatch<SetStateAction<Set<CloudSnippetSchema['id']>>>
}

const TableHeadingCheckbox: React.FC<TableHeadingCheckboxProps> = ({ availableIds, selectedIds, setSelectedIds }) =>
	<td className="column-cb check-column">
		<input
			id="cb-select-all-cloud-snippets"
			type="checkbox"
			checked={availableIds.every(snippetId => selectedIds.has(snippetId))}
			onChange={event =>
				setSelectedIds(previous =>
					new Set(event.target.checked
						? [...previous, ...availableIds]
						: [...previous].filter(snippetId => !availableIds.includes(snippetId)))
				)}
			aria-label={__('Select all snippets', 'code-snippets')}
		/>
	</td>

interface TableRowCheckboxProps {
	snippet: CloudSnippetSchema
	selected: Set<CloudSnippetSchema['id']>
	setSelected: Dispatch<SetStateAction<Set<CloudSnippetSchema['id']>>>
}

const TableRowCheckbox: React.FC<TableRowCheckboxProps> = ({ snippet, selected, setSelected }) =>
	<th scope="row" className="check-column">
		{isCloudSnippetDownloadable(snippet) && (
			<input
				id={`cb-select-${snippet.id}`}
				type="checkbox"
				name="checked[]"
				checked={selected.has(snippet.id)}
				// translators: %s: snippet name.
				aria-label={sprintf(__('Select %s', 'code-snippets'), snippet.name)}
				onChange={event =>
					setSelected(previous =>
						new Set(event.target.checked
							? [...previous, snippet.id]
							: [...previous].filter(snippetId => snippetId !== snippet.id))
					)}
			/>)}
	</th>

export interface CloudSnippetsTableProps {
	snippets: CloudSnippetSchema[]
	selected?: Set<CloudSnippetSchema['id']>
	setSelected?: Dispatch<SetStateAction<Set<CloudSnippetSchema['id']>>>
}

/**
 * Table view for lists of cloud snippets (community search results and
 * bundle contents), mirroring the card actions. Descriptions are clamped
 * so all rows stay the same height. Selection is only offered when the
 * containing view provides selection state, as bundle contents have no
 * bulk actions.
 */
export const CloudSnippetsTable: React.FC<CloudSnippetsTableProps> = ({
	snippets,
	selected,
	setSelected
}) =>
	<table className="wp-list-table widefat fixed striped cloud-snippets-table">
		<thead>
			<tr>
				{selected && setSelected && (
					<TableHeadingCheckbox
						selectedIds={selected}
						setSelectedIds={setSelected}
						availableIds={snippets
							.filter(snippet => isCloudSnippetDownloadable(snippet))
							.map(snippet => snippet.id)}
					/>)}

				<th scope="col" className="column-name column-primary">{__('Name', 'code-snippets')}</th>
				<th scope="col" className="column-type">{__('Type', 'code-snippets')}</th>
				<th scope="col" className="column-status">{__('Status', 'code-snippets')}</th>
				<th scope="col" className="column-desc">{__('Description', 'code-snippets')}</th>
				<th scope="col" className="column-actions">
					<span className="screen-reader-text">{__('Actions', 'code-snippets')}</span>
				</th>
			</tr>
		</thead>
		<tbody>
			{snippets.map(snippet =>
				<tr key={snippet.id}>
					{selected && setSelected && <TableRowCheckbox {...{ snippet, selected, setSelected }} />}
					<CloudSnippetRow snippet={snippet} />
				</tr>
			)}
		</tbody>
	</table>
