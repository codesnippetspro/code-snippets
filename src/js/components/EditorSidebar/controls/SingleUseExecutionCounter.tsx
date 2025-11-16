import React, { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Tooltip } from '../../common/Tooltip'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

const renderCountIcon = (count: number) =>
	<span className="single-use-count-icon" aria-label={__('Execution count this session', 'code-snippets')}>
		{count}
	</span>

/**
 * Reuses the activation switch row to display execution count for single-use snippets.
 * Shows how many times the snippet has been executed during the edit page session.
 */
export const SingleUseExecutionCounter: React.FC = () => {
	const { currentNotice } = useSnippetForm()
	const [count, setCount] = useState(0)
	const [overflowing, setOverflowing] = useState(false)
	const lastNoticeTimestampRef = useRef<number>(0)
	const tooltipRef = useRef<HTMLDivElement | null>(null)

	useEffect(() => {
		const noticeMessage = currentNotice?.[1]
		const now = Date.now()
		const MIN_NOTICE_INTERVAL = 500
		
		// Only count if this is a success notice with "executed" and at least 500ms has passed
		if (currentNotice && 
			'updated' === currentNotice[0] && 
			noticeMessage?.includes('executed') &&
			MIN_NOTICE_INTERVAL < now - lastNoticeTimestampRef.current) {
			setCount(prev => prev + 1)
			lastNoticeTimestampRef.current = now
		}
	}, [currentNotice])

	// Measure on layout to decide if we need to flip the tooltip horizontally.
	useLayoutEffect(() => {
		const EDGE_PADDING = 8
		if (!tooltipRef.current) { return }
		const icon = tooltipRef.current.querySelector('.single-use-count-icon')
		const content = tooltipRef.current.querySelector('.tooltip-content')
		if (!icon || !content) { return }
		// Tooltip-content is visibility hidden until hover but still has layout; width measurable.
		const iconRect = icon.getBoundingClientRect()
		const contentWidth = content.getBoundingClientRect().width
		const rightEdge = iconRect.right + contentWidth
		const viewportWidth = window.innerWidth
		setOverflowing(rightEdge > viewportWidth - EDGE_PADDING)
	}, [count])

	return (
		<div className="inline-form-field activation-switch-container single-use-counter">
			<h4>{__('Status')}</h4>
			<div className="single-use-counter-display">
				<span className="single-use-count-label">{__('Executed', 'code-snippets')}</span>
				<div ref={tooltipRef} style={{display:'inline-block'}}>
					<Tooltip inline start={overflowing} end={!overflowing} icon={renderCountIcon(count)}>
						{__('Execution count for the current edit session.', 'code-snippets')}
					</Tooltip>
				</div>
				<span className="single-use-count-label">
					{ __(1 === count ? 'time' : 'times', 'code-snippets') }
				</span>
			</div>
		</div>
	)
}
