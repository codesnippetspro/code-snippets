import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../../hooks/useRestAPI'
import { ScreenMetaSlot } from '../../../common/ScreenMetaSlot'
import { DemoCallout } from '../common/DemoCallout'
import { DemoPageHeader } from '../common/DemoPageHeader'
import { DemoSpotlight } from '../common/DemoSpotlight'
import { useMarkDemoSeen } from '../common/useDemoSeen'
import { getCallout } from './callouts'
import { DemoPlanCard } from './DemoPlanCard'
import { DemoPromptBox } from './DemoPromptBox'
import { DemoResultCard } from './DemoResultCard'
import { DemoSidebar } from './DemoSidebar'
import { DemoMessage, DemoTransit, DemoTyping } from './DemoThread'
import { AiAgentDemoUpsell } from './DemoUpsell'
import { DEMO_EXAMPLES, DEMO_PROMPT, DEMO_REFINEMENT_REPLY } from './demoScript'
import { useAiAgentDemo } from './useAiAgentDemo'
import { hasReached } from './types'
import type { DemoSnippet, DemoStage } from './types'
import type { RefObject } from 'react'

/**
 * Only the steps that point at one particular thing are spotlit. Typing,
 * planning and building animate in place and need no dimming to be followed.
 */
const STAGE_SPOTLIGHTS: Partial<Record<DemoStage, { target: string, padding?: number }>> = {
	'plan-ready': { target: '.ai-agent-plan', padding: 6 },
	'plan-accepted': { target: '.ai-agent-plan', padding: 6 },
	'result-ready': { target: '.ai-agent-result__rows', padding: 8 },
	'applying': { target: '.ai-agent-result__edit', padding: 6 }
}

/**
 * Text announced to assistive technology as each stage begins, so the
 * walkthrough is followable without watching the animation.
 */
const STAGE_ANNOUNCEMENTS: Partial<Record<DemoStage, string>> = {
	'typing-prompt': __('Writing a prompt.', 'code-snippets'),
	'prompt-sent': __('Prompt sent.', 'code-snippets'),
	'planning': __('Planning your snippets.', 'code-snippets'),
	'plan-ready': __('The plan is ready.', 'code-snippets'),
	'plan-accepted': __('Plan accepted.', 'code-snippets'),
	'building': __('Building the code.', 'code-snippets'),
	'result-ready': __('Snippets created.', 'code-snippets'),
	'refine-open': __('Choosing snippets to refine.', 'code-snippets'),
	'typing-refinement': __('Writing a refinement.', 'code-snippets'),
	'applying': __('Applying your changes.', 'code-snippets'),
	'saved': __('Snippets updated and saved to your site.', 'code-snippets'),
	'finished': __('Demo complete.', 'code-snippets')
}

interface AgentThreadProps {
	stage: DemoStage
	snippets: DemoSnippet[]
	typedRefinement: string
}

const AgentThread = ({ stage, snippets, typedRefinement }: AgentThreadProps) =>
	<div className="ai-agent-thread">
		{hasReached(stage, 'prompt-sent') && <DemoMessage speaker="user">{DEMO_PROMPT}</DemoMessage>}

		{'planning' === stage && (
			<>
				<DemoTransit label={__('Sending your prompt to Code Snippets Cloud.', 'code-snippets')} />
				<DemoTyping label={__('Planning your snippets…', 'code-snippets')} />
			</>)}

		{hasReached(stage, 'plan-ready') && !hasReached(stage, 'building') && (
			<DemoPlanCard accepted={'plan-accepted' === stage} />)}

		{'building' === stage && (
			<>
				<DemoTransit label={__('Building the snippets in Code Snippets Cloud.', 'code-snippets')} />
				<DemoTyping label={__('Building the code…', 'code-snippets')} />
			</>)}

		{hasReached(stage, 'result-ready') && (
			<DemoResultCard
				stage={stage}
				snippets={snippets}
				typedRefinement={typedRefinement}
			/>)}

		{'applying' === stage && <DemoTyping label={__('Applying your changes…', 'code-snippets')} />}

		{hasReached(stage, 'saved') && <DemoMessage speaker="assistant">{DEMO_REFINEMENT_REPLY}</DemoMessage>}
	</div>

