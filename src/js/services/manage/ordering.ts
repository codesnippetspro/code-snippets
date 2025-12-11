import $ from 'jquery'
import { updateSnippet } from './requests'
import type { Snippet } from '../../types/Snippet'

export const handleSnippetOrdering = () => {
	const table = $('.wp-list-table tbody')

	if (!table.length) {
		return
	}

	(<any> table).sortable({
		items: '> tr',
		cursor: 'move',
		axis: 'y',
		containment: 'parent',
		cancel: 'input, textarea, button, select, option, a',
		placeholder: 'sortable-placeholder',
		update: () => {
			const rows = table.find('tr')

			rows.each(function (index) {
				const row = $(this)
				const priorityInput = row.find('.snippet-priority')
				const currentPriority = parseInt(<string> priorityInput.val(), 10)
				const newPriority = index + 1

				if (currentPriority !== newPriority) {
					priorityInput.val(newPriority)
					const snippet: Partial<Snippet> = { priority: newPriority }
					updateSnippet('priority', row[0], snippet)
				}
			})
		}
	})
}