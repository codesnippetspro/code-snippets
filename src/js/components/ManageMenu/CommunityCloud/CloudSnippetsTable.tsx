import { __ } from '@wordpress/i18n'
import React, { useState } from 'react'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudSnippetDownloadButton } from '../../common/cloud/CloudSnippetDownloadButton'
import { CloudSnippetPreviewModal } from '../../common/cloud/CloudSnippetPreviewModal'
import { CloudStatusBadge } from '../../common/cloud/CloudStatusBadge'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

interface CloudSnippetRowProps {
	snippet: CloudSnippetSchema
}

const CloudSnippetRow: React.FC<CloudSnippetRowProps> = ({ snippet }) => {
	const [isPreviewOpen, setIsPreviewOpen] = useState(false)

	return (
		<tr>
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
				<div className="cloud-table-description">{truncateChars(snippet.description)}</div>
			</td>

			<td className="column-actions">
				<div className="cloud-snippet-action-buttons">
					<Button secondary onClick={() => setIsPreviewOpen(true)}>
						{__('Preview', 'code-snippets')}
					</Button>

					<CloudSnippetDownloadButton snippet={snippet} />
				</div>

				<CloudSnippetPreviewModal snippet={snippet} isOpen={isPreviewOpen} setIsOpen={setIsPreviewOpen} />
			</td>
		</tr>
	)
}

export interface CloudSnippetsTableProps {
	snippets: CloudSnippetSchema[]
}

/**
 * Table view for lists of cloud snippets (community search results and
 * bundle contents), mirroring the card actions. Descriptions are clamped
 * so all rows stay the same height.
 */
export const CloudSnippetsTable: React.FC<CloudSnippetsTableProps> = ({ snippets }) =>
	<table className="wp-list-table widefat fixed striped cloud-snippets-table">
		<thead>
			<tr>
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
				<CloudSnippetRow key={snippet.id} snippet={snippet} />)}
		</tbody>
	</table>
