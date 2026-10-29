import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { ListTableColumn, ListTableSortDirection } from './ListTable'
import type { Key, ThHTMLAttributes } from 'react'

interface SortableHeadingCellProps<T> {
	column: ListTableColumn<T>
	cellProps: ThHTMLAttributes<HTMLTableCellElement>
	sortColumn: ListTableColumn<T> | undefined
	sortDirection: ListTableSortDirection
	setSortColumn: (column: ListTableColumn<T> | undefined) => void
	setSortDirection: (direction: ListTableSortDirection) => void
}

const SortableHeadingCell = <T, >({
	column,
	cellProps,
	sortColumn,
	sortDirection,
	setSortColumn,
	setSortDirection
}: SortableHeadingCellProps<T>) => {
	const isCurrent = column.id === sortColumn?.id
	const nextSortDirection = isCurrent
		? 'asc' === sortDirection ? 'desc' : 'asc'
		: column.defaultSortDirection ?? 'asc'

	const classDirection = isCurrent ? sortDirection : 'asc' === nextSortDirection ? 'desc' : 'asc'
	const ariaSort = isCurrent ? 'asc' === sortDirection ? 'ascending' : 'descending' : undefined

	return (
		<th
			{...cellProps}
			aria-sort={ariaSort}
			className={classnames(cellProps.className, isCurrent ? 'sorted' : 'sortable', classDirection)}
		>
			<button
				type="button"
				className="list-table-sort-button"
				onClick={() => {
					setSortColumn(column)
					setSortDirection(nextSortDirection)
				}}
			>
				<span className="sortable-column-title">{column.title}</span>
				<span className="sorting-indicators">
					<span className="sorting-indicator asc" aria-hidden="true"></span>
					<span className="sorting-indicator desc" aria-hidden="true"></span>
				</span>
				{isCurrent ? null
					: <span className="screen-reader-text">
						{/* translators: Hidden accessibility text. */}
						{'asc' === nextSortDirection ? __('Sort ascending.', 'code-snippets') : __('Sort descending.', 'code-snippets')}
					</span>}
			</button>
		</th>
	)
}

export interface ColumnHeadingsProps<T, K extends Key> {
	items: T[]
	which: 'head' | 'foot'
	getKey: (item: T) => K
	columns: ListTableColumn<T>[]
	selected: Set<K>
	sortColumn: ListTableColumn<T> | undefined
	setSelected: (selected: Set<K>) => void
	sortDirection: ListTableSortDirection
	setSortColumn: (column: ListTableColumn<T> | undefined) => void
	setSortDirection: (direction: ListTableSortDirection) => void
}

export const ColumnHeadings = <T, K extends Key>({
	items,
	which,
	getKey,
	columns,
	sortColumn,
	selected,
	setSelected,
	setSortColumn,
	sortDirection,
	setSortDirection
}: ColumnHeadingsProps<T, K>) =>
	<tr>
		<td className="column-cb check-column">
			<input
				id={`cb-select-all-${which}`}
				type="checkbox"
				name="checked[]"
				checked={0 < items.length && items.every(item => selected.has(getKey(item)))}
				aria-label={__('Select All', 'code-snippets')}
				onChange={event => {
					setSelected(new Set(event.target.checked ? items.map(getKey) : []))
				}}
			/>
		</td>
		{columns.map(column => {
			const cellProps: ThHTMLAttributes<HTMLTableCellElement> = {
				id: 'head' === which ? column.id.toString() : undefined,
				scope: 'col',
				className: classnames(
					'manage-column',
					`column-${column.id}`,
					`${column.id}-column`,
					{ 'hidden': column.isHidden, 'column-primary': column.isPrimary }
				)
			}

			return column.sortedValue
				? <SortableHeadingCell
					key={column.id}
					{...{ column, cellProps, sortColumn, sortDirection, setSortColumn, setSortDirection }}
				/>
				: <th key={column.id} {...cellProps}>{column.title}</th>
		})}
	</tr>
