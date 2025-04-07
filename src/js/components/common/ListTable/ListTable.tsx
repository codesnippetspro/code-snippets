import React, { useState } from 'react'
import classnames from 'classnames'
import type { Dispatch, Key, ReactNode, SetStateAction } from 'react'
import { __ } from '@wordpress/i18n'
import { TableItems } from './TableItems'
import { TableNav, TableNavProps } from './TableNav'

export interface ListTableColumn {
	key: Key
	title: string
	isHeading?: boolean
}

export type ListTableActions = Record<string, string | Record<string, string>>

interface ColumnHeadingsProps<T, K extends Key> extends Pick<ListTableProps<T, K>, 'columns' | 'getKey' | 'items'> {
	id: number
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

const ColumnHeadings = <T, K extends Key>({ id, items, columns, getKey, setSelected }: ColumnHeadingsProps<T, K>) =>
	<tr>
		<td className="column-cb check-column">
			<input
				id={`cb-select-all-${id}`}
				type="checkbox"
				name="checked[]"
				onChange={event => {
					setSelected(new Set(event.target.checked ? items.map(getKey) : null))
				}}
			/>
			<label htmlFor={`cb-select-all-${id}`}>
				<span className="screen-reader-text">{__('Select All', 'code-snippets')}</span>
			</label>
		</td>
		{columns.map(column =>
			<th scope="col" key={column.key}>{column.title}</th>)}
	</tr>

export interface ListTableNavProps<K extends Key> {
	actions?: ListTableActions
	extraTableNav?: (which: 'top' | 'bottom') => ReactNode
	handleBulkAction?: (action: string, selected: Set<K>) => void
}

export interface ListTableItemsProps<T, K extends Key> {
	items: T[]
	getKey: (item: T) => K
	columns: ListTableColumn[]
	noItems?: ReactNode
	renderColumn: (column: ListTableColumn, item: T) => ReactNode
}

export interface ListTableProps<T, K extends Key> extends ListTableItemsProps<T, K>, ListTableNavProps<K> {
	wide?: boolean
	fixed?: boolean
	striped?: boolean
	className?: string
}

export const ListTable = <T, K extends Key>({
	wide = true,
	items,
	fixed,
	striped,
	getKey,
	columns,
	actions,
	noItems,
	className,
	renderColumn,
	extraTableNav,
	handleBulkAction
}: ListTableProps<T, K>) => {
	const [selected, setSelected] = useState(new Set<K>())

	const tableNavProps: Omit<TableNavProps<K>, 'which'> =
		{ hasItems: items.length > 0, actions, extraTableNav, handleBulkAction, selected }

	return (
		<>
			<TableNav which="top" {...tableNavProps} />
			<table className={classnames('wp-list-table', { widefat: wide, striped, fixed }, className)}>
				<thead>
				<ColumnHeadings id={1} {...{ items, setSelected, columns, getKey }} />
				</thead>
				<tbody id="the-list">
				<TableItems {...{ items, getKey, columns, noItems, renderColumn, setSelected }} />
				</tbody>
				<tfoot>
				<ColumnHeadings id={2} {...{ items, setSelected, columns, getKey }} />
				</tfoot>
			</table>
			<TableNav which="bottom" {...tableNavProps} />
		</>
	)
}
