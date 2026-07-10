import React from 'react'
import classnames from 'classnames'
import type { ReactNode } from 'react'

export interface SnippetCardProps {
	className?: string
	isSelected?: boolean
	onSelectedChange?: (isSelected: boolean) => void
	selectionLabel?: string
	cornerControls?: ReactNode
	footer?: ReactNode
	children: ReactNode
}

/**
 * Shared card chrome for displaying a snippet in a card grid: border, inner
 * padding, footer strip, and a top corner holding optional extra controls
 * (such as an activation toggle) plus a selection checkbox for bulk actions.
 * Both cloud search results and local snippet cards render inside this shell
 * so the two views stay visually consistent.
 */
export const SnippetCard: React.FC<SnippetCardProps> = ({
	className,
	isSelected = false,
	onSelectedChange,
	selectionLabel,
	cornerControls,
	footer,
	children
}) =>
	<li
		className={classnames('code-snippets-card', className, {
			'is-selectable': undefined !== onSelectedChange,
			'is-selected': undefined !== onSelectedChange && isSelected,
			'has-corner-controls': undefined !== cornerControls
		})}
	>
		{undefined !== onSelectedChange || undefined !== cornerControls
			? <div className="snippet-card-corner">
				{cornerControls}
				{onSelectedChange
					? <input
						type="checkbox"
						className="snippet-card-select"
						checked={isSelected}
						aria-label={selectionLabel}
						onChange={event => onSelectedChange(event.target.checked)}
					/>
					: null}
			</div>
			: null}

		{children}

		{footer ? <footer>{footer}</footer> : null}
	</li>
