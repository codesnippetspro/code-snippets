import { __, sprintf } from '@wordpress/i18n'
import React, { useState } from 'react'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { stripTags, truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudSnippetDownloadButton } from '../../common/cloud/CloudSnippetDownloadButton'
import { CloudSnippetPreviewModal } from '../../common/cloud/CloudSnippetPreviewModal'
import { CloudStatusBadge } from '../../common/cloud/CloudStatusBadge'
import { useCloudSearch } from './WithCloudSearchContext'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { Dispatch, SetStateAction } from 'react'

type CloudSnippetId = CloudSnippetSchema['id']
type SetSelected = Dispatch<SetStateAction<Set<CloudSnippetId>>>

const updateSelection = (setSelected: SetSelected, ids: CloudSnippetId[], isSelected: boolean) => {
	setSelected(previous => {
		const updated = new Set(previous)
		ids.forEach(id => isSelected ? updated.add(id) : updated.delete(id))
		return updated
	})
}

interface CloudSnippetRowProps {
	snippet: CloudSnippetSchema
	selected: Set<CloudSnippetId>
	setSelected: SetSelected
}

interface CloudSnippetActionsProps extends Pick<CloudSnippetRowProps, 'snippet'> {
	isPreviewOpen: boolean
	setIsPreviewOpen: (isOpen: boolean) => void
}

const CloudSnippetActions: React.FC<CloudSnippetActionsProps> = ({
	snippet,
	isPreviewOpen,
	setIsPreviewOpen
}) => {
	const { doSearch } = useCloudSearch()

	return (
		<>
			<div className="cloud-snippet-action-buttons">
				<Button secondary onClick={() => setIsPreviewOpen(true)}>
					{__('Preview', 'code-snippets')}
				</Button>

				<CloudSnippetDownloadButton snippet={snippet} onDownloaded={doSearch} />
			</div>

			<CloudSnippetPreviewModal
				snippet={snippet}
				isOpen={isPreviewOpen}
				setIsOpen={setIsPreviewOpen}
				onDownloaded={doSearch}
			/>
		</>
	)
}

const CloudSnippetRow: React.FC<CloudSnippetRowProps> = ({ snippet, selected, setSelected }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<tr>
			<th scope="row" className="check-column">
				<input
					id={`cb-select-${snippet.id}`}
					type="checkbox"
					name="checked[]"
					checked={selected.has(snippet.id)}
					aria-label={sprintf(
						// translators: %s: snippet name.
						__('Select %s', 'code-snippets'),
						snippet.name
					)}
					onChange={event => updateSelection(setSelected, [snippet.id], event.target.checked)}
				/>
			</th>

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
				<CloudSnippetActions {...{ snippet, isPreviewOpen, setIsPreviewOpen }} />
			</td>
		</tr>
	)
}

export interface CloudSnippetsTableProps {
	snippets: CloudSnippetSchema[]
	selected: Set<CloudSnippetId>
	setSelected: SetSelected
}

/**
 * Table view for lists of cloud snippets (community search results and
 * bundle contents), mirroring the card actions. Descriptions are clamped
 * so all rows stay the same height.
 */
export const CloudSnippetsTable: React.FC<CloudSnippetsTableProps> = ({ snippets, selected, setSelected }) =>
	<table className="wp-list-table widefat fixed striped cloud-snippets-table">
		<thead>
			<tr>
				<th scope="col" className="check-column">
					<input
						id="cb-select-all-cloud-snippets"
						type="checkbox"
						checked={0 < snippets.length && snippets.every(snippet => selected.has(snippet.id))}
						aria-label={__('Select all snippets', 'code-snippets')}
						onChange={event =>
							updateSelection(setSelected, snippets.map(snippet => snippet.id), event.target.checked)}
					/>
				</th>
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
				<CloudSnippetRow key={snippet.id} {...{ snippet, selected, setSelected }} />)}
		</tbody>
	</table>
