import React, { MouseEventHandler } from 'react'
import classnames from 'classnames'
import { SNIPPET_TYPES, SnippetType } from '../../types/Snippet'
import { addQueryArgs } from '@wordpress/url'
import { SNIPPET_TYPE_LABELS } from '../../utils/snippets/snippets'
import { Badge } from '../common/Badge'
import { __ } from '@wordpress/i18n'

const updateQueryParam = (name: string, value?: string) => {
	if ('URLSearchParams' in window) {
		const searchParams = new URLSearchParams(window.location.search)

		if (value) {
			searchParams.set(name, value)
		} else {
			searchParams.delete(name)
		}

		const newUrl = window.location.toString().replace(window.location.search, `?${searchParams}`)
		console.log(window.location.search, searchParams.toString(), newUrl)
		window.history.pushState({}, document.title, newUrl)
	}
}

interface TabProps {
	type?: SnippetType
	activeTab?: SnippetType
	setActiveTab: (tab?: SnippetType) => void
}

const Tab: React.FC<TabProps> = ({ type, activeTab, setActiveTab }) => {
	const tabName = type ?? 'all'

	const handleClick: MouseEventHandler<HTMLAnchorElement> = event => {
		event.preventDefault()
		setActiveTab(type)
		updateQueryParam('type', type)
	}

	return (
		<a
			href={addQueryArgs(window.location.href, { type: tabName })}
			className={classnames('nav-tab', `${tabName}-tab`, { 'nav-tab-active': type === activeTab })}
			onClick={handleClick}
		>
			<span className={`${tabName}-label`}>
				{type ? SNIPPET_TYPE_LABELS[type] : __('All Snippets', 'code-snippets')}
			</span>
			{type && <Badge name={type} />}
		</a>
	)
}

export interface SnippetTypeTabsProps {
	activeTab?: SnippetType
	setActiveTab: (tab?: SnippetType) => void
}

export const SnippetTypeTabs: React.FC<SnippetTypeTabsProps> = ({ activeTab, setActiveTab }) =>
	<h2 className="nav-tab-wrapper snippet-type-tabs">
		<Tab activeTab={activeTab} setActiveTab={setActiveTab} />

		{SNIPPET_TYPES.map(type =>
			<Tab key={type} type={type} activeTab={activeTab} setActiveTab={setActiveTab} />)}
	</h2>
