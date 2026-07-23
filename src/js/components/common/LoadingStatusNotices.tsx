import React from 'react'
import { __ } from '@wordpress/i18n'
import type { ReactNode } from 'react'

export interface LoadingStatusNoticesProps {
	isLoading: boolean
	errorMessage: string | undefined
	errorPrefix: ReactNode
	loadingNotice: ReactNode
}

export const LoadingStatusNotices: React.FC<LoadingStatusNoticesProps> = ({
	isLoading,
	errorMessage,
	errorPrefix,
	loadingNotice
}) => {
	switch (true) {
		case errorMessage !== undefined:
			return (
				<div className="notice notice-error inline">
					<p>{errorPrefix} <span>{errorMessage}</span></p>
				</div>
			)

		case isLoading:
			return (
				<div className="notice inline">
					<p>{loadingNotice}</p>
				</div>
			)

		default:
			return (
				<div className="notice notice-warning inline">
					<p>{__('An unknown error occurred. Please try again.', 'code-snippets')}</p>
				</div>
			)
	}
}
