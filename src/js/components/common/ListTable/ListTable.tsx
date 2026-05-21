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

export interface ListTableProps<T, K extends Key, A extends string> extends ListTableItemsProps<T, K>,
	ListTableNavProps<K, A>, ListTablePaginationProps {
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

const getVisibleSelected = <T, K extends Key>(
	visibleItems: T[],
	getKey: (item: T) => K,
	selected: Set<K>
): Set<K> =>
	new Set(visibleItems.map(getKey).filter(key => selected.has(key)))

interface ListTableMarkupProps<T, K extends Key, A extends string> {
	className?: string
	fixed?: boolean
	striped?: boolean
	getKey: (item: T) => K
	columns: ListTableColumn<T>[]
	noItems?: ReactNode
	rowClassName?: (item: T) => string
	tableNavProps: Omit<TableNavProps<K, A>, 'which'>
	tableHeadingsProps: Omit<TableHeadingsProps<T, K>, 'which'>
	visibleItems: T[]
}

const ListTableMarkup = <T, K extends Key, A extends string>({
	fixed,
	striped,
	getKey,
	columns,
	noItems,
	className,
	rowClassName,
	tableNavProps,
	tableHeadingsProps,
	visibleItems
}: ListTableMarkupProps<T, K, A>) => (
	<>
		<TableNav which="top" {...tableNavProps} />
		<table className={classnames('wp-list-table widefat', { striped, fixed }, className)}>
			<thead>
				<TableHeadings which="head" {...tableHeadingsProps} />
			</thead>
			<tbody>
				<TableItems
					items={visibleItems}
					{...{
						getKey,
						columns,
						noItems,
						selected: tableHeadingsProps.selected,
						setSelected: tableHeadingsProps.setSelected,
						rowClassName
					}}
				/>
			</tbody>
			<tfoot>
				<TableHeadings which="foot" {...tableHeadingsProps} />
			</tfoot>
		</table>
		<TableNav which="bottom" {...tableNavProps} />
	</>
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
	...tableProps
}: ListTableProps<T, K, A>) => {
	const [selected, setSelected] = useState(new Set<K>())
	const [sortColumn, setSortColumn] = useState<ListTableColumn<T>>()
	const [currentPage, setCurrentPage] = useState(() => useQueryVars && Number(fetchQueryParam('paged')) || 1)
	const [sortDirection, setSortDirection] = useState<ListTableSortDirection>('asc')

	const visibleItems: T[] = useMemo(
		() => pageItems(sortItems(items, sortColumn, sortDirection), { currentPage, totalPages }),
		[items, sortColumn, sortDirection, currentPage, totalPages])

	return (
		<ListTableMarkup
			{...tableProps}
			getKey={getKey}
			columns={columns}
			visibleItems={visibleItems}
			tableHeadingsProps={{
				items: visibleItems, selected, setSelected, columns, getKey, sortColumn, setSortColumn, sortDirection, setSortDirection
			}}
			tableNavProps={{
				totalItems: items.length,
				actions,
				doAction,
				extraTableNav,
				selected: getVisibleSelected(visibleItems, getKey, selected),
				setSelected,
				disabled,
				currentPage,
				totalPages,
				setCurrentPage,
				useQueryVars
			}}
		/>
	)
}
