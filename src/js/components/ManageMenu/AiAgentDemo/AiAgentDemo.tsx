import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { WithSnippetsAPIContext } from '../../../hooks/useSnippetsAPI'
import { Notice } from '../../common/Notice'
import { DemoCallout } from '../../common/demo/DemoCallout'
import { DemoPageHeader } from '../../common/demo/DemoPageHeader'
import { useMarkDemoSeen } from '../../common/demo/useDemoSeen'
import { getCallout } from './callouts'
import { DemoPlanCard } from './DemoPlanCard'
import { DemoPromptBox } from './DemoPromptBox'
import { DemoResultCard } from './DemoResultCard'
import { DemoSidebar } from './DemoSidebar'
import { DemoMessage, DemoTransit, DemoTyping } from './DemoThread'
import { AiAgentDemoUpsell } from './DemoUpsell'
import { DEMO_PROMPT, DEMO_REFINEMENT_REPLY } from './demoScript'
import { useAiAgentDemo } from './useAiAgentDemo'
import { hasReached } from './types'
import type { DemoStage } from './types'

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

// eslint-disable-next-line max-lines-per-function -- the timeline reads as one sequence.
const AiAgentDemoPage: React.FC = () => {
	const {
		stage,
		typedPrompt,
		typedRefinement,
		snippets,
		saveError,
		hasStarted,
		isFinished,
		reducedMotion,
		play,
		skip,
		replay
	} = useAiAgentDemo()

	const markSeen = useMarkDemoSeen('ai-agent')
	const upsellRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (!isFinished) {
			return
		}

		markSeen()

		// Settle on the foot of the page so the closing panel and the snippets
		// it refers to are in view together.
		window.scrollTo({
			top: document.body.scrollHeight,
			behavior: reducedMotion ? 'auto' : 'smooth'
		})
	}, [isFinished, markSeen, reducedMotion])

	const showPromptBox = !hasReached(stage, 'prompt-sent')

	return (
		<div className="ai-agent ai-agent-demo">
			<DemoPageHeader
				title={__('AI Agent', 'code-snippets')}
				description={__('A guided walkthrough of the Pro AI Agent. Press play and watch it plan, build, and refine a snippet on your site.', 'code-snippets')}
				hasStarted={hasStarted}
				isFinished={isFinished}
				onPlay={play}
				onSkip={skip}
				onReplay={replay}
			/>

			<div className="screen-reader-text" aria-live="polite">{STAGE_ANNOUNCEMENTS[stage]}</div>

			<DemoCallout key={stage} callout={getCallout(stage)} />

			<div className="ai-agent-layout">
				<div className="ai-agent-layout__main">
					{saveError && <Notice type="error">
						<p>{__('The demo could not save its snippets to your site.', 'code-snippets')}</p>
						<p>{saveError}</p>
					</Notice>}

					<div className="ai-agent-thread">
						{hasReached(stage, 'prompt-sent') && <DemoMessage speaker="user">{DEMO_PROMPT}</DemoMessage>}

						{'planning' === stage && <>
							<DemoTransit label={__('Sending your prompt to Code Snippets Cloud.', 'code-snippets')} />
							<DemoTyping label={__('Planning your snippets…', 'code-snippets')} />
						</>}

						{hasReached(stage, 'plan-ready') && !hasReached(stage, 'building') &&
							<DemoPlanCard accepted={hasReached(stage, 'plan-accepted')} />}

						{'building' === stage && <>
							<DemoTransit label={__('Building the snippets in Code Snippets Cloud.', 'code-snippets')} />
							<DemoTyping label={__('Building the code…', 'code-snippets')} />
						</>}

						{hasReached(stage, 'result-ready') && <DemoResultCard
							stage={stage}
							snippets={snippets}
							typedRefinement={typedRefinement}
						/>}

						{'applying' === stage && <DemoTyping label={__('Applying your changes…', 'code-snippets')} />}

						{hasReached(stage, 'saved') && <DemoMessage speaker="assistant">{DEMO_REFINEMENT_REPLY}</DemoMessage>}

						{isFinished && <div ref={upsellRef}>
							<AiAgentDemoUpsell snippets={snippets} onReplay={replay} />
						</div>}
					</div>

					{showPromptBox && <DemoPromptBox
						value={typedPrompt}
						typing={'typing-prompt' === stage}
						submitLabel={__('Send', 'code-snippets')}
						placeholder={__('Describe what you want to build…', 'code-snippets')}
					/>}
				</div>

				<div className="ai-agent-layout__sidebar">
					<DemoSidebar />
				</div>
			</div>
		</div>
	)
}

export const AiAgentDemo: React.FC = () =>
	<WithRestAPIContext>
		<WithSnippetsAPIContext>
			<AiAgentDemoPage />
		</WithSnippetsAPIContext>
	</WithRestAPIContext>
