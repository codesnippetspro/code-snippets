import React from 'react'
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
}

const TableCell = <T, >({ item, column }: TableCellProps<T>) => {
	const className = classnames(`${column.id}-column`, `column-${column.id}`, { hidden: column.isHidden })

	return column.isHeading
		? <th className={className}>{column.render(item)}</th>
		: <td className={className}>{column.render(item)}</td>
}

export interface TableRowsProps<T, K extends Key>
	extends Pick<ListTableRowsProps<T, K>, 'getKey' | 'columns' | 'noItems' | 'rowClassName'> {
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
	rowClassName
}: TableRowsProps<T, K>
) =>
	0 < items.length
		? items.map(item =>
			<tr key={getKey(item)} className={rowClassName?.(item)}>
				<CheckboxCell {...{ item, selected, setSelected, getKey }} />

				{columns.map(column =>
					<TableCell key={column.id} item={item} column={column} />)}
			</tr>
		)
		: <tr className="no-items">
			<td className="colspanchange" colSpan={columns.length}>{noItems}</td>
		</tr>
