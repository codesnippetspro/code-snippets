import React, { useState } from 'react'
import classnames from 'classnames'
import { __, _n, _x, sprintf } from '@wordpress/i18n'
import { buildUrl, updateQueryParams } from '../../../utils/urls'
import { Button } from '../Button'
import type { ReactNode } from 'react'

interface NavigationButtonProps {
	icon: ReactNode
	newPage: number
	className?: string
	disabled?: boolean
	helperText?: string
	setCurrentPage: (page: number) => void
	pageSearchParam?: string
}

const NavigationButton: React.FC<NavigationButtonProps> = ({
	icon,
	newPage,
	disabled,
	className,
	helperText,
	setCurrentPage,
	pageSearchParam
}) =>
	pageSearchParam
		? <>
			<a
				className={`${className} button`}
				href={buildUrl(window.location.href, { [pageSearchParam]: 1 === newPage ? undefined : newPage })}
				onClick={event => {
					event.preventDefault()
					setCurrentPage(newPage)
				}}
			>
				<span className="screen-reader-text">{helperText}</span>
				<span aria-hidden>{icon}</span>
			</a>{'\n'}
		</>
		: <Button className={className} onClick={() => setCurrentPage(newPage)} disabled={disabled}>
			<span className="screen-reader-text">{helperText}</span>
			<span aria-hidden>{icon}</span>
		</Button>

interface NavigationButtonsProps {
	currentPage: number
	disabled?: boolean
	pageSearchParam?: string
	setCurrentPage: (page: number) => void
}

const BackwardNavigationButtons: React.FC<NavigationButtonsProps> = ({ currentPage, ...buttonProps }) =>
	1 === currentPage
		? <>
			<span className="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>{'\n'}
			<span className="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>{'\n'}
		</>
		: <>
			<NavigationButton
				icon={<>&laquo;</>}
				newPage={1}
				className="first-page"
				/* translators: Hidden accessibility text. */
				helperText={__('First page', 'code-snippets')}
				{...buttonProps}
			/>{'\n'}
			<NavigationButton
				icon={<>&lsaquo;</>}
				newPage={Math.max(1, currentPage - 1)}
				className="prev-page"
				/* translators: Hidden accessibility text. */
				helperText={__('Previous page', 'code-snippets')}
				{...buttonProps}
			/>{'\n'}
		</>

interface ForwardNavigationButtonsProps extends NavigationButtonsProps {
	totalPages: number
}

const ForwardNavigationButtons: React.FC<ForwardNavigationButtonsProps> = ({ currentPage, totalPages, ...buttonProps }) =>
	totalPages === currentPage
		? <>
			<span className="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>{'\n'}
			<span className="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
		</>
		: <>
			<NavigationButton
				icon={<>&rsaquo;</>}
				newPage={Math.min(totalPages, currentPage + 1)}
				className="next-page"
				/* translators: Hidden accessibility text. */
				helperText={__('Next page', 'code-snippets')}
				{...buttonProps}
			/>{'\n'}
			<NavigationButton
				icon={<>&raquo;</>}
				newPage={totalPages}
				className="last-page"
				/* translators: Hidden accessibility text. */
				helperText={__('Last page', 'code-snippets')}
				{...buttonProps}
			/>{'\n'}
		</>

interface PagingInputProps {
	which: 'top' | 'bottom'
	totalPages: number
	inputName?: string
	disabled?: boolean
	inputValue: number
	setInputValue: (value: number) => void
	confirmInputValue: VoidFunction
}

const PagingInput: React.FC<PagingInputProps> = ({
	which,
	disabled,
	inputName,
	inputValue,
	totalPages,
	setInputValue,
	confirmInputValue
}) =>
	<input
		className="current-page"
		id={`current-page-selector-${which}`}
		aria-label={__('Current Page', 'code-snippets')}
		type="text"
		name={inputName}
		value={inputValue}
		size={totalPages.toString().length}
		disabled={disabled}
		aria-describedby="table-paging"
		onBlur={confirmInputValue}
		onChange={event => {
			const value = Number(event.target.value)

			if (value) {
				setInputValue(value)
			}
		}}
	/>

interface CurrentPageProps extends PagingInputProps {
	currentPage: number
}

const CurrentPage: React.FC<CurrentPageProps> = ({ which, totalPages, currentPage, ...inputProps }) =>
	'bottom' === which
		? <>
			{/* translators: Hidden accessibility text. */}
			<span className="screen-reader-text">{__('Current Page', 'code-snippets')}</span>
			<span className="paging-input">
				<span className="tablenav-paging-text">
					{/* translators: 1: Current page. */
						sprintf(_x('%s of ', 'paging', 'code-snippets'), currentPage)}
					<span className="total-pages">{totalPages}</span>
				</span>
			</span>
		</>
		: <span className="paging-input">
			<PagingInput which={which} totalPages={totalPages} {...inputProps} />
			<span className="tablenav-paging-text">
				{/* translators: 1: Current page. */
					_x(' of ', 'paging', 'code-snippets')}
				<span className="total-pages">{totalPages}</span>
			</span>
		</span>

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

type NavigationLinksProps = Omit<PaginationControlsProps, 'totalItems'>

const NavigationLinks: React.FC<NavigationLinksProps> = ({
	which,
	disabled,
	totalPages,
	inputValue,
	currentPage,
	setCurrentPage,
	setInputValue,
	pageSearchParam
}) => (
	<span className="pagination-links">
		<BackwardNavigationButtons
			disabled={disabled}
			currentPage={currentPage}
			setCurrentPage={setCurrentPage}
			pageSearchParam={pageSearchParam}
		/>
		<CurrentPage
			which={which}
			disabled={disabled}
			totalPages={totalPages}
			currentPage={currentPage}
			inputName={pageSearchParam}
			inputValue={inputValue}
			setInputValue={setInputValue}
			confirmInputValue={() => setCurrentPage(inputValue)}
		/>{'\n'}
		<ForwardNavigationButtons
			disabled={disabled}
			totalPages={totalPages}
			currentPage={currentPage}
			setCurrentPage={setCurrentPage}
			pageSearchParam={pageSearchParam}
		/>
	</span>
)

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
				<NavigationLinks
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
