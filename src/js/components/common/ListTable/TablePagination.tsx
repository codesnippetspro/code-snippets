import React, { useState } from 'react'
import classnames from 'classnames'
import { __, _n, _x, sprintf } from '@wordpress/i18n'
import { buildUrl, updateQueryParam } from '../../../utils/urls'
import { Button } from '../Button'
import type { ListTablePaginationProps } from './ListTable'
import type { ReactNode } from 'react'

interface NavigationButtonProps {
	icon: ReactNode
	newPage: number
	className?: string
	helperText?: string
	renderAsLinks?: boolean
	setCurrentPage: (page: number) => void
}

const NavigationButton: React.FC<NavigationButtonProps> = ({ icon, newPage, className, helperText, renderAsLinks, setCurrentPage }) =>
	renderAsLinks
		? <>
			<a
				className={`${className} button`}
				href={buildUrl(window.location.href, { paged: 1 === newPage ? undefined : newPage })}
				onClick={event => {
					event.preventDefault()
					setCurrentPage(newPage)
				}}
			>
				<span className="screen-reader-text">{helperText}</span>
				<span aria-hidden>{icon}</span>
			</a>{'\n'}
		</>
		: <Button className={className} onClick={() => setCurrentPage(newPage)}>
			<span className="screen-reader-text">{helperText}</span>
			<span aria-hidden>{icon}</span>
		</Button>

interface NavigationButtonsProps {
	currentPage: number
	renderAsLinks?: boolean
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
	inputValue: number
	setInputValue: (value: number) => void
	confirmInputValue: VoidFunction
}

const PagingInput: React.FC<PagingInputProps> = ({ which, totalPages, inputValue, setInputValue, confirmInputValue }) =>
	<>
		<label htmlFor={`current-page-selector-${which}`} className="screen-reader-text">
			{/* translators: Hidden accessibility text. */
				__('Current Page', 'code-snippets')}
		</label>
		<input
			className="current-page"
			id={`current-page-selector-${which}`}
			type="text"
			name="paged"
			value={inputValue}
			size={totalPages.toString().length}
			aria-describedby="table-paging"
			onBlur={confirmInputValue}
			onChange={event => {
				const value = Number(event.target.value)

				if (value) {
					setInputValue(value)
				}
			}}
		/>
	</>

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
			<PagingInput
				which={which}
				totalPages={totalPages}
				{...inputProps}
			/>
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
	useQueryVars?: boolean
	setInputValue: (value: number) => void
	setCurrentPage: (page: number) => void
}

const PaginationControls: React.FC<PaginationControlsProps> = ({
	which,
	totalPages,
	totalItems,
	inputValue,
	currentPage,
	useQueryVars,
	setCurrentPage,
	setInputValue
}) =>
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
			{/* translators: %s: Number of items. */}
			{sprintf(_n('%s item', '%s items', totalItems), totalItems)}
		</span>{'\n'}

		<span className="pagination-links">
			<BackwardNavigationButtons
				currentPage={currentPage}
				renderAsLinks={useQueryVars}
				setCurrentPage={setCurrentPage}
			/>

			<CurrentPage
				which={which}
				totalPages={totalPages}
				currentPage={currentPage}
				inputValue={inputValue}
				setInputValue={setInputValue}
				confirmInputValue={() => setCurrentPage(inputValue)}
			/>{'\n'}

			<ForwardNavigationButtons
				totalPages={totalPages}
				currentPage={currentPage}
				renderAsLinks={useQueryVars}
				setCurrentPage={setCurrentPage}
			/>
		</span>
	</form>

export interface TablePaginationProps extends Omit<ListTablePaginationProps, 'totalPages'>,
	Required<Pick<ListTablePaginationProps, 'totalPages'>> {
	which: 'top' | 'bottom'
	totalItems: number
	currentPage: number
	setCurrentPage: (page: number) => void
}

export const TablePagination: React.FC<TablePaginationProps> = ({
	which,
	totalItems,
	currentPage,
	totalPages,
	useQueryVars,
	setCurrentPage
}) => {
	const [inputValue, setInputValue] = useState(currentPage)

	const setCurrentPageSafe = (page: number) => {
		if (page) {
			const validPage = Math.max(1, Math.min(page, totalPages))
			setInputValue(validPage)
			setCurrentPage(validPage)

			if (useQueryVars) {
				updateQueryParam('paged', 1 === validPage ? undefined : validPage)
			}
		}
	}

	return (
		<PaginationControls
			which={which}
			useQueryVars
			currentPage={currentPage}
			totalItems={totalItems}
			totalPages={totalPages}
			inputValue={inputValue}
			setInputValue={setInputValue}
			setCurrentPage={setCurrentPageSafe}
		/>
	)
}
