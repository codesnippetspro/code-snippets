import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippets } from '../../../hooks/useSnippetsAPI'
import { getSnippetDisplayName, getSnippetEditUrl, getSnippetType } from '../../../utils/snippets'
import { stripTags } from '../../../utils/text'
import { ListTable } from '../../common/ListTable'
import { SnippetTypeBadge } from '../../common/SnippetTypeBadge'
import type { Snippet } from '../../../types/Snippet'
import type { ListTableColumn } from '../../common/ListTable'

const columns: ListTableColumn<Snippet>[] = [
	{
		id: 'name',
		title: __('Snippet Name', 'code-snippets'),
		isPrimary: true,
		sortedValue: snippet => snippet.name,
		render: snippet =>
			<a href={getSnippetEditUrl(snippet)} target="_blank" rel="noreferrer">
				<strong>{getSnippetDisplayName(snippet)}</strong>
			</a>
	},
	{
		id: 'type',
		title: __('Type', 'code-snippets'),
		sortedValue: snippet => getSnippetType(snippet),
		render: snippet =>
			<SnippetTypeBadge snippetType={getSnippetType(snippet)} />
	},
	{
		id: 'desc',
		title: __('Description', 'code-snippets'),
		render: snippet => stripTags(snippet.desc)
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
					if ('detach' === action) {
						// TODO: update snippet data.
					}
				}}
			/>
		</div>
}
