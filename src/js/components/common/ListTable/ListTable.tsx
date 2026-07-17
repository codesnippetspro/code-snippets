import React, { useMemo, useState } from 'react'
import classnames from 'classnames'
import { fetchQueryParam } from '../../../utils/urls'
import { ColumnHeadings } from './ColumnHeadings'
import { TableRows } from './TableRows'
import { TableNavigation } from './TableNavigation'
import type { ColumnHeadingsProps } from './ColumnHeadings'
import type { Dispatch, Key, ReactNode, SetStateAction } from 'react'

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
	doAction?: (action: A, selected: Set<K>) => Promise<void>
	disabled?: boolean
	extraTableNav?: (which: 'top' | 'bottom') => ReactNode
	endTableNav?: (which: 'top' | 'bottom') => ReactNode
}

export interface ListTableRowsProps<T, K extends Key> {
	getKey: (item: T) => K
	columns: ListTableColumn<T>[]
	noItems?: ReactNode
	rowClassName?: (item: T) => string
}

export interface ListTablePaginationProps {
	totalPages?: number
	pageSearchParam?: string
}

export interface ListTableBorderProps {
	fixed?: boolean
	striped?: boolean
	className?: string
}

export const sortTableItems = <T, >(
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

export interface ListTableProps<T, K extends Key, A extends string> extends ListTableBorderProps,
	ListTableNavProps<K, A>,
	ListTablePaginationProps,
	ListTableRowsProps<T, K> {
	items: T[]
	beforeTable?: ReactNode
	selectAllControl?: boolean
}

export const ListTable = <T, K extends Key, A extends string = never>({
	items,
	getKey,
	totalPages,
	pageSearchParam = 'paged',
	...tableProps
}: ListTableProps<T, K, A>) => {
	const [sortColumn, setSortColumn] = useState<ListTableColumn<T>>()
	const [currentPage, setCurrentPage] = useState(() => pageSearchParam && Number(fetchQueryParam(pageSearchParam)) || 1)
	const [sortDirection, setSortDirection] = useState<ListTableSortDirection>('asc')

	const visibleItems: T[] = useMemo(
		() => pageItems(sortTableItems(items, sortColumn, sortDirection), { currentPage, totalPages }),
		[items, sortColumn, sortDirection, currentPage, totalPages])

	return (
		<PartialDataListTable
			getKey={getKey}
			totalItems={items.length}
			totalPages={totalPages}
			visibleItems={visibleItems}
			pageSearchParam={pageSearchParam}
			{...{ sortColumn, sortDirection, currentPage, setSortColumn, setSortDirection, setCurrentPage }}
			{...tableProps}
		/>
	)
}

export interface PartialDataListTableProps<T, K extends Key, A extends string> extends ListTablePaginationProps,
	ListTableBorderProps, ListTableRowsProps<T, K>, ListTableNavProps<K, A> {
	sortColumn: ListTableColumn<T> | undefined
	totalItems: number
	currentPage: number
	visibleItems: T[]
	beforeTable?: ReactNode
	selectAllControl?: boolean
	setSortColumn: (column: ListTableColumn<T> | undefined) => void
	sortDirection?: ListTableSortDirection
	setCurrentPage: (page: number) => void
	setSortDirection: (direction: ListTableSortDirection) => void
}

interface PartialDataListTableContentProps<T, K extends Key> extends ListTableBorderProps, ListTableRowsProps<T, K> {
	visibleItems: T[]
	beforeTable?: ReactNode
	selected: Set<K>
	sortColumn: ListTableColumn<T> | undefined
	setSelected: Dispatch<SetStateAction<Set<K>>>
	sortDirection: ListTableSortDirection
	setSortColumn: (column: ListTableColumn<T> | undefined) => void
	setSortDirection: (direction: ListTableSortDirection) => void
}

const PartialDataListTableContent = <T, K extends Key>({
	fixed,
	getKey,
	columns,
	striped,
	className,
	selected,
	sortColumn,
	beforeTable,
	visibleItems,
	setSelected,
	sortDirection,
	setSortColumn,
	setSortDirection,
	...tableRowsProps
}: PartialDataListTableContentProps<T, K>) =>
	<>
		{beforeTable}

		<TableBorder
			items={visibleItems}
			fixed={fixed}
			striped={striped}
			className={className}
			{...{ columns, getKey, selected, sortColumn, sortDirection, setSelected, setSortColumn, setSortDirection }}
		>
			<TableRows
				items={visibleItems}
				selected={selected}
				setSelected={setSelected}
				{...{ getKey, columns }}
				{...tableRowsProps}
			/>
		</TableBorder>
	</>

export const PartialDataListTable = <T, K extends Key, A extends string>({
	fixed,
	getKey,
	columns,
	actions,
	striped,
	doAction,
	disabled = false,
	className,
	totalItems,
	totalPages,
	sortColumn,
	beforeTable,
	currentPage,
	visibleItems,
	sortDirection = 'asc',
	setSortColumn,
	extraTableNav,
	endTableNav,
	selectAllControl,
	setCurrentPage,
	pageSearchParam,
	setSortDirection,
	...tableRowsProps
}: PartialDataListTableProps<T, K, A>) => {
	const [selected, setSelected] = useState(() => new Set<K>())

	return (
		<TableNavigation
			totalItems={totalItems}
			selected={getVisibleSelected(visibleItems, getKey, selected)}
			selectAllKeys={selectAllControl ? visibleItems.map(getKey) : undefined}
			{...{ actions, doAction, extraTableNav, endTableNav, disabled, currentPage, totalPages }}
			{...{ pageSearchParam, setSelected, setCurrentPage }}
		>
			<PartialDataListTableContent
				{...{ fixed, getKey, columns, striped, className, selected, sortColumn, beforeTable, visibleItems }}
				{...{ setSelected, sortDirection, setSortColumn, setSortDirection }}
				{...tableRowsProps}
			/>
		</TableNavigation>
	)
}
