import React, { useEffect, useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { fetchConstQueryParam, fetchQueryParam, updateQueryParams } from '../../utils/urls'
import { DismissibleNotice } from '../common/Notice'
import { SUBPAGES, Toolbar } from '../common/Toolbar'
import { UpsellPage } from '../common/UpsellDialog'
import { AiAgentDemo } from './AiAgentDemo/AiAgentDemo'
import { BlueprintsDemo } from './BlueprintsDemo/BlueprintsDemo'
import { CommunityCloud } from './CommunityCloud/CommunityCloud'
import { SnippetsTable } from './SnippetsTable'

const repositionTableOptionsSettings = () => {
	const screenOptionsForm = document.getElementById('adv-settings')
	const tableOptions = screenOptionsForm?.querySelector<HTMLFieldSetElement>('fieldset.table-options-prefs')
	// Locate the first column-visibility fieldset that is NOT the table-options one.
	// This relies on WordPress core rendering #adv-settings with .metabox-prefs fieldsets.
	// Verified against WP 6.5+ (core/Screen_Options). If WP changes this structure the
	// reordering will silently no-op, which is acceptable — it is a cosmetic improvement only.
	const columns = Array.from(
		screenOptionsForm?.querySelectorAll<HTMLFieldSetElement>('fieldset.metabox-prefs') ?? []
	).find(fieldset => !fieldset.classList.contains('table-options-prefs'))

	if (screenOptionsForm && tableOptions && columns) {
		screenOptionsForm.insertBefore(tableOptions, columns)
	}
}

const getNoticeText = (result: string) => {
	switch (result) {
		case 'deleted':
			return __('Snippet <strong>deleted</strong>.', 'code-snippets')

		default:
			return undefined
	}
}

const PageNotices = () => {
	const [noticeText, setNoticeText] = useState(() => {
		const result = fetchQueryParam('result')
		updateQueryParams({ result: undefined })
		return result && getNoticeText(result)
	})

	return noticeText
		? <DismissibleNotice
			className="code-snippets-notice"
			onDismiss={() => {
				setNoticeText(undefined)
			}}
			type="success">
			<p>{createInterpolateElement(noticeText, { strong: <strong /> })}</p>
		</DismissibleNotice>
		: null
}

interface PageContentParams {
	subpage: typeof SUBPAGES[number]
}

const PageContent: React.FC<PageContentParams> = ({ subpage }) => {
	switch (subpage) {
		case 'snippets':
			return <SnippetsTable />

		case 'cloud-community':
			return <CommunityCloud />

		case 'ai-agent':
			return <AiAgentDemo />

		case 'blueprints':
			return <BlueprintsDemo />

		case 'cloud-library':
			return <UpsellPage />
	}
}

export const ManageMenu = () => {
	const subpage = useMemo(() =>
		fetchConstQueryParam('subpage', SUBPAGES) ?? SUBPAGES[0], [])

	useEffect(() => {
		if ('snippets' === subpage) {
			repositionTableOptionsSettings()
		}
	}, [subpage])

	return (
		<>
			<Toolbar />
			<PageNotices />
			<PageContent subpage={subpage} />
		</>
	)
}
