import React from 'react'
import { __ } from '@wordpress/i18n'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'

export const getCloudStatusLabel = (status: CloudStatus) => {
	switch (status) {
		case CloudStatus.Public:
			return __('Public', 'code-snippets')

		case CloudStatus.Private:
			return __('Private', 'code-snippets')

		case CloudStatus.Unverified:
			return __('Unverified', 'code-snippets')

		case CloudStatus.AI_Verified:
			return __('AI Verified', 'code-snippets')

		case CloudStatus.Pro_Verified:
			return __('Pro Verified', 'code-snippets')

		default:
			return __('Unknown', 'code-snippets')
	}
}

const STATUS_BADGES: Record<CloudStatus, string | undefined> = {
	[CloudStatus.Public]: 'public',
	[CloudStatus.Private]: 'private',
	[CloudStatus.Unverified]: 'failure',
	[CloudStatus.AI_Verified]: 'info',
	[CloudStatus.Pro_Verified]: 'success'
}

const getStatusClassName = (status: CloudStatus): string | undefined =>
	(CloudStatus[status] as typeof CloudStatus[number] | undefined)?.toLowerCase().replace('_', '-')

export interface CloudStatusBadgeProps {
	status: CloudStatus
}

export const CloudStatusIndicator: React.FC<CloudStatusBadgeProps> = ({ status }) =>
	<span className={`cloud-snippet-status cloud-snippet-status-${getStatusClassName(status) ?? 'unknown'}`}>
		{getCloudStatusLabel(status)}
	</span>

export const CloudStatusBadge: React.FC<CloudStatusBadgeProps> = ({ status }) =>
	<span className={`badge ${STATUS_BADGES[status] ?? 'neutral'}-badge`}>
		{getCloudStatusLabel(status)}
	</span>
