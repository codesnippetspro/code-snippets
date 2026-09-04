import React, { useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import classnames from 'classnames'
import { DEMO_EXAMPLES } from './demoScript'
import { hasReached } from './types'
import type { DemoStage } from './types'

type DemoSidebarTab = 'actions' | 'history'

interface DemoQuotaWindow {
	label: string
	used: number
	limit: number
}

/** Sample usage, so the meters the real agent shows have something to draw. */
const DEMO_QUOTA: readonly DemoQuotaWindow[] = [
	{ label: __('Today', 'code-snippets'), used: 2, limit: 25 },
	{ label: __('This month', 'code-snippets'), used: 34, limit: 300 }
]

const FULL_PERCENT = 100

/** Conversations the scripted library already holds when the demo opens. */
const PAST_CONVERSATIONS: readonly string[] = [
	__('Hide the admin bar for subscribers', 'code-snippets'),
	__('Custom login screen logo', 'code-snippets')
]

interface DemoStatus {
	label: string
	modifier?: string
}

/**
 * How the conversation the walkthrough is having would be labelled in the
 * history rail at each point of the script.
 */
const currentStatus = (stage: DemoStage): DemoStatus => {
	if ('applying' === stage) {
		return { label: __('Updating', 'code-snippets') }
	}

	if (hasReached(stage, 'result-ready')) {
		return { label: __('Completed', 'code-snippets'), modifier: 'is-done' }
	}

	if (hasReached(stage, 'building')) {
		return { label: __('Building', 'code-snippets') }
	}

	if (hasReached(stage, 'plan-ready')) {
		return { label: __('Plan ready', 'code-snippets'), modifier: 'is-plan' }
	}

	return { label: __('Planning', 'code-snippets') }
}

const DemoQuota: React.FC = () =>
	<div className="ai-agent-quota">
		<h2 className="ai-agent-sidebar__heading">{__('AI usage', 'code-snippets')}</h2>

		{DEMO_QUOTA.map(({ label, used, limit }) =>
			<div key={label} className="ai-agent-quota__row">
				<div className="ai-agent-quota__label">
					<span>{label}</span>
					<span>{sprintf(
						/* translators: 1: used count, 2: limit count. */
						__('%1$d / %2$d', 'code-snippets'),
						used,
						limit
					)}</span>
				</div>

				<div className="ai-agent-quota__track is-ok">
					<div
						className="ai-agent-quota__fill"
						style={{ inlineSize: `${FULL_PERCENT * used / limit}%` }}
					/>
				</div>
			</div>)}
	</div>

const DemoHistory: React.FC<{ stage: DemoStage }> = ({ stage }) => {
	const status = currentStatus(stage)

	return (
		<div className="ai-agent-history">
			<ul className="ai-agent-history__list">
				{hasReached(stage, 'prompt-sent') &&
					<li className="ai-agent-history__item is-active">
						<span className="ai-agent-history__open">
							<span className="ai-agent-history__title">
								{__('Dismissible welcome banner', 'code-snippets')}
							</span>
							<span className={classnames('ai-agent-history__status', status.modifier)}>
								{status.label}
							</span>
						</span>
					</li>}

				{PAST_CONVERSATIONS.map(title =>
					<li key={title} className="ai-agent-history__item">
						<span className="ai-agent-history__open">
							<span className="ai-agent-history__title">{title}</span>
							<span className="ai-agent-history__status is-done">
								{__('Completed', 'code-snippets')}
							</span>
						</span>
					</li>)}
			</ul>
		</div>
	)
}

interface DemoTabProps {
	tab: DemoSidebarTab
	current: DemoSidebarTab
	label: string
	onSelect: (tab: DemoSidebarTab) => void
}

const DemoTab: React.FC<DemoTabProps> = ({ tab, current, label, onSelect }) =>
	<button
		type="button"
		role="tab"
		id={`ai-agent-demo-tab-${tab}`}
		aria-selected={tab === current}
		aria-controls={`ai-agent-demo-panel-${tab}`}
		className={classnames('ai-agent-sidebar__tab', { 'is-active': tab === current })}
		onClick={() => onSelect(tab)}
	>
		{label}
	</button>

/**
 * The sidebar the real agent uses, rendered inert. Only the tabs respond: the
 * prompt chips, usage meters and conversation list are here for the shape of
 * the page, not to be operated.
 */
export const DemoSidebar: React.FC<{ stage: DemoStage }> = ({ stage }) => {
	const [tab, setTab] = useState<DemoSidebarTab>('actions')

	return (
		<aside className="ai-agent-sidebar" aria-label={__('AI Agent tools', 'code-snippets')}>
			<div className="ai-agent-sidebar__tabs" role="tablist">
				<DemoTab
					tab="actions"
					current={tab}
					label={__('Prompt Actions', 'code-snippets')}
					onSelect={setTab}
				/>

				<DemoTab
					tab="history"
					current={tab}
					label={__('Past Conversations', 'code-snippets')}
					onSelect={setTab}
				/>
			</div>

			<div
				className="ai-agent-sidebar__panel"
				role="tabpanel"
				id="ai-agent-demo-panel-actions"
				aria-labelledby="ai-agent-demo-tab-actions"
				hidden={'actions' !== tab}
			>
				<div className="ai-agent-examples">
					<h2 className="ai-agent-sidebar__heading">{__('Prompt examples', 'code-snippets')}</h2>

					{DEMO_EXAMPLES.map(example =>
						<button key={example} type="button" className="ai-agent-examples__chip" disabled>
							{example}
						</button>)}
				</div>

				<DemoQuota />
			</div>

			<div
				className="ai-agent-sidebar__panel"
				role="tabpanel"
				id="ai-agent-demo-panel-history"
				aria-labelledby="ai-agent-demo-tab-history"
				hidden={'history' !== tab}
			>
				<DemoHistory stage={stage} />
			</div>
		</aside>
	)
}
