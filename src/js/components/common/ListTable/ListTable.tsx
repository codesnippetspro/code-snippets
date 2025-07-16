import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { TableHeadings } from './TableHeadings'
import { TableItems } from './TableItems'
import { TableNav } from './TableNav'
import type { TableNavProps } from './TableNav'
import type { TableHeadingsProps } from './TableHeadings'
import type { Key, ReactNode } from 'react'

export interface ListTableColumn<T> {
	id: Key
	title?: ReactNode
	render: (item: T) => ReactNode
	isHidden?: boolean
	isPrimary?: boolean
	sortedValue?: (item: T) => Key
	defaultSortDirection?: ListTableSortDirection
}

export interface ListTableBulkAction<K extends Key> {
	name: string
	apply: (selected: Set<K>) => Promise<void>
}

export interface ListTableBulkActionGroup<K extends Key> {
	name: string
	actions: ListTableBulkAction<K>[]
}

export type ListTableSortDirection = 'asc' | 'desc'

export interface ListTableNavProps<K extends Key> {
	actions?: readonly (ListTableBulkAction<K> | ListTableBulkActionGroup<K>)[]
	isDisabled?: boolean
	extraTableNav?: (which: 'top' | 'bottom') => ReactNode
}

export interface ListTableItemsProps<T, K extends Key> {
	items: T[]
	getKey: (item: T) => K
	columns: ListTableColumn<T>[]
	noItems?: ReactNode
	rowClassName?: (item: T) => string
}

export interface ListTableProps<T, K extends Key> extends ListTableItemsProps<T, K>, ListTableNavProps<K> {
	fixed?: boolean
	striped?: boolean
	className?: string
}

const sortItems = <T, >(
	items: T[],
	sortColumn: ListTableColumn<T> | undefined,
	sortDirection: ListTableSortDirection
): T[] =>
	items.toSorted((itemA, itemB) => {
		const valueA = sortColumn?.sortedValue?.(itemA)
		const valueB = sortColumn?.sortedValue?.(itemB)

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

export const ListTable = <T, K extends Key>({
	items,
	fixed,
	striped,
	getKey,
	columns,
	actions,
	noItems,
	className,
	extraTableNav,
	isDisabled = false,
}: ListTableProps<T, K>) => {
	const [selected, setSelected] = useState(new Set<K>())
	const [sortColumn, setSortColumn] = useState<ListTableColumn<T>>()
	const [sortDirection, setSortDirection] = useState<ListTableSortDirection>('asc')

	const sortedItems: T[] = useMemo(
		() => sortItems(items, sortColumn, sortDirection),
		[items, sortColumn, sortDirection])

	const tableNavProps: Omit<TableNavProps<K>, 'which'> =
		{ hasItems: 0 < items.length, actions, extraTableNav, selected, isDisabled }

	const tableHeadingsProps: Omit<TableHeadingsProps<T, K>, 'which'> =
		{ items: sortedItems, setSelected, columns, getKey, sortColumn, setSortColumn, sortDirection, setSortDirection }

	return (
		<>
			<TableNav which="top" {...tableNavProps} />
			<table className={classnames('wp-list-table widefat snippets', { striped, fixed }, className)}>
				<thead>
					<TableHeadings which="head" {...tableHeadingsProps} />
				</thead>
				<tbody>
					<TableItems items={sortedItems} {...{ getKey, columns, noItems, setSelected }} />
				</tbody>
				<tfoot>
					<TableHeadings which="foot" {...tableHeadingsProps} />
				</tfoot>
			</table>
			<TableNav which="bottom" {...tableNavProps} />
		</>
	)
}
