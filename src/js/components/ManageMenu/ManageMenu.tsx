import React, { useEffect, useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { fetchConstQueryParam, fetchQueryParam, updateQueryParams } from '../../utils/urls'
import { DismissibleNotice, type NoticeType } from '../common/Notice'
import { SUBPAGES, Toolbar } from '../common/Toolbar'
import { AiAgentDemo } from './AiAgentDemo/AiAgentDemo'
import { BlueprintsDemo } from './BlueprintsDemo/BlueprintsDemo'
import { CloudLibraryDemo } from './CloudLibraryDemo/CloudLibraryDemo'
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

const getNotice = (result: string): { text: string, type: NoticeType } | undefined => {
	switch (result) {
		case 'deleted':
			return { text: __('Snippet <strong>deleted</strong>.', 'code-snippets'), type: 'success' }

		case 'executed':
			return { text: __('Snippet <strong>executed</strong>.', 'code-snippets'), type: 'success' }

		case 'run-once-failed':
			return {
				text: __('The snippet could not be run. Check that its code is valid and try again.', 'code-snippets'),
				type: 'error'
			}

		case 'run-once-safe-mode':
			return {
				text: __('Snippet execution is disabled on this site, so the snippet was not run.', 'code-snippets'),
				type: 'warning'
			}

		default:
			return undefined
	}
}

const PageNotices = () => {
	const [notice, setNotice] = useState(() => {
		const result = fetchQueryParam('result')
		updateQueryParams({ result: undefined })
		return result ? getNotice(result) : undefined
	})

	return notice
		? <DismissibleNotice
			className="code-snippets-notice"
			onDismiss={() => {
				setNotice(undefined)
			}}
			type={notice.type}>
			<p>{createInterpolateElement(notice.text, { strong: <strong /> })}</p>
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
			return <CloudLibraryDemo />
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
