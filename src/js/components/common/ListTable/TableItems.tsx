import React from 'react'
import type { Dispatch, Key, SetStateAction, ThHTMLAttributes } from 'react'
import type { ListTableColumn, ListTableItemsProps } from './ListTable'

interface CheckboxCellProps<T, K extends Key> extends Pick<TableItemsProps<T, K>, 'getKey'> {
	item: T
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

const CheckboxCell = <T, K extends Key>({ item, setSelected, getKey }: CheckboxCellProps<T, K>) =>
	<th scope="row" className="check-column">
		<input
			type="checkbox"
			name="checked[]"
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
	const props: ThHTMLAttributes<HTMLTableCellElement> = {
		className: `${column.id}-column`,
		children: column.render(item)
	}

	return column.isPrimary ? <th {...props} /> : <td {...props} />
}

export interface TableItemsProps<T, K extends Key>
	extends Pick<ListTableItemsProps<T, K>, 'items' | 'getKey' | 'columns' | 'noItems' | 'rowClassName'> {
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

export const TableItems = <T, K extends Key>({ items, getKey, columns, noItems, setSelected, rowClassName }: TableItemsProps<T, K>) =>
	0 < items.length
		? items.map(item =>
			<tr key={getKey(item)} className={rowClassName?.(item)}>
				<CheckboxCell {...{ item, setSelected, getKey }} />

				{columns.map(column =>
					<TableCell key={column.id} {...{ item, column }} />)}
			</tr>
		)
		: <tr className="no-items">
			<td className="colspanchange" colSpan={columns.length}>{noItems}</td>
		</tr>
