import React from 'react'
import { Dispatch, Key, SetStateAction, ThHTMLAttributes } from 'react'
import { ListTableColumn, ListTableItemsProps } from './ListTable'

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

interface TableCellProps<T, K extends Key> extends Pick<TableItemsProps<T, K>, 'renderColumn'> {
	item: T
	column: ListTableColumn
}

const TableCell = <T, K extends Key>({ item, column, renderColumn }: TableCellProps<T, K>) => {
	const props: ThHTMLAttributes<HTMLTableCellElement> = {
		className: `${column.key}-column`,
		children: renderColumn(column, item)
	}

	return column.isHeading ? <th {...props} /> : <td {...props} />
}

export interface TableItemsProps<T, K extends Key> extends ListTableItemsProps<T, K> {
	setSelected: Dispatch<SetStateAction<Set<K>>>
}

export const TableItems = <T, K extends Key>({ items, getKey, columns, renderColumn, noItems, setSelected }: TableItemsProps<T, K>) =>
	0 < items.length
		? items.map(item =>
			<tr key={getKey(item)}>
				<CheckboxCell {...{ item, setSelected, getKey }} />

				{columns.map(column =>
					<TableCell key={column.key} {...{ item, column, renderColumn }} />)}
			</tr>
		)
		: <tr className="no-items">
			<td className="colspanchange" colSpan={columns.length}>{noItems}</td>
		</tr>
