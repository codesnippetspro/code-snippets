import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import React from 'react'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'

export const STATUS_LABELS: Record<CloudStatus, string> = {
	[CloudStatus.Public]: __('Public', 'code-snippets'),
	[CloudStatus.Private]: __('Private', 'code-snippets'),
	[CloudStatus.Unverified]: __('Unverified', 'code-snippets'),
	[CloudStatus.AI_Verified]: __('AI Verified', 'code-snippets'),
	[CloudStatus.Pro_Verified]: __('Pro Verified', 'code-snippets')
}

export interface CloudStatusBadge {
	status: CloudStatus
}

export const CloudStatusBadge: React.FC<CloudStatusBadge> = ({ status }) =>
	<span className={classnames(
		'cloud-snippet-status',
		`cloud-snippet-status-${CloudStatus[status].toLowerCase().replace('_', '-')}`
	)}>
		{STATUS_LABELS[status]}
	</span>
