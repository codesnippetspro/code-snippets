import React, { useState } from 'react'
import classnames from 'classnames'
import { __, _n, sprintf } from '@wordpress/i18n'
import { updateQueryParams } from '../../../utils/urls'
import { TablePaginationNavigation } from './TablePaginationNavigation'

interface PaginationControlsProps {
	which: 'top' | 'bottom'
	inputValue: number
	totalPages: number
	totalItems: number
	currentPage: number
	disabled?: boolean
	setInputValue: (value: number) => void
	setCurrentPage: (page: number) => void
	pageSearchParam?: string
}

const PaginationControls: React.FC<PaginationControlsProps> = ({
	which,
	disabled,
	totalPages,
	totalItems,
	inputValue,
	currentPage,
	pageSearchParam,
	setCurrentPage,
	setInputValue
}) => {
	const navLabel = 'top' === which
		? __('Pagination, before the table', 'code-snippets')
		: __('Pagination, after the table', 'code-snippets')

	return (
		<nav className="tablenav-pages-nav" aria-label={navLabel}>
			<form
				className={classnames('tablenav-pages', {
					'one-page': totalPages && 1 === totalPages,
					'no-pages': !totalPages
				})}
				onSubmit={event => {
					event.preventDefault()
					setCurrentPage(inputValue)
				}}
			>
				<span className="displaying-num">
					{
						sprintf(
							// translators: %s: Number of items.
							_n('%s item', '%s items', totalItems, 'code-snippets'),
							totalItems
						)
					}
				</span>{'\n'}
				<TablePaginationNavigation
					{...{ which, disabled, totalPages, inputValue, currentPage, pageSearchParam, setCurrentPage, setInputValue }}
				/>
			</form>
		</nav>
	)
}

export interface TablePaginationProps {
	which: 'top' | 'bottom'
	disabled?: boolean
	totalItems: number
	totalPages: number
	currentPage: number
	pageSearchParam?: string
	setCurrentPage: (page: number) => void
}

export const TablePagination: React.FC<TablePaginationProps> = ({
	which,
	disabled,
	totalItems,
	totalPages,
	currentPage,
	setCurrentPage,
	pageSearchParam
}) => {
	const [inputValue, setInputValue] = useState(currentPage)

	const setCurrentPageSafe = (page: number) => {
		if (page) {
			const validPage = Math.max(1, Math.min(page, totalPages))
			setInputValue(validPage)
			setCurrentPage(validPage)

			if (pageSearchParam) {
				updateQueryParams({ [pageSearchParam]: 1 === validPage ? undefined : validPage })
			}
		}
	}

	return (
		<PaginationControls
			{...{ which, inputValue, currentPage, totalItems, totalPages, disabled, setInputValue }}
			setCurrentPage={setCurrentPageSafe}
		/>
	)
}
