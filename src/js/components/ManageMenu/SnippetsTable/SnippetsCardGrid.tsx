import classnames from 'classnames'
import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { getSnippetDisplayName, getSnippetType } from '../../../utils/snippets/snippets'
import { TableNavigation } from '../../common/ListTable/TableNavigation'
import { ManageSnippetCard } from './ManageSnippetCard'
import type { ListTableAction, ListTableNavProps } from '../../common/ListTable'
import type { Snippet } from '../../../types/Snippet'
import type { Dispatch, ReactNode, SetStateAction } from 'react'

type SortKey = 'name' | 'type' | 'modified' | 'priority'

const SORT_LABELS: Record<SortKey, string> = {
	name: __('Name', 'code-snippets'),
	type: __('Type', 'code-snippets'),
	modified: __('Recently Modified', 'code-snippets'),
	priority: __('Priority', 'code-snippets')
}

const SORT_COMPARATORS: Record<SortKey, (snippetA: Snippet, snippetB: Snippet) => number> = {
	name: (snippetA, snippetB) =>
		getSnippetDisplayName(snippetA).localeCompare(getSnippetDisplayName(snippetB), undefined, { sensitivity: 'base' }),
	type: (snippetA, snippetB) => getSnippetType(snippetA).localeCompare(getSnippetType(snippetB)),
	modified: (snippetA, snippetB) =>
		new Date(snippetB.modified ?? 0).getTime() - new Date(snippetA.modified ?? 0).getTime(),
	priority: (snippetA, snippetB) => snippetA.priority - snippetB.priority
}

const isSortKey = (value: string): value is SortKey => value in SORT_COMPARATORS

interface CardsToolbarProps {
	sortKey: SortKey
	setSortKey: (sortKey: SortKey) => void
	allSelected: boolean
	toggleSelectAll: (isSelected: boolean) => void
}

const CardsToolbar: React.FC<CardsToolbarProps> = ({ sortKey, setSortKey, allSelected, toggleSelectAll }) =>
	<div className="alignleft actions snippets-cards-toolbar">
		<label className="snippets-cards-select-all">
			<input
				type="checkbox"
				checked={allSelected}
				aria-label={__('Select all snippets', 'code-snippets')}
				onChange={event => toggleSelectAll(event.target.checked)}
			/>
			{__('Select all', 'code-snippets')}
		</label>

		<label className="snippets-cards-sort">
			{__('Sort by', 'code-snippets')}
			<select
				value={sortKey}
				onChange={event => {
					if (isSortKey(event.target.value)) {
						setSortKey(event.target.value)
					}
				}}
			>
				{Object.entries(SORT_LABELS).map(([key, label]) =>
					<option key={key} value={key}>{label}</option>)}
			</select>
		</label>
	</div>

interface CardGridProps {
	snippets: Snippet[]
	noItems: ReactNode
	selected: Set<Snippet['id']>
	setSelected: Dispatch<SetStateAction<Set<Snippet['id']>>>
}

const CardGrid: React.FC<CardGridProps> = ({ snippets, noItems, selected, setSelected }) =>
	0 < snippets.length
		? <ul
			className={classnames('code-snippets-cards', 'snippets-card-grid', {
				'has-selection': snippets.some(snippet => selected.has(snippet.id))
			})}
		>
			{snippets.map(snippet =>
				<ManageSnippetCard
					key={snippet.id}
					snippet={snippet}
					isSelected={selected.has(snippet.id)}
					onSelectedChange={isSelected => {
						setSelected(previous => {
							const updated = new Set(previous)

							if (isSelected) {
								updated.add(snippet.id)
							} else {
								updated.delete(snippet.id)
							}

							return updated
						})
					}}
				/>)}
		</ul>
		: <p className="no-items">{noItems}</p>

interface ToggleSnippetsSelectionParams {
	previous: Set<Snippet['id']>
	snippets: Snippet[]
	isSelected: boolean
}