interface DemoContentProps extends AgentThreadProps {
	stage: DemoStage
	typedPrompt: string
	promptSent: boolean
	upsellRef: RefObject<HTMLDivElement>
	onReplay: VoidFunction
	onCancel: VoidFunction
	isFinished: boolean
}

const DemoContent = ({
	stage,
	typedPrompt,
	promptSent,
	upsellRef,
	onReplay,
	onCancel,
	isFinished,
	...threadProps
}: DemoContentProps) =>
	<section className="ai-agent ai-agent-demo" aria-label={__('AI Agent demo', 'code-snippets')}>
		<div className="screen-reader-text" aria-live="polite">{STAGE_ANNOUNCEMENTS[stage]}</div>

		<DemoCallout callout={getCallout(stage)} onClose={onCancel} />

		<DemoSpotlight {...STAGE_SPOTLIGHTS[stage]} />

		<div className="ai-agent-layout">
			<div className="ai-agent-layout__main">
				{!promptSent && (
					<div className="ai-agent-empty">
						<p className="ai-agent-empty__eyebrow">{__('Start with these prompts', 'code-snippets')}</p>

						<div className="ai-agent-empty__chips">
							{DEMO_EXAMPLES.map(example =>
								<button key={example} type="button" className="ai-agent-empty__chip" disabled>
									{example}
								</button>)}
						</div>
					</div>)}

				<AgentThread {...threadProps} stage={stage} />

				<DemoPromptBox
					value={hasReached(stage, 'planning') ? '' : typedPrompt}
					typing={'typing-prompt' === stage}
					pressed={'prompt-sent' === stage}
					disabled={promptSent}
					submitLabel={__('Send', 'code-snippets')}
					placeholder={__('Describe what you want to build…', 'code-snippets')}
				/>
			</div>

			<div className="ai-agent-layout__sidebar">
				<DemoSidebar stage={stage} />
			</div>
		</div>

		{isFinished && (
			<div
				ref={upsellRef}
				className="ai-agent-demo__closing"
				role="region"
				aria-label={__('AI Agent demo complete', 'code-snippets')}
			>
				<AiAgentDemoUpsell onReplay={onReplay} />
			</div>)}
	</section>

const AiAgentDemoPage: React.FC = () => {
	const { stage, typedPrompt, typedRefinement, snippets, hasStarted, isFinished, reducedMotion, play, skip, replay } = useAiAgentDemo()

	const markSeen = useMarkDemoSeen('ai-agent')
	const upsellRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (!isFinished) {
			return
		}

		markSeen()

		// The closing panel sits below the agent, so bring it into view rather
		// than leaving the walkthrough to end off-screen.
		upsellRef.current?.scrollIntoView({
			behavior: reducedMotion ? 'auto' : 'smooth',
			block: 'end'
		})
	}, [isFinished, markSeen, reducedMotion])

	return (
		<>
			<ScreenMetaSlot />

			<DemoPageHeader
				title={__('AI Agent', 'code-snippets')}
				description={__('A guided walkthrough of the Pro AI Agent. Press play and watch it plan, build, and refine a snippet on your site.', 'code-snippets')}
				hasStarted={hasStarted}
				isFinished={isFinished}
				onPlay={play}
				onSkip={skip}
				onReplay={replay}
			/>

			<DemoContent
				stage={stage}
				typedPrompt={typedPrompt}
				typedRefinement={typedRefinement}
				snippets={snippets}
				promptSent={hasReached(stage, 'prompt-sent')}
				upsellRef={upsellRef}
				onReplay={replay}
				onCancel={skip}
				isFinished={isFinished}
			/>
		</>
	)
}

export const AiAgentDemo: React.FC = () =>
	<WithRestAPIContext>
		<AiAgentDemoPage />
	</WithRestAPIContext>
