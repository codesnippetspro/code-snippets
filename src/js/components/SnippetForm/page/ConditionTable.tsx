import { __ } from '@wordpress/i18n'
import React, { useEffect, useMemo, useState } from 'react'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { isNetworkAdmin } from '../../../utils/screen'
import { buildSnippetSelectOptionGroups, getSnippetDisplayName, getSnippetEditUrl, getSnippetType, isCondition } from '../../../utils/snippets/snippets'
import { stripTags } from '../../../utils/text'
import { Button } from '../../common/Button'
import { ListTable } from '../../common/ListTable'
import { Select } from '../../common/Select'
import { SnippetTypeBadge } from '../../common/SnippetTypeBadge'
import type { SelectGroup } from '../../../types/SelectOption'
import type { Snippet } from '../../../types/Snippet'
import type { ListTableBulkAction, ListTableColumn } from '../../common/ListTable'

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

interface SnippetSelectorProps {
	onSubmit: (selected: Snippet) => void
}

const SnippetSelector: React.FC<SnippetSelectorProps> = ({ onSubmit }) => {
	const [currentValue, setCurrentValue] = useState<Snippet>()
	const { snippetsList } = useSnippetsList()
	const { snippet: condition } = useSnippetForm()

	const options: SelectGroup<Snippet>[] | undefined = useMemo(
		() =>
			snippetsList &&
			buildSnippetSelectOptionGroups(snippetsList.filter(snippet =>
				!isCondition(snippet) && snippet.conditionId !== condition.id)),
		[snippetsList, condition.id]
	)

	return (
		<div className="snippet-selector">
			<Select
				options={options}
				currentValue={currentValue}
				onSelect={selectedValue => setCurrentValue(selectedValue)}
			/>

			<Button
				primary
				large
				type="submit"
				onClick={event => {
					event.preventDefault()

					if (currentValue) {
						onSubmit(currentValue)
					}
				}}>{__('Attach', 'code-snippets')}</Button>
		</div>
	)
}

export const ConditionTable: React.FC = () => {
	const { snippet: condition } = useSnippetForm()
	const { snippetsAPI: { attach, detach } } = useRestAPI()
	const { snippetsList, refreshSnippetsList } = useSnippetsList()
	const [attachedSnippets, setAttachedSnippets] = useState<Snippet[]>()

	useEffect(() => {
		setAttachedSnippets(
			condition.id
				? snippetsList?.filter(snippet => snippet.conditionId === condition.id)
				: undefined
		)
	}, [snippetsList, condition.id])

	const actions: ListTableBulkAction<Snippet['id']>[] = useMemo(() => [
		{
			name: __('Detach condition', 'code-snippets'),
			apply: snippetIds =>
				Promise.allSettled(
					[...snippetIds.values()].map(snippetId => detach({ id: snippetId, network: isNetworkAdmin() })))
					.then(refreshSnippetsList)
		}
	], [detach, refreshSnippetsList])

	const actionColumn: ListTableColumn<Snippet> = useMemo(() => ({
		id: 'actions',
		render: snippet =>
			<Button onClick={() => {
				detach(snippet).then(refreshSnippetsList).catch(handleUnknownError)
			}}>{__('Detach', 'code-snippets')}</Button>
	}), [detach, refreshSnippetsList])

	return attachedSnippets === undefined
		? null
		: <form className="condition-snippets-table">
			<h3>{__('Snippets using this Condition', 'code-snippets')}</h3>

			<SnippetSelector
				onSubmit={selectedSnippet => {
					attach({ id: selectedSnippet.id, network: isNetworkAdmin(), conditionId: condition.id })
						.then(refreshSnippetsList)
						.catch(handleUnknownError)
				}} />

			<ListTable
				items={attachedSnippets}
				columns={[...columns, actionColumn]}
				actions={actions}
				getKey={snippet => snippet.id}
				noItems={__('No snippets are using this condition.', 'code-snippets')}
			/>
		</form>
}
