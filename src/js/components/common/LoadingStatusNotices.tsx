import React from 'react'
import { __ } from '@wordpress/i18n'
import { Notice } from './Notice'
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
				<Notice aria-label={noticeLabel} className="inline">
					<p>{loadingNotice}</p>
				</Notice>
			)

		case errorMessage !== undefined:
			return (
				<Notice aria-label={noticeLabel} className="inline" type="error">
					<p>{errorMessage}</p>
				</Notice>
			)

		default:
			return (
				<Notice aria-label={noticeLabel} className="inline" type="warning">
					<p>{__('An unknown error occurred. Please try again.', 'code-snippets')}</p>
				</Notice>
			)
	}
}
