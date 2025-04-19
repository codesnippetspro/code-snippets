import { __ } from '@wordpress/i18n'
import React, { useEffect, useMemo, useState } from 'react'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippetsAPI } from '../../../hooks/useSnippetsAPI'
import { handleUnknownError } from '../../../utils/errors'
import { isNetworkAdmin } from '../../../utils/screen'
import { buildSnippetSelectOptions, getSnippetDisplayName, getSnippetEditUrl, getSnippetType, isCondition } from '../../../utils/snippets'
import { stripTags } from '../../../utils/text'
import { Button } from '../../common/Button'
import { ListTable } from '../../common/ListTable'
import { SingleSelect } from '../../common/Select'
import { SnippetTypeBadge } from '../../common/SnippetTypeBadge'
import type { Dispatch, SetStateAction } from 'react'
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
	snippets: Snippet[] | undefined
	condition: Snippet
	onSubmit: (selected: Snippet) => void
}

const SnippetSelector: React.FC<SnippetSelectorProps> = ({ snippets, condition, onSubmit }) => {
	const [currentValue, setCurrentValue] = useState<Snippet>()

	const options: SelectGroup<Snippet>[] | undefined = useMemo(
		() =>
			snippets &&
			buildSnippetSelectOptions(snippets.filter(snippet => !isCondition(snippet) && snippet.conditionId !== condition.id)),
		[snippets, condition.id]
	)

	return (
		<div className="snippet-selector">
			<SingleSelect
				options={options}
				currentValue={currentValue}
				onChange={selectedValue => setCurrentValue(selectedValue)}
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

interface TableFormProps {
	allSnippets: Snippet[] | undefined
	setAllSnippets: Dispatch<SetStateAction<Snippet[] | undefined>>
	attachedSnippets: Snippet[]
}

const TableForm: React.FC<TableFormProps> = ({ allSnippets, setAllSnippets, attachedSnippets }) => {
	const { attach, detach } = useSnippetsAPI()
	const { snippet: condition } = useSnippetForm()

	const actions: ListTableBulkAction<Snippet['id']>[] = useMemo(() => [
		{
			name: __('Detach condition', 'code-snippets'),
			apply: snippetIds =>
				Promise
					.allSettled(
						[...snippetIds.values()].map(snippetId =>
							detach({ id: snippetId, network: isNetworkAdmin() })))
					.then(() => setAllSnippets(undefined))
		}
	], [detach, setAllSnippets])

	const actionColumn: ListTableColumn<Snippet> = useMemo(() => ({
		id: 'actions',
		render: snippet =>
			<Button onClick={() => {
				detach(snippet)
					.then(() => setAllSnippets(undefined))
					.catch(handleUnknownError)
			}}>{__('Detach', 'code-snippets')}</Button>
	}), [detach, setAllSnippets])

	return (
		<form className="condition-snippets-table">
			<h3>{__('Snippets using this Condition', 'code-snippets')}</h3>

			<SnippetSelector
				snippets={allSnippets}
				condition={condition}
				onSubmit={selectedSnippet => {
					attach({ id: selectedSnippet.id, network: isNetworkAdmin(), conditionId: condition.id })
						.then(() => setAllSnippets(undefined))
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
	)
}

export const ConditionTable: React.FC = () => {
	const { snippet: condition } = useSnippetForm()
	const { fetchAll } = useSnippetsAPI()
	const [allSnippets, setAllSnippets] = useState<Snippet[]>()
	const [attachedSnippets, setAttachedSnippets] = useState<Snippet[]>()

	useEffect(() => {
		if (!allSnippets) {
			fetchAll(isNetworkAdmin())
				.then(response => {
					setAllSnippets(response)
					setAttachedSnippets(
						condition.id ? response.filter(snippet => snippet.conditionId === condition.id) : undefined)
				})
				.catch(handleUnknownError)
		}
	}, [allSnippets, fetchAll, condition.id])

	return attachedSnippets === undefined
		? null
		: <TableForm
			allSnippets={allSnippets}
			setAllSnippets={setAllSnippets}
			attachedSnippets={attachedSnippets}
		/>
}
