import classnames from 'classnames'
import React, { useMemo, useState } from 'react'
import { fetchQueryParam } from '../../../utils/urls'
import { TableNavigation } from '../../common/ListTable/TableNavigation'
import { getSnippetKey } from '../../../utils/snippets/snippets'
import { ManageSnippetCard } from './ManageSnippetCard'
import type { ListTableAction, ListTableNavProps } from '../../common/ListTable'
import type { Snippet } from '../../../types/Snippet'
import type { SnippetKey } from '../../../utils/snippets/snippets'
import type { Dispatch, ReactNode, SetStateAction } from 'react'

const PAGE_SEARCH_PARAM = 'paged'

interface CardGridProps {
	snippets: Snippet[]
	noItems: ReactNode
	selected: Set<SnippetKey>
	setSelected: Dispatch<SetStateAction<Set<SnippetKey>>>
}

const CardGrid: React.FC<CardGridProps> = ({ snippets, noItems, selected, setSelected }) =>
	0 < snippets.length
		? <ul
			className={classnames('code-snippets-cards', 'snippets-card-grid', {
				'has-selection': snippets.some(snippet => selected.has(getSnippetKey(snippet)))
			})}
		>
			{snippets.map(snippet =>
				<ManageSnippetCard
					key={getSnippetKey(snippet)}
					snippet={snippet}
					isSelected={selected.has(getSnippetKey(snippet))}
					onSelectedChange={isSelected => {
						setSelected(previous => {
							const updated = new Set(previous)

							if (isSelected) {
								updated.add(getSnippetKey(snippet))
							} else {
								updated.delete(getSnippetKey(snippet))
							}

							return updated
						})
					}}
				/>)}
		</ul>
		: <p className="no-items">{noItems}</p>

const getVisibleSelection = (selected: Set<SnippetKey>, visible: Snippet[]): Set<SnippetKey> =>
	new Set([...selected].filter(key => visible.some(snippet => getSnippetKey(snippet) === key)))

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
	extends Pick<ListTableNavProps<SnippetKey, A>, 'extraTableNav' | 'endTableNav'> {
	snippets: Snippet[]
	actions: ListTableAction<A>[]
	doAction: (action: A | undefined, selected: Set<SnippetKey>) => Promise<void>
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
	const [selected, setSelected] = useState(() => new Set<SnippetKey>())
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
				selectAllKeys={visibleSnippets.map(getSnippetKey)}
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
