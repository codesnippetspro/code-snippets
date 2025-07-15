import React from 'react'
import classnames from 'classnames'
import type { SnippetType } from '../../types/Snippet'

export type BadgeName = SnippetType | 'core' | 'pro' | 'ai' | 'cloud' | 'bundles' | 'cloud_search'

const badgeIcons: Partial<Record<BadgeName, string>> = {
	cond: 'randomize',
	cloud: 'cloud',
	bundles: 'screenoptions',
	cloud_search: 'search'
}

export interface BadgeProps {
	name: BadgeName
	small?: boolean
	inverted?: boolean
	content?: boolean
}

export const Badge: React.FC<BadgeProps> = ({ name, small, inverted }) =>
	<span className={classnames('badge', `${name}-badge`, { 'small-badge': small, 'inverted-badge': inverted })}>
		{badgeIcons[name]
			? <span className={`dashicons dashicons-${badgeIcons[name]}`} />
			: name}
	</span>
