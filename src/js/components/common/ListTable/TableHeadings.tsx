import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { ListTableColumn, ListTableProps, ListTableSortDirection } from './ListTable'
import type { Dispatch, Key, SetStateAction, ThHTMLAttributes } from 'react'

export interface TableHeadingsProps<T, K extends Key> extends Pick<ListTableProps<T, K>, 'columns' | 'getKey' | 'items'> {
	which: 'head' | 'foot'
	sortColumn: ListTableColumn<T> | undefined
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
	sortDirection: ListTableSortDirection
	setSortColumn: Dispatch<SetStateAction<ListTableColumn<T> | undefined>>
	setSortDirection: Dispatch<SetStateAction<ListTableSortDirection>>
}

interface SortableHeadingCellProps<T> {
	column: ListTableColumn<T>
	cellProps: ThHTMLAttributes<HTMLTableCellElement>
	sortColumn: ListTableColumn<T> | undefined
	sortDirection: ListTableSortDirection
	setSortColumn: Dispatch<SetStateAction<ListTableColumn<T> | undefined>>
	setSortDirection: Dispatch<SetStateAction<ListTableSortDirection>>
}

const SortableHeadingCell = <T,>({
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
				<span>{column.title}</span>
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

export const TableHeadings = <T, K extends Key>({
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
}: TableHeadingsProps<T, K>) => {
	const allSelected = 0 < items.length && items.every(item => selected.has(getKey(item)))

	return <tr>
		<td className="column-cb check-column">
			<input
				id={`cb-select-all-${which}`}
				type="checkbox"
				name="checked[]"
				checked={allSelected}
				onChange={event => {
					setSelected(new Set(event.target.checked ? items.map(getKey) : []))
				}}
			/>
			<label htmlFor={`cb-select-all-${which}`}>
				<span className="screen-reader-text">{__('Select All', 'code-snippets')}</span>
			</label>
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

			if (!column.sortedValue) {
				return <th key={column.id} {...cellProps}>{column.title}</th>
			}

			return <SortableHeadingCell
				key={column.id}
				{...{ column, cellProps, sortColumn, sortDirection, setSortColumn, setSortDirection }}
			/>
		})}
	</tr>
}
