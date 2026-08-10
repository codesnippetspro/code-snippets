import { useState } from 'react'

export interface UseSelection<T, K> {
	selectAll: VoidFunction
	toggleItem: (itemKey: K) => void
	selectedItems: Set<K>
	isAllSelected: boolean
	availableItems: T[]
	clearSelection: VoidFunction
	getSelectedItems: () => T[]
}

export const useSelection = <T, K>(availableItems: T[], getKey: (item: T) => K): UseSelection<T, K> => {
	const [selectedItems, setSelectedItems] = useState<Set<K>>(new Set())

	const toggleItem = (itemKey: K) => {
		const newSelected = new Set(selectedItems)

		if (newSelected.has(itemKey)) {
			newSelected.delete(itemKey)
		} else {
			newSelected.add(itemKey)
		}

		setSelectedItems(newSelected)
	}

	const selectAll = () => {
		if (selectedItems.size === availableItems.length) {
			clearSelection()
		} else {
			setSelectedItems(new Set(availableItems.map(getKey)))
		}
	}

	const clearSelection = () => {
		setSelectedItems(new Set())
	}

	const getSelectedItems = () =>
		availableItems.filter(item =>
			selectedItems.has(getKey(item)))

	const isAllSelected = selectedItems.size === availableItems.length && 0 < availableItems.length

	return {
		availableItems,
		selectedItems,
		toggleItem,
		selectAll,
		clearSelection,
		getSelectedItems,
		isAllSelected
	}
}
