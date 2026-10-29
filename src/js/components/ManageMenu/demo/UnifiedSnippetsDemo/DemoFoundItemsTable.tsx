import React from 'react'
import classnames from 'classnames'
import { __, sprintf } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { Badge } from '../../../common/Badge'
import { Button } from '../../../common/Button'
import { FEATURED_VIEW_ONLY_KEY, sourceLabel } from './demoFoundItems'
import { hasReached, isImportable } from './types'
import type { DemoFoundItem, DemoStage } from './types'

interface DemoRowProps {
	item: DemoFoundItem
	stage: DemoStage
	onPreview: (item: DemoFoundItem) => void
}

const isImported = (item: DemoFoundItem, stage: DemoStage): boolean =>
	isImportable(item) && hasReached(stage, 'imported')

const TypeBadge: React.FC<{ item: DemoFoundItem }> = ({ item }) =>
	'config' === item.type || 'mixed' === item.type
		? <Badge name="core" small>{item.type}</Badge>
		: <Badge name={item.type} small />

const StatusBadge: React.FC<{ item: DemoFoundItem, stage: DemoStage }> = ({ item, stage }) => {
	if (!isImportable(item)) {
		return <span className="badge small-badge unified-snippets-status-badge is-view-only">{__('View only', 'code-snippets')}</span>
	}

	return isImported(item, stage)
		? <span className="badge small-badge unified-snippets-status-badge is-imported">{__('Imported', 'code-snippets')}</span>
		: <span className="badge small-badge unified-snippets-status-badge is-ready">{__('Ready to import', 'code-snippets')}</span>
}

const ImportCell: React.FC<{ item: DemoFoundItem, stage: DemoStage }> = ({ item, stage }) => {
	if (!isImportable(item)) {
		return null
	}

	if (isImported(item, stage)) {
		return (
			<span className="unified-snippets-row__imported">
				<span className="dashicons dashicons-yes" aria-hidden="true" />
				{sprintf(
					/* translators: %d: snippet ID */
					__('Snippet #%d (inactive)', 'code-snippets'),
					item.importedId ?? 0
				)}
			</span>
		)
	}

	const isImporting = 'importing' === stage

	return (
		<Button primary small disabled={isImporting} aria-hidden="true" tabIndex={-1}>
			{isImporting && <Spinner />}
			{isImporting ? __('Importing…', 'code-snippets') : __('Import', 'code-snippets')}
		</Button>
	)
}

const DemoRow: React.FC<DemoRowProps> = ({ item, stage, onPreview }) => {
	const isSelected = isImportable(item) && hasReached(stage, 'select') && !hasReached(stage, 'imported')

	return (
		<tr className={classnames({
			'is-view-only': !isImportable(item),
			'demo-featured-row': FEATURED_VIEW_ONLY_KEY === item.key
		})}>
			<th scope="row" className="check-column">
				{isImportable(item) && !isImported(item, stage) &&
					<input type="checkbox" checked={isSelected} disabled aria-label={item.name} />}
			</th>

			<td className="column-name column-primary">
				<strong className="unified-snippets-item-name">{item.name}</strong>
				<span className="unified-snippets-item-source">{item.sourceName}</span>
				<div className="unified-snippets-row__evidence">
					{item.isRunning
						? <span className="unified-snippets-chip is-running">{__('Seen on a page', 'code-snippets')}</span>
						: <span className="unified-snippets-chip is-inactive">{__('Not running', 'code-snippets')}</span>}
				</div>
			</td>

			<td className="column-status">
				<StatusBadge item={item} stage={stage} />
				{item.viewOnlyReason && <span className="unified-snippets-item-source">{item.viewOnlyReason}</span>}
			</td>

			<td className="column-type">
				<TypeBadge item={item} />
			</td>

			<td className="column-source">
				<span>{sourceLabel(item.sourceType)}</span>
				<code className="unified-snippets-item-path">{item.sourcePath}</code>
			</td>

			<td className="column-actions">
				<div className="unified-snippets-item-actions">
					<Button secondary small onClick={() => onPreview(item)}>
						{__('Preview', 'code-snippets')}
					</Button>
					<ImportCell item={item} stage={stage} />
				</div>
			</td>
		</tr>
	)
}

const DemoBulkActions: React.FC<{ stage: DemoStage }> = ({ stage }) =>
	<div className="tablenav top">
		<div className="alignleft actions bulkactions unified-snippets-demo-bulk">
			<select disabled aria-label={__('Select bulk action', 'code-snippets')} value={hasReached(stage, 'select') ? 'import' : '-1'}>
				<option value="-1">{__('Bulk actions', 'code-snippets')}</option>
				<option value="import">{__('Import (inactive)', 'code-snippets')}</option>
			</select>

			<Button
				aria-hidden="true"
				tabIndex={-1}
				className={classnames('action', { 'demo-click': 'importing' === stage })}
			>
				{__('Apply', 'code-snippets')}
			</Button>
		</div>
		<br className="clear" />
	</div>

interface DemoFoundItemsTableProps {
	items: DemoFoundItem[]
	stage: DemoStage
	onPreview: (item: DemoFoundItem) => void
}

/**
 * The scan's results table, drawn by hand rather than through the shared list
 * table so the walkthrough can tick and import rows itself. Only the preview
 * buttons respond; everything else is driven by the script.
 */
export const DemoFoundItemsTable: React.FC<DemoFoundItemsTableProps> = ({ items, stage, onPreview }) =>
	<div className="unified-snippets-items">
		<DemoBulkActions stage={stage} />

		<table className={classnames('wp-list-table widefat striped unified-snippets-table unified-snippets-demo-table', {
			'is-highlighting-sources': 'sources' === stage
		})}>
			<thead>
				<tr>
					<td className="manage-column column-cb check-column">
						<span className="screen-reader-text">{__('Selected', 'code-snippets')}</span>
					</td>
					<th scope="col" className="manage-column column-name column-primary">{__('Name', 'code-snippets')}</th>
					<th scope="col" className="manage-column column-status">{__('Status', 'code-snippets')}</th>
					<th scope="col" className="manage-column column-type">{__('Type', 'code-snippets')}</th>
					<th scope="col" className="manage-column column-source">{__('Found in', 'code-snippets')}</th>
					<th scope="col" className="manage-column column-actions">
						<span className="screen-reader-text">{__('Actions', 'code-snippets')}</span>
					</th>
				</tr>
			</thead>

			<tbody>
				{items.map(item =>
					<DemoRow key={item.key} item={item} stage={stage} onPreview={onPreview} />)}
			</tbody>
		</table>
	</div>
