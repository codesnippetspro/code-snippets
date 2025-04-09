import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { TableHeadings, TableHeadingsProps } from './TableHeadings'
import { TableItems } from './TableItems'
import { TableNav, TableNavProps } from './TableNav'
import type { Key, ReactNode } from 'react'

export interface ListTableColumn<T> {
	key: Key
	title: string
	isHidden?: boolean
	isHeading?: boolean
	isPrimary?: boolean
	getSortedValue?: (item: T) => Key
	defaultSortDirection?: ListTableSortDirection
}

export type ListTableActions = Record<string, string | Record<string, string>>

export type ListTableSortDirection = 'asc' | 'desc'

export interface ListTableNavProps<K extends Key> {
	actions?: ListTableActions
	extraTableNav?: (which: 'top' | 'bottom') => ReactNode
	handleBulkAction?: (action: string, selected: Set<K>) => void
}

export interface ListTableItemsProps<T, K extends Key> {
	items: T[]
	getKey: (item: T) => K
	columns: ListTableColumn<T>[]
	noItems?: ReactNode
	renderColumn: (column: ListTableColumn<T>, item: T) => ReactNode
}

export interface ListTableProps<T, K extends Key> extends ListTableItemsProps<T, K>, ListTableNavProps<K> {
	fixed?: boolean
	striped?: boolean
	className?: string
}

export const ListTable = <T, K extends Key>({
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
	const [sortColumn, setSortColumn] = useState<ListTableColumn<T>>()
	const [sortDirection, setSortDirection] = useState<ListTableSortDirection>('asc')

	const sortedItems = useMemo(() => {
		console.log('sorting items', items, sortColumn, sortDirection)

		return items.toSorted((itemA, itemB) => {
			const valueA = sortColumn?.getSortedValue?.(itemA)
			const valueB = sortColumn?.getSortedValue?.(itemB)

			if (valueA === undefined || valueB === undefined) {
				return 0
			}

			if (valueA < valueB) {
				return 'asc' === sortDirection ? -1 : 1
			}

			if (valueA > valueB) {
				return 'asc' === sortDirection ? 1 : -1
			}

			return 0
		})
	}, [items, sortColumn, sortDirection])

	const tableNavProps: Omit<TableNavProps<K>, 'which'> =
		{ hasItems: items.length > 0, actions, extraTableNav, handleBulkAction, selected }

	const tableHeadingsProps: TableHeadingsProps<T, K> =
		{ items: sortedItems, setSelected, columns, getKey, sortColumn, setSortColumn, sortDirection, setSortDirection }

	return (
		<>
			<TableNav which="top" {...tableNavProps} />
			<table className={classnames('wp-list-table widefat', { striped, fixed }, className)}>
				<thead>
				<TableHeadings firstOnPage {...tableHeadingsProps} />
				</thead>
				<tbody id="the-list">
				<TableItems {...{ items: sortedItems, getKey, columns, noItems, renderColumn, setSelected }} />
				</tbody>
				<tfoot>
				<TableHeadings {...tableHeadingsProps} />
				</tfoot>
			</table>
			<TableNav which="bottom" {...tableNavProps} />
		</>
	)
}
