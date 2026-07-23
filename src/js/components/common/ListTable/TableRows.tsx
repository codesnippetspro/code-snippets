import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import type { Dispatch, Key, SetStateAction } from 'react'
import type { ListTableColumn, ListTableRowsProps } from './ListTable'

interface CheckboxCellProps<T, K extends Key> extends Pick<TableRowsProps<T, K>, 'getKey'> {
	item: T
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

const CheckboxCell = <T, K extends Key>({ item, selected, setSelected, getKey }: CheckboxCellProps<T, K>) =>
	<th scope="row" className="check-column">
		<input
			id={`cb-select-${getKey(item)}`}
			type="checkbox"
			name="checked[]"
			aria-label={__('Select snippet', 'code-snippets')}
			checked={selected.has(getKey(item))}
			onChange={event => {
				setSelected(previous => {
					const updated = new Set(previous)

					if (event.target.checked) {
						updated.add(getKey(item))
					} else {
						updated.delete(getKey(item))
					}

					return updated
				})
			}}
		/>
	</th>

interface TableCellProps<T> {
	item: T
	column: ListTableColumn<T>
	isExpanded: boolean
	expansionLabel?: string
	toggleExpanded: () => void
}

const TableCell = <T, >({
	item,
	column,
	isExpanded,
	expansionLabel,
	toggleExpanded
}: TableCellProps<T>) => {
	const className = classnames(`${column.id}-column`, `column-${column.id}`, { hidden: column.isHidden })
	const rendered = column.render(item)
	const label = column.mobileLabel ?? ('string' === typeof column.title ? column.title : undefined)
	const cellContent = <>
		{column.mobileLabel
			? <>
				<span className="mobile-cell-label" aria-hidden="true">{column.mobileLabel}</span>
				<div className="mobile-cell-value">{rendered}</div>
			</>
			: rendered}
		{column.isPrimary && expansionLabel
			? <button
				type="button"
				className="mobile-row-toggle"
				aria-expanded={isExpanded}
				aria-label={expansionLabel}
				onClick={toggleExpanded}
			>
				<span className="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
			</button>
			: null}
	</>

	return column.isHeading
		? <th className={className} data-label={label}>
			{cellContent}
		</th>
		: <td className={className} data-label={label}>
			{cellContent}
		</td>
}

export interface TableRowsProps<T, K extends Key>
	extends Pick<
		ListTableRowsProps<T, K>,
		'getKey' | 'columns' | 'noItems' | 'rowClassName' | 'getRowExpansionLabel'
	> {
	items: T[]
	selected: Set<K>
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

export const TableRows = <T, K extends Key>({
	items,
	getKey,
	columns,
	noItems,
	selected,
	setSelected,
	rowClassName,
	getRowExpansionLabel
}: TableRowsProps<T, K>
) => {
	const [expanded, setExpanded] = useState(() => new Set<K>())

	return 0 < items.length
		? items.map(item => {
			const key = getKey(item)
			const isExpanded = expanded.has(key)

			return <tr
				key={key}
				className={classnames(rowClassName?.(item), { 'is-mobile-expanded': isExpanded })}
			>
				<CheckboxCell {...{ item, selected, setSelected, getKey }} />

				{columns.map(column =>
					<TableCell
						key={column.id}
						item={item}
						column={column}
						isExpanded={isExpanded}
						expansionLabel={getRowExpansionLabel?.(item, isExpanded)}
						toggleExpanded={() => {
							setExpanded(previous => {
								const updated = new Set(previous)
								if (updated.has(key)) {
									updated.delete(key)
								} else {
									updated.add(key)
								}
								return updated
							})
						}}
					/>)}
			</tr>
		})
		: <tr className="no-items">
			<td className="colspanchange" colSpan={columns.length}>{noItems}</td>
		</tr>
}
