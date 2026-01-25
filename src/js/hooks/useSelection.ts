import { useState } from 'react'

export const useSelection = <T, K extends string | number = string | number>(availableItems: T[], getKey: (item: T) => K) => {
	const [selectedItems, setSelectedItems] = useState<Set<K>>(new Set())

	const handleItemToggle = (itemKey: K) => {
		const newSelected = new Set(selectedItems)

		if (newSelected.has(itemKey)) {
			newSelected.delete(itemKey)
		} else {
			newSelected.add(itemKey)
		}

		setSelectedItems(newSelected)
	}

	const handleSelectAll = () => {
		if (selectedItems.size === availableItems.length) {
			setSelectedItems(new Set())
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
		selectedItems,
		handleItemToggle,
		handleSelectAll,
		clearSelection,
		getSelectedItems,
		isAllSelected
	}
}
