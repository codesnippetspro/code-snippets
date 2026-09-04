import React, { Fragment } from 'react'
import { __ } from '@wordpress/i18n'
import { humanTimeDiff } from '@wordpress/date'
import classnames from 'classnames'
import { getSnippetDisplayName, getSnippetType } from '../../../utils/snippets/snippets'
import { stripTags, truncateChars } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { CloudStatusBadge } from '../../common/cloud/CloudStatusBadge'
import { Tooltip } from '../../common/Tooltip'
import { CloudAvailableIcon } from '../../common/icons/cloud/CloudAvailableIcon'
import { CloudSyncedIcon } from '../../common/icons/cloud/CloudSyncedIcon'
import { FEATURED_SNIPPET_ID } from './demoCloudSnippets'
import { hasReached } from './types'
import type { DemoStage } from './types'
import type { ListTableColumn } from '../../common/ListTable'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

/**
 * Whether a row should render as already downloaded. Only the featured snippet
 * changes state, and only once the walkthrough has downloaded it.
 */
const isSynced = (snippet: CloudSnippetSchema, stage: DemoStage): boolean =>
	FEATURED_SNIPPET_ID === snippet.id && hasReached(stage, 'synced')

const StateColumn: React.FC<{ snippet: CloudSnippetSchema, stage: DemoStage }> = ({ snippet, stage }) => {
	const synced = isSynced(snippet, stage)
	const label = synced
		? __('Synced to Cloud', 'code-snippets')
		: __('Available in Cloud', 'code-snippets')

	const Icon = () =>
		<span className="cloud-sync-indicator" role="img" aria-label={label}>
			{synced ? <CloudSyncedIcon /> : <CloudAvailableIcon />}
		</span>

	return <Tooltip inline label={label} icon={<Icon />}>{label}</Tooltip>
}

const TagsColumn: React.FC<{ snippet: CloudSnippetSchema }> = ({ snippet }) =>
	snippet.tags.map((tag, index) =>
		<Fragment key={tag}>{tag}{index < snippet.tags.length - 1 ? ', ' : ''}</Fragment>)

interface ActionsColumnProps {
	snippet: CloudSnippetSchema
	stage: DemoStage
	onPreview: (snippet: CloudSnippetSchema) => void
}

const ActionsColumn: React.FC<ActionsColumnProps> = ({ snippet, stage, onPreview }) => {
	const featured = FEATURED_SNIPPET_ID === snippet.id

	return (
		<div className="cloud-snippet-action-buttons">
			<Button
				secondary
				className={classnames('demo-preview-button', { 'demo-click': featured && 'preview' === stage })}
				onClick={() => onPreview(snippet)}
			>
				{__('Preview', 'code-snippets')}
			</Button>

			{isSynced(snippet, stage)
				? <Button secondary className="demo-action-button" aria-hidden="true" tabIndex={-1}>
					{__('Edit', 'code-snippets')}
				</Button>
				: <Button
					primary
					aria-hidden="true"
					tabIndex={-1}
					className={classnames('cloud-snippet-download demo-action-button', {
						'demo-click': featured && 'download' === stage
					})}
				>
					{__('Download', 'code-snippets')}
				</Button>}
		</div>
	)
}

interface ColumnParams {
	stage: DemoStage
	onPreview: (snippet: CloudSnippetSchema) => void
}

// eslint-disable-next-line max-lines-per-function -- the column set reads as one table definition.
export const cloudLibraryDemoColumns = (
	{ stage, onPreview }: ColumnParams
): ListTableColumn<CloudSnippetSchema>[] => [
	{
		id: 'state',
		title: <CloudAvailableIcon role="img" aria-label={__('Sync Status', 'code-snippets')} />,
		render: snippet => <StateColumn snippet={snippet} stage={stage} />
	},
	{
		id: 'name',
		title: __('Snippet Name', 'code-snippets'),
		isPrimary: true,
		sortedValue: snippet => getSnippetDisplayName(snippet).toLowerCase(),
		render: snippet => <strong>{snippet.name}</strong>
	},
	{
		id: 'type',
		title: __('Type', 'code-snippets'),
		sortedValue: snippet => getSnippetType(snippet),
		render: snippet => <Badge name={getSnippetType(snippet)} />
	},
	{
		id: 'status',
		title: __('Status', 'code-snippets'),
		sortedValue: snippet => snippet.status,
		render: snippet => <CloudStatusBadge status={snippet.status} />
	},
	{
		id: 'desc',
		title: __('Description', 'code-snippets'),
		render: snippet =>
			<div className="snippet-description-content">
				{truncateChars(stripTags(snippet.description))}
			</div>
	},
	{
		id: 'tags',
		title: __('Tags', 'code-snippets'),
		render: snippet => <TagsColumn snippet={snippet} />
	},
	{
		id: 'date',
		title: __('Updated', 'code-snippets'),
		sortedValue: snippet => new Date(snippet.updated).toISOString(),
		render: snippet =>
			<span className="modified-column-content" title={snippet.updated}>
				<time dateTime={snippet.updated}>{humanTimeDiff(snippet.updated, undefined)}</time>
			</span>
	},
	{
		id: 'actions',
		title: <span className="screen-reader-text">{__('Actions', 'code-snippets')}</span>,
		render: snippet => <ActionsColumn snippet={snippet} stage={stage} onPreview={onPreview} />
	}
]
