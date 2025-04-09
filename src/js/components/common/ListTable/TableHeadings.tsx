import React, { ThHTMLAttributes } from 'react'
import classnames from 'classnames'
import { Dispatch, Key, SetStateAction } from 'react'
import { ListTableColumn, ListTableProps, ListTableSortDirection } from './ListTable'
import { __ } from '@wordpress/i18n'

interface SortableHeadingProps<T> {
	column: ListTableColumn<T>
	cellProps: ThHTMLAttributes<HTMLTableCellElement>
	sortColumn: ListTableColumn<T> | undefined
	sortDirection: ListTableSortDirection
	setSortColumn: Dispatch<SetStateAction<ListTableColumn<T> | undefined>>
	setSortDirection: Dispatch<SetStateAction<ListTableSortDirection>>
}

const SortableHeading = <T, >({ column, sortColumn, cellProps, sortDirection, setSortColumn, setSortDirection }: SortableHeadingProps<T>) => {
	const isCurrent = column.key === sortColumn?.key

	const newSortDirection = isCurrent
		? (sortDirection === 'asc' ? 'desc' : 'asc')
		: column.defaultSortDirection ?? 'asc'

	return (
		<th {...cellProps} className={classnames(cellProps.className, isCurrent ? 'sorted' : 'sortable')}>
			<a href="#" onClick={event => {
				event.preventDefault()
				setSortColumn(column)
				setSortDirection(newSortDirection)
				console.log('updating sort', column.key, newSortDirection)
			}}>
				<span>{column.title}</span>
				<span className="sorting-indicators">
					<span className="sorting-indicator asc" aria-hidden="true"></span>
					<span className="sorting-indicator desc" aria-hidden="true"></span>
				</span>
				{isCurrent ? null :
					<span className="screen-reader-text">
						{/* translators: Hidden accessibility text. */}
						{'asc' === newSortDirection ? __('Sort ascending.', 'code-snippets') : __('Sort descending.', 'code-snippets')}
					</span>}
			</a>
		</th>
	)
}

export interface TableHeadingsProps<T, K extends Key> extends Pick<ListTableProps<T, K>, 'columns' | 'getKey' | 'items'> {
	firstOnPage?: boolean
	sortColumn: ListTableColumn<T> | undefined
	setSelected: Dispatch<SetStateAction<Set<K>>>
	sortDirection: ListTableSortDirection
	setSortColumn: Dispatch<SetStateAction<ListTableColumn<T> | undefined>>
	setSortDirection: Dispatch<SetStateAction<ListTableSortDirection>>
}

export const TableHeadings = <T, K extends Key>({
	items,
	getKey,
	columns,
	sortColumn,
	setSelected,
	firstOnPage,
	setSortColumn,
	sortDirection,
	setSortDirection
}: TableHeadingsProps<T, K>) =>
	<tr>
		<td className="column-cb check-column">
			<input
				id={`cb-select-all-${firstOnPage ? 1 : 2}`}
				type="checkbox"
				name="checked[]"
				onChange={event => {
					setSelected(new Set(event.target.checked ? items.map(getKey) : null))
				}}
			/>
			<label htmlFor={`cb-select-all-${firstOnPage ? 1 : 2}`}>
				<span className="screen-reader-text">{__('Select All', 'code-snippets')}</span>
			</label>
		</td>
		{columns.map(column => {
			const cellProps: ThHTMLAttributes<HTMLTableCellElement> = {
				id: firstOnPage ? column.key.toString() : undefined,
				scope: 'col',
				className: classnames(
					'manage-column',
					`column-${column.key}`,
					{ 'hidden': column.isHidden, 'column-primary': column.isPrimary }
				)
			}

			return column.getSortedValue
				? <SortableHeading {...{ column, sortColumn, setSortColumn, sortDirection, setSortDirection, cellProps }} />
				: <th key={column.key} {...cellProps}>{column.title}</th>
		})}
	</tr>
