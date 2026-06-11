import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { fetchQueryParam } from '../../../utils/urls'
import { ColumnHeadings } from './ColumnHeadings'
import { TableRows } from './TableRows'
import { TableNavigation } from './TableNavigation'
import type { ColumnHeadingsProps } from './ColumnHeadings'
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

export type ListTableSortDirection = 'asc' | 'desc'

export interface ListTableAction<A extends string> {
	key: A
	label: string
	group?: string
}

export interface ListTableNavProps<K extends Key, A extends string> {
	actions?: ListTableAction<A>[]
	doAction: (action: A, selected: Set<K>) => Promise<void>
	disabled?: boolean
	extraTableNav?: (which: 'top' | 'bottom') => ReactNode
}

export interface ListTableRowsProps<T, K extends Key> {
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

export interface ListTableBorderProps {
	fixed?: boolean
	striped?: boolean
	className?: string
}

export type ListTableProps<T, K extends Key, A extends string> =
	ListTableBorderProps &
	ListTableNavProps<K, A> &
	ListTablePaginationProps &
	ListTableRowsProps<T, K>

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

const getVisibleSelected = <T, K extends Key>(
	visibleItems: T[],
	getKey: (item: T) => K,
	selected: Set<K>
): Set<K> =>
	new Set(visibleItems.map(getKey).filter(key => selected.has(key)))

interface TableBorderProps<T, K extends Key> extends ListTableBorderProps, Omit<ColumnHeadingsProps<T, K>, 'which'> {
	children: ReactNode
}

const TableBorder = <T, K extends Key>({
	fixed,
	striped,
	children,
	className,
	...tableHeadingsProps
}: TableBorderProps<T, K>) => (
	<table className={classnames('wp-list-table widefat', { striped, fixed }, className)}>
		<thead>
			<ColumnHeadings which="head" {...tableHeadingsProps} />
		</thead>
		<tbody>
			{children}
		</tbody>
		<tfoot>
			<ColumnHeadings which="foot" {...tableHeadingsProps} />
		</tfoot>
	</table>
)

export const ListTable = <T, K extends Key, A extends string = never>({
	items,
	getKey,
	columns,
	actions,
	doAction,
	totalPages,
	extraTableNav,
	disabled = false,
	useQueryVars = true,
	fixed,
	striped,
	className,
	...tableRowsProps
}: ListTableProps<T, K, A>) => {
	const [selected, setSelected] = useState(() => new Set<K>())
	const [sortColumn, setSortColumn] = useState<ListTableColumn<T>>()
	const [currentPage, setCurrentPage] = useState(() => useQueryVars && Number(fetchQueryParam('paged')) || 1)
	const [sortDirection, setSortDirection] = useState<ListTableSortDirection>('asc')

	const visibleItems: T[] = useMemo(
		() => pageItems(sortItems(items, sortColumn, sortDirection), { currentPage, totalPages }),
		[items, sortColumn, sortDirection, currentPage, totalPages])
	return (
		<TableNavigation
			totalItems={items.length}
			selected={getVisibleSelected(visibleItems, getKey, selected)}
			{...{ actions, doAction, extraTableNav, disabled, currentPage, totalPages, useQueryVars, setSelected, setCurrentPage }}
		>
			<TableBorder
				items={visibleItems}
				fixed={fixed}
				striped={striped}
				className={className}
				{...{ columns, getKey, selected, sortColumn, sortDirection, setSelected, setSortColumn, setSortDirection }}
			>
				<TableRows
					items={visibleItems}
					{...{ getKey, columns, selected, setSelected }}
					{...tableRowsProps}
				/>
			</TableBorder>
		</TableNavigation>
	)
}