const toggleSnippetsSelection = ({ previous, snippets, isSelected }: ToggleSnippetsSelectionParams): Set<Snippet['id']> => {
	const updated = new Set(previous)

	snippets.forEach(snippet => {
		if (isSelected) {
			updated.add(snippet.id)
		} else {
			updated.delete(snippet.id)
		}
	})

	return updated
}

const getVisibleSelection = (selected: Set<Snippet['id']>, visible: Snippet[]): Set<Snippet['id']> =>
	new Set([...selected].filter(id => visible.some(snippet => snippet.id === id)))

const composeExtraTableNav = (
	toolbar: ReactNode,
	extraTableNav: ((which: 'top' | 'bottom') => ReactNode) | undefined
) =>
	function extraCardsTableNav(which: 'top' | 'bottom'): ReactNode {
		return <>
			{'top' === which ? toolbar : null}
			{extraTableNav?.(which)}
		</>
	}

interface SortedPagedSnippetsParams {
	snippets: Snippet[]
	sortKey: SortKey
	itemsPerPage: number | undefined
	currentPage: number
}

interface PagedSnippets {
	sortedSnippets: Snippet[]
	totalPages: number
	safePage: number
	visibleSnippets: Snippet[]
}

const useSortedPagedSnippets = ({ snippets, sortKey, itemsPerPage, currentPage }: SortedPagedSnippetsParams): PagedSnippets => {
	const sortedSnippets = useMemo(
		() => snippets.toSorted(SORT_COMPARATORS[sortKey]),
		[snippets, sortKey]
	)

	const totalPages = itemsPerPage ? Math.ceil(sortedSnippets.length / itemsPerPage) : 0
	const safePage = totalPages ? Math.min(currentPage, totalPages) : 1

	const visibleSnippets = useMemo(
		() => itemsPerPage
			? sortedSnippets.slice((safePage - 1) * itemsPerPage, safePage * itemsPerPage)
			: sortedSnippets,
		[sortedSnippets, itemsPerPage, safePage]
	)

	return { sortedSnippets, totalPages, safePage, visibleSnippets }
}

export interface SnippetsCardGridProps<A extends string>
	extends Pick<ListTableNavProps<Snippet['id'], A>, 'extraTableNav'> {
	snippets: Snippet[]
	actions: ListTableAction<A>[]
	doAction: (action: A, selected: Set<Snippet['id']>) => Promise<void>
	itemsPerPage?: number
	noItems: ReactNode
	beforeGrid?: ReactNode
}

export const SnippetsCardGrid = <A extends string>({
	snippets,
	actions,
	doAction,
	extraTableNav,
	itemsPerPage,
	noItems,
	beforeGrid
}: SnippetsCardGridProps<A>) => {
	const [selected, setSelected] = useState(() => new Set<Snippet['id']>())
	const [sortKey, setSortKey] = useState<SortKey>('name')
	const [currentPage, setCurrentPage] = useState(1)

	const { sortedSnippets, totalPages, safePage, visibleSnippets } =
		useSortedPagedSnippets({ snippets, sortKey, itemsPerPage, currentPage })
	const allSelected = 0 < visibleSnippets.length && visibleSnippets.every(snippet => selected.has(snippet.id))

	return (
		<div className="snippets-card-grid-container">
			<TableNavigation
				totalItems={sortedSnippets.length}
				totalPages={1 < totalPages ? totalPages : undefined}
				currentPage={safePage}
				setCurrentPage={setCurrentPage}
				pageSearchParam=""
				selected={getVisibleSelection(selected, visibleSnippets)}
				setSelected={setSelected}
				actions={actions}
				doAction={doAction}
				extraTableNav={composeExtraTableNav(
					<CardsToolbar
						sortKey={sortKey}
						setSortKey={setSortKey}
						allSelected={allSelected}
						toggleSelectAll={isSelected => {
							setSelected(previous => toggleSnippetsSelection({ previous, snippets: visibleSnippets, isSelected }))
						}}
					/>,
					extraTableNav
				)}
			>
				{beforeGrid}

				<CardGrid
					snippets={visibleSnippets}
					noItems={noItems}
					selected={selected}
					setSelected={setSelected}
				/>
			</TableNavigation>
		</div>
	)
}
