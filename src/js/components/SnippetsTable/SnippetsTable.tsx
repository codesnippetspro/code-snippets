import { __ } from "@wordpress/i18n"
import React, { useState } from "react"
import { WithRestAPIContext } from '../../hooks/useRestAPI'
import { WithSnippetsListContext } from '../../hooks/useSnippetsList'
import { SNIPPET_STATUSES, SNIPPET_TYPES, SnippetStatus, SnippetType } from '../../types/Snippet'
import { SnippetsListTable } from './SnippetsListTable'
import { SnippetTypeTabs } from './SnippetTypeTabs'

const fetchQueryParam = (name: string): string | undefined => {
	const urlParams = new URLSearchParams(window.location.search)
	return urlParams.get(name) ?? undefined
}

const Page = () => {
	const [currentType, setCurrentType] = useState<SnippetType | undefined>(() => {
		const type = fetchQueryParam('type')
		return type && SNIPPET_TYPES.includes(type as SnippetType) ? (type as SnippetType) : undefined
	})

	const [currentStatus, setCurrentStatus] = useState<SnippetStatus | undefined>(() => {
		const status = fetchQueryParam('status')
		return status && SNIPPET_STATUSES.includes(status as SnippetStatus) ? (status as SnippetStatus) : undefined
	})

	return (
		<div className="wrap">
			<h1>{__('Manage Code Snippets', 'code-snippets')}</h1>

			<SnippetTypeTabs activeTab={currentType} setActiveTab={setCurrentType} />

			<SnippetsListTable currentType={currentType} currentStatus={currentStatus} setCurrentStatus={setCurrentStatus} />
		</div>
	)
}

export const SnippetsTable: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsListContext>
			<Page />
		</WithSnippetsListContext>
	</WithRestAPIContext>
