import classnames from 'classnames'
import React, { useMemo, useState } from 'react'
import { fetchQueryParam } from '../../../utils/urls'
import { TableNavigation } from '../../common/ListTable/TableNavigation'
import { ManageSnippetCard } from './ManageSnippetCard'
import type { ListTableAction, ListTableNavProps } from '../../common/ListTable'
import type { Snippet } from '../../../types/Snippet'
import type { Dispatch, ReactNode, SetStateAction } from 'react'

const PAGE_SEARCH_PARAM = 'paged'

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

interface PagedSnippetsParams {
	snippets: Snippet[]
	itemsPerPage: number | undefined
	currentPage: number
}

interface PagedSnippets {
	totalPages: number
	safePage: number
	visibleSnippets: Snippet[]
}

const usePagedSnippets = ({
	snippets,
	itemsPerPage,
	currentPage
}: PagedSnippetsParams): PagedSnippets => {
	const totalPages = itemsPerPage ? Math.ceil(snippets.length / itemsPerPage) : 0
	const safePage = totalPages ? Math.min(currentPage, totalPages) : 1

	const visibleSnippets = useMemo(
		() => itemsPerPage
			? snippets.slice((safePage - 1) * itemsPerPage, safePage * itemsPerPage)
			: snippets,
		[snippets, itemsPerPage, safePage]
	)

	return { totalPages, safePage, visibleSnippets }
}

export interface SnippetsCardGridProps<A extends string>
	extends Pick<ListTableNavProps<Snippet['id'], A>, 'extraTableNav' | 'endTableNav'> {
	snippets: Snippet[]
	actions: ListTableAction<A>[]
	doAction: (action: A | undefined, selected: Set<Snippet['id']>) => Promise<void>
	itemsPerPage?: number
	noItems: ReactNode
	beforeGrid?: ReactNode
}

export const SnippetsCardGrid = <A extends string>({
	snippets,
	actions,
	doAction,
	extraTableNav,
	endTableNav,
	itemsPerPage,
	noItems,
	beforeGrid
}: SnippetsCardGridProps<A>) => {
	const [selected, setSelected] = useState(() => new Set<Snippet['id']>())
	const [currentPage, setCurrentPage] = useState(
		() => Number(fetchQueryParam(PAGE_SEARCH_PARAM)) || 1
	)

	const { totalPages, safePage, visibleSnippets } = usePagedSnippets({
		snippets,
		itemsPerPage,
		currentPage
	})

	return (
		<div className="snippets-card-grid-container">
			<TableNavigation
				totalItems={snippets.length}
				totalPages={0 < totalPages ? totalPages : undefined}
				currentPage={safePage}
				setCurrentPage={setCurrentPage}
				pageSearchParam={PAGE_SEARCH_PARAM}
				selected={getVisibleSelection(selected, visibleSnippets)}
				setSelected={setSelected}
				selectAllKeys={visibleSnippets.map(snippet => snippet.id)}
				actions={actions}
				doAction={doAction}
				extraTableNav={extraTableNav}
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
