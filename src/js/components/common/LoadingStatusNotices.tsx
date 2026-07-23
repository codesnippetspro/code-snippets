import React from 'react'
import { __ } from '@wordpress/i18n'
import type { ReactNode } from 'react'

export interface LoadingStatusNoticesProps {
	isLoading: boolean
	errorMessage: string | undefined
	loadingNotice: ReactNode
	noticeLabel: string
}

export const LoadingStatusNotices: React.FC<LoadingStatusNoticesProps> = ({
	isLoading,
	errorMessage,
	loadingNotice,
	noticeLabel
}) => {
	switch (true) {
		case isLoading:
			return (
				<div aria-label={noticeLabel} className="notice inline" role="region">
					<p>{loadingNotice}</p>
				</div>
			)

		case errorMessage !== undefined:
			return (
				<div aria-label={noticeLabel} className="notice notice-error inline" role="region">
					<p>{errorMessage}</p>
				</div>
			)

		default:
			return (
				<div aria-label={noticeLabel} className="notice notice-warning inline" role="region">
					<p>{__('An unknown error occurred. Please try again.', 'code-snippets')}</p>
				</div>
			)
	}
}
