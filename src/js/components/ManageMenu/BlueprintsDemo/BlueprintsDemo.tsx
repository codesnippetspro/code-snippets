import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { DemoPageHeader } from '../../common/demo/DemoPageHeader'
import { DemoSpotlight } from '../../common/demo/DemoSpotlight'
import { DemoCallout } from '../../common/demo/DemoCallout'
import { DemoUpsell } from '../../common/demo/DemoUpsell'
import { useMarkDemoSeen } from '../../common/demo/useDemoSeen'
import { BlueprintFormPanel } from './BlueprintFormPanel'
import { BlueprintSidebar } from './BlueprintSidebar'
import { GeneratedNotice } from './GeneratedNotice'
import { getCallout } from './callouts'
import { BLUEPRINT_DESCRIPTION, BLUEPRINT_TITLE, getSection } from './demoBlueprint'
import { useBlueprintsDemo } from './useBlueprintsDemo'
import { hasReached } from './types'
import type { DemoStage } from './types'

/**
 * Only the steps that point at one particular control are spotlit. Filling in
 * the three sections needs no dimming: the active tab and the panel crossfade
 * already say where to look.
 */
const STAGE_SPOTLIGHTS: Partial<Record<DemoStage, { target: string, padding?: number }>> = {
	generating: { target: '.blueprint-form-sidebar__generate', padding: 8 },
	generated: { target: '.blueprints-demo-generated', padding: 6 }
}

const STAGE_ANNOUNCEMENTS: Partial<Record<DemoStage, string>> = {
	general: __('Filling in the general settings.', 'code-snippets'),
	attributes: __('Adding the shortcode attributes.', 'code-snippets'),
	output: __('Setting the shortcode output.', 'code-snippets'),
	generating: __('Generating the code.', 'code-snippets'),
	generated: __('The snippet has been generated.', 'code-snippets'),
	finished: __('Demo complete.', 'code-snippets')
}

const BlueprintHeader: React.FC = () =>
	<header className="blueprint-detail__header">
		<div className="blueprint-detail__header-content">
			<div>
				<h3>{BLUEPRINT_TITLE}</h3>
				<p>{BLUEPRINT_DESCRIPTION}</p>
			</div>
		</div>
	</header>

// eslint-disable-next-line max-lines-per-function -- the page reads as one sequence.
const BlueprintsDemoPage: React.FC = () => {
	const {
		stage,
		activeSection,
		isFading,
		sectionsBrowsable,
		hasStarted,
		isFinished,
		reducedMotion,
		selectSection,
		play,
		skip,
		replay
	} = useBlueprintsDemo()

	const generatedRef = useRef<HTMLDivElement>(null)
	const showGenerated = hasReached(stage, 'generated')
	const markSeen = useMarkDemoSeen('blueprints')

	useEffect(() => {
		if (isFinished) {
			markSeen()
		}
	}, [isFinished, markSeen])

	useEffect(() => {
		const behavior = reducedMotion ? 'auto' : 'smooth'

		if ('generated' === stage) {
			generatedRef.current?.scrollIntoView({ behavior, block: 'center' })
		}

		// Once the closing panel is in place, settle on the foot of the page so
		// both it and the generated notice are in view together.
		if ('finished' === stage) {
			window.scrollTo({ top: document.body.scrollHeight, behavior })
		}
	}, [reducedMotion, stage])

	return (
		<div className="blueprints-demo">
			<DemoPageHeader
				title={__('Blueprints', 'code-snippets')}
				description={__('A guided walkthrough of Pro Blueprints. Press play and watch a shortcode blueprint fill itself in and generate a snippet.', 'code-snippets')}
				hasStarted={hasStarted}
				isFinished={isFinished}
				onPlay={play}
				onSkip={skip}
				onReplay={replay}
			/>

			<div className="screen-reader-text" aria-live="polite">{STAGE_ANNOUNCEMENTS[stage]}</div>

			<DemoCallout callout={getCallout(stage)} />

			<DemoSpotlight {...STAGE_SPOTLIGHTS[stage]} />

			<div className="blueprint-detail">
				<BlueprintHeader />

				<div className="blueprint-form-layout">
					<BlueprintSidebar
						activeSection={activeSection}
						browsable={sectionsBrowsable}
						generating={'generating' === stage}
						onSelect={selectSection}
					/>

					<BlueprintFormPanel section={getSection(activeSection)} isFading={isFading} />
				</div>
			</div>

			{showGenerated && <GeneratedNotice ref={generatedRef} />}

			{isFinished && <DemoUpsell
				title={__('That was a demo — Blueprints build the code for you', 'code-snippets')}
				onReplay={replay}
			>
				<p>{__('The whole walkthrough was scripted and ran inside this plugin. No code was generated and nothing was saved.', 'code-snippets')}</p>
				<p>{__('Code Snippets Pro ships blueprints for shortcodes, post types, taxonomies, settings pages, and more — fill in the form and it writes the snippet for you.', 'code-snippets')}</p>
			</DemoUpsell>}
		</div>
	)
}

export const BlueprintsDemo: React.FC = () =>
	<WithRestAPIContext>
		<BlueprintsDemoPage />
	</WithRestAPIContext>
