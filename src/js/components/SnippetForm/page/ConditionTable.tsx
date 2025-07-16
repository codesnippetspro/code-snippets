import { __ } from '@wordpress/i18n'
import React, { useEffect, useMemo, useState } from 'react'
import { Spinner } from '@wordpress/components'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { useSnippetsList } from '../../../hooks/useSnippetsList'
import { handleUnknownError } from '../../../utils/errors'
import { isNetworkAdmin } from '../../../utils/screen'
import { buildSnippetSelectOptionGroups, getSnippetDisplayName, getSnippetEditUrl, getSnippetType, isCondition } from '../../../utils/snippets/snippets'
import { stripTags } from '../../../utils/text'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { ListTable } from '../../common/ListTable'
import { Select } from '../../common/Select'
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
				{snippet.active
					? <strong>{getSnippetDisplayName(snippet)}</strong>
					: getSnippetDisplayName(snippet)}
			</a>
	},
	{
		id: 'type',
		title: __('Type', 'code-snippets'),
		sortedValue: snippet => getSnippetType(snippet),
		render: snippet =>
			<Badge name={getSnippetType(snippet)} />
	},
	{
		id: 'desc',
		title: __('Description', 'code-snippets'),
		render: snippet => stripTags(snippet.desc)
	},
	{
		id: 'actions',
		render: snippet => <DetachConditionButton snippet={snippet} />
	}
]

const DetachConditionButton: React.FC<{ snippet: Snippet }> = ({ snippet }) => {
	const { snippetsAPI: { detach } } = useRestAPI()
	const { refreshSnippetsList } = useSnippetsList()
	const [showSpinner, setShowSpinner] = useState(false)

	const handleClick = () => {
		setShowSpinner(true)

		detach(snippet)
			.then(() => refreshSnippetsList())
			.catch((error: unknown) => {
				console.error(error)
				setShowSpinner(false)
			})
	}

	return showSpinner
		? <Spinner />
		: <Button onClick={handleClick}>{__('Detach', 'code-snippets')}</Button>
}

const SnippetSelector: React.FC = () => {
	const { snippetsList, refreshSnippetsList } = useSnippetsList()
	const { snippet: condition } = useSnippetForm()
	const { snippetsAPI: { attach } } = useRestAPI()
	const [currentValue, setCurrentValue] = useState<Snippet>()
	const [isAttaching, setIsAttaching] = useState(false)

	const options: SelectGroup<Snippet>[] | undefined = useMemo(
		() =>
			snippetsList &&
			buildSnippetSelectOptionGroups(snippetsList.filter(snippet =>
				!isCondition(snippet) && snippet.conditionId !== condition.id)),
		[snippetsList, condition.id]
	)

	const handleSubmit = () => {
		if (currentValue) {
			setIsAttaching(true)

			attach({ id: currentValue.id, network: isNetworkAdmin(), conditionId: condition.id })
				.then(() => refreshSnippetsList())
				.catch(handleUnknownError)
				.finally(() => setIsAttaching(false))
		}
	}

	return (
		<div className="snippet-selector">
			<Select
				options={options}
				currentValue={currentValue}
				onSelect={selectedValue => setCurrentValue(selectedValue)}
			/>

			<Button primary large type="submit" onClick={handleSubmit} disabled={isAttaching}>
				{isAttaching ? <Spinner /> : __('Attach', 'code-snippets')}
			</Button>
		</div>
	)
}

export const ConditionTable: React.FC = () => {
	const { snippet: condition } = useSnippetForm()
	const { snippetsAPI: { detach } } = useRestAPI()
	const { snippetsList, refreshSnippetsList } = useSnippetsList()
	const [attachedSnippets, setAttachedSnippets] = useState<Snippet[]>([])

	useEffect(() => {
		if (snippetsList) {
			setAttachedSnippets(
				condition.id
					? snippetsList.filter(snippet => snippet.conditionId === condition.id)
					: []
			)
		}
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

	return condition.id
		? <form className="condition-snippets-table">
			<h3>{__('Snippets using this Condition', 'code-snippets')}</h3>

			<SnippetSelector />

			<ListTable
				items={attachedSnippets}
				columns={columns}
				actions={actions}
				getKey={snippet => snippet.id}
				noItems={snippetsList === undefined
					? <Spinner />
					: __('No snippets are using this condition.', 'code-snippets')}
			/>
		</form>
		: null
}
