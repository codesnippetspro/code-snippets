import classnames from 'classnames'
import React, { useMemo, useState } from 'react'
import { getSnippetDisplayName } from '../../../utils/snippets/snippets'
import { TableNavigation } from '../../common/ListTable/TableNavigation'
import { ManageSnippetCard } from './ManageSnippetCard'
import type { ListTableAction, ListTableNavProps } from '../../common/ListTable'
import type { Snippet } from '../../../types/Snippet'
import type { Dispatch, ReactNode, SetStateAction } from 'react'

const compareSnippetNames = (snippetA: Snippet, snippetB: Snippet): number =>
	getSnippetDisplayName(snippetA).localeCompare(getSnippetDisplayName(snippetB), undefined, { sensitivity: 'base' })

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

const getVisibleSelection = (selected: Set<Snippet['id']>, visible: Snippet[]): Set<Snippet['id']> =>
	new Set([...selected].filter(id => visible.some(snippet => snippet.id === id)))

interface SortedPagedSnippetsParams {
	snippets: Snippet[]
	itemsPerPage: number | undefined
	currentPage: number
}

interface PagedSnippets {
	sortedSnippets: Snippet[]
	totalPages: number
	safePage: number
	visibleSnippets: Snippet[]
}

const useSortedPagedSnippets = ({ snippets, itemsPerPage, currentPage }: SortedPagedSnippetsParams): PagedSnippets => {
	const sortedSnippets = useMemo(
		() => snippets.toSorted(compareSnippetNames),
		[snippets]
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
	extends Pick<ListTableNavProps<Snippet['id'], A>, 'extraTableNav' | 'selectAll' | 'endTableNav'> {
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
	selectAll,
	endTableNav,
	itemsPerPage,
	noItems,
	beforeGrid
}: SnippetsCardGridProps<A>) => {
	const [selected, setSelected] = useState(() => new Set<Snippet['id']>())
	const [currentPage, setCurrentPage] = useState(1)

	const { sortedSnippets, totalPages, safePage, visibleSnippets } =
		useSortedPagedSnippets({ snippets, itemsPerPage, currentPage })

	return (
		<div className="snippets-card-grid-container">
			<TableNavigation
				totalItems={sortedSnippets.length}
				totalPages={totalPages || undefined}
				currentPage={safePage}
				setCurrentPage={setCurrentPage}
				pageSearchParam=""
				selected={getVisibleSelection(selected, visibleSnippets)}
				setSelected={setSelected}
				visibleKeys={visibleSnippets.map(snippet => snippet.id)}
				actions={actions}
				doAction={doAction}
				extraTableNav={extraTableNav}
				selectAll={selectAll}
				endTableNav={endTableNav}
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
