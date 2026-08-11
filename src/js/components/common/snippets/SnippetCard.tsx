import React from 'react'
import classnames from 'classnames'
import type { ReactNode } from 'react'

export interface SnippetCardProps {
	className?: string
	isSelected?: boolean
	onSelectedChange?: (isSelected: boolean) => void
	selectionLabel?: string
	footer?: ReactNode
	footerStatus?: ReactNode
	children: ReactNode
}

/**
 * Shared card chrome for displaying a snippet in a card grid: border, inner
 * padding, footer strip, and a top corner holding a selection checkbox for
 * bulk actions.
 * The footer is split into a status region at the inline start and an actions
 * region at the inline end; the status region is always rendered so actions
 * stay end-aligned even when no status is provided. Both cloud search results
 * and local snippet cards render inside this shell so the two views stay
 * visually consistent.
 */
export const SnippetCard: React.FC<SnippetCardProps> = ({
	className,
	isSelected = false,
	onSelectedChange,
	selectionLabel,
	footer,
	footerStatus,
	children
}) =>
	<li
		className={classnames('code-snippets-card', className, {
			'is-selectable': undefined !== onSelectedChange,
			'is-selected': undefined !== onSelectedChange && isSelected
		})}
	>
		{undefined !== onSelectedChange
			? <div className="snippet-card-corner">
				<input
					type="checkbox"
					className="snippet-card-select"
					checked={isSelected}
					aria-label={selectionLabel}
					onChange={event => onSelectedChange(event.target.checked)}
				/>
			</div>
			: null}

		{children}

		{undefined !== footer || undefined !== footerStatus
			? <footer>
				<div className="snippet-card-footer-status">{footerStatus}</div>
				<div className="snippet-card-footer-actions">{footer}</div>
			</footer>
			: null}
	</li>
