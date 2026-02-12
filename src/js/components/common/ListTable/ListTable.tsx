import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { fetchQueryParam } from '../../../utils/urls'
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
	isHeading?: boolean
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

export interface ListTablePaginationProps {
	totalPages?: number
	useQueryVars?: boolean
}

export interface ListTableProps<T, K extends Key> extends ListTableItemsProps<T, K>, ListTableNavProps<K>, ListTablePaginationProps {
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

const pageItems = <T, >(
	items: T[],
	{ currentPage, totalPages }: { currentPage: number; totalPages?: number }
): T[] => {
	if (totalPages) {
		const itemsPerPage = Math.ceil(items.length / totalPages)
		const start = (currentPage - 1) * itemsPerPage
		const end = start + itemsPerPage
		return items.slice(start, end)
	} else {
		return items
	}
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
	totalPages,
	rowClassName,
	extraTableNav,
	useQueryVars = true,
	isDisabled = false
}: ListTableProps<T, K>) => {
	const [selected, setSelected] = useState(new Set<K>())
	const [sortColumn, setSortColumn] = useState<ListTableColumn<T>>()
	const [currentPage, setCurrentPage] = useState(() => useQueryVars && Number(fetchQueryParam('paged')) || 1)
	const [sortDirection, setSortDirection] = useState<ListTableSortDirection>('asc')

	const visibleItems: T[] = useMemo(
		() => pageItems(sortItems(items, sortColumn, sortDirection), { currentPage, totalPages }),
		[items, sortColumn, sortDirection, currentPage, totalPages])

	const tableNavProps: Omit<TableNavProps<K>, 'which'> =
		{ totalItems: items.length, actions, extraTableNav, selected, isDisabled, currentPage, totalPages, setCurrentPage, useQueryVars }

	const tableHeadingsProps: Omit<TableHeadingsProps<T, K>, 'which'> =
		{ items: visibleItems, setSelected, columns, getKey, sortColumn, setSortColumn, sortDirection, setSortDirection }

	return (
		<>
			<TableNav which="top" {...tableNavProps} />
			<table className={classnames('wp-list-table widefat', { striped, fixed }, className)}>
				<thead>
					<TableHeadings which="head" {...tableHeadingsProps} />
				</thead>
				<tbody>
					<TableItems items={visibleItems} {...{ getKey, columns, noItems, setSelected, rowClassName }} />
				</tbody>
				<tfoot>
					<TableHeadings which="foot" {...tableHeadingsProps} />
				</tfoot>
			</table>
			<TableNav which="bottom" {...tableNavProps} />
		</>
	)
}
