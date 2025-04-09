import { __, sprintf } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippets } from '../../../hooks/useSnippetsAPI'
import { Snippet } from '../../../types/Snippet'
import { getSnippetType } from '../../../utils/snippets'
import { ListTable } from '../../common/ListTable'
import { SnippetTypeBadge } from '../../common/SnippetTypeBadge'
import type { ListTableColumn } from '../../common/ListTable'
import { addQueryArgs } from '@wordpress/url'

const columns: ListTableColumn<Snippet>[] = [
	{
		key: 'name',
		title: __('Snippet Name', 'code-snippets'),
		isHeading: true,
		getSortedValue: snippet => snippet.name
	},
	{
		key: 'type',
		title: __('Type', 'code-snippets'),
		getSortedValue: snippet => getSnippetType(snippet)
	},
	{
		key: 'desc',
		title: __('Description', 'code-snippets')
	}
]

const actions = {
	detach: __('Detach condition', 'code-snippets')
}

export const ConditionTable: React.FC = () => {
	const { snippet: condition } = useSnippetForm()
	const allSnippets = useSnippets()
	const [attachedSnippets, setAttachedSnippets] = useState<Snippet[]>()

	useEffect(() => {
		if (attachedSnippets === undefined) {
			setAttachedSnippets(
				condition.id
					? allSnippets?.filter(snippet => snippet.conditionId === condition.id)
					: undefined
			)
		}
	}, [attachedSnippets, allSnippets, condition.id])

	return attachedSnippets === undefined
		? null
		: <div>
			<h3>{__('Snippets using this Condition', 'code-snippets')}</h3>

			<ListTable
				items={attachedSnippets}
				getKey={snippet => snippet.id}
				columns={columns}
				actions={actions}
				noItems={__('No snippets are using this condition.', 'code-snippets')}
				handleBulkAction={(action, _selected) => {
					if (action === 'detach') {
						// TODO: update snippet data.
					}

				}}
				renderColumn={(column, snippet) => {
					switch (column.key) {
						case 'name':
							return (
								<a href={addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id: snippet.id })} target="_blank" rel="noreferrer">
									<strong>{snippet.name.trim() ? snippet.name : sprintf(__('Untitled #%d', 'code-snippets'), snippet.id)}</strong>
								</a>
							)

						case 'type':
							return <SnippetTypeBadge snippetType={getSnippetType(snippet)} />

						case 'desc':
							return snippet.desc

						default:
							return null
					}
				}}
			/>
		</div>
}
