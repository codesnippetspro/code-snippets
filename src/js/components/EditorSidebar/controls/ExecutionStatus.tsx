import React, { useEffect, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'

/**
 * Displays how many times a single-use snippet has been executed
 * during the lifetime of the current edit page session.
 */
export const ExecutionStatus: React.FC = () => {
	const { currentNotice } = useSnippetForm()
	const [count, setCount] = useState(0)
	const lastNoticeRef = useRef<string | undefined>()

	useEffect(() => {
		const noticeMessage = currentNotice?.[1]
		if (currentNotice && currentNotice[0] === 'updated' && noticeMessage?.includes('executed')) {
			// Prevent double-counting if notice unchanged.
			if (lastNoticeRef.current !== noticeMessage) {
				setCount(previous => previous + 1)
				lastNoticeRef.current = noticeMessage
			}
		}
	}, [currentNotice])

	return (
		<div className="inline-form-field execution-status">
			<strong>{__('Status', 'code-snippets')}:</strong>{' '}
			<span>{__('Executed', 'code-snippets')} ({count}) {__('times', 'code-snippets')}</span>
		</div>
	)
}
