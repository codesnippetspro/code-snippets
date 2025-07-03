import React from 'react'
import type { SnippetType } from '../../types/Snippet'

export interface SnippetTypeBadgeProps {
	snippetType: SnippetType
}

export const SnippetTypeBadge: React.FC<SnippetTypeBadgeProps> = ({ snippetType }) =>
	<span
		className="badge snippet-type-badge"
		data-snippet-type={snippetType}
	>
		{snippetType}
	</span>
