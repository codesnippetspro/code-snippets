import React from 'react'
import { __ } from '@wordpress/i18n'
import { ScreenMetaSlot } from '../../../common/ScreenMetaSlot'
import { DemoCallout } from '../common/DemoCallout'
import { DemoPageHeader } from '../common/DemoPageHeader'
import { DemoSpotlight } from '../common/DemoSpotlight'
import { DemoUpsell } from '../common/DemoUpsell'
import { getCallout } from './callouts'
import { DemoPreviewSite } from './DemoPreviewSite'
import { usePreviewDemo } from './usePreviewDemo'
import type { DemoStage } from './types'

const STAGE_SPOTLIGHTS: Partial<Record<DemoStage, { target: string, padding?: number }>> = {
	opened: { target: '.preview-demo-browser', padding: 6 },
	typing: { target: '.preview-demo-code', padding: 6 },
	updated: { target: '.preview-demo-browser', padding: 6 },
	log: { target: '.preview-demo-log', padding: 6 }
}

const STAGE_ANNOUNCEMENTS: Partial<Record<DemoStage, string>> = {
	opened: __('The front page is open with the draft snippet applied.', 'code-snippets'),
	typing: __('Changing the background colour in the snippet.', 'code-snippets'),
	updating: __('Updating the preview.', 'code-snippets'),
	updated: __('The preview now shows the new background colour.', 'code-snippets'),
	log: __('The preview log shows no errors or warnings.', 'code-snippets'),
	finished: __('Demo complete.', 'code-snippets')
}

export const PreviewDemo: React.FC = () => {
	const { stage, typedColor, pageColor, hasStarted, isFinished, play, skip, replay } = usePreviewDemo()

	return (
		<>
			<ScreenMetaSlot />

			<DemoPageHeader
				title={__('Snippet Preview', 'code-snippets')}
				description={__('A guided walkthrough of the Pro snippet preview. Press play and watch a draft change the page it runs on before it is saved.', 'code-snippets')}
				hasStarted={hasStarted}
				isFinished={isFinished}
				onPlay={play}
				onSkip={skip}
				onReplay={replay}
			/>

			<section className="preview-demo" aria-label={__('Snippet preview demo', 'code-snippets')}>
				<div className="screen-reader-text" aria-live="polite">{STAGE_ANNOUNCEMENTS[stage]}</div>

				<DemoSpotlight {...STAGE_SPOTLIGHTS[stage]} />

				<div className="preview-demo__layout">
					<DemoPreviewSite stage={stage} typedColor={typedColor} pageColor={pageColor} />

					<div className="preview-demo__aside">
						{isFinished
							? <div className="preview-demo__closing">
								<DemoUpsell
									title={__('That was a demo: the real preview runs your draft', 'code-snippets')}
									onReplay={replay}
								>
									<p>{__('The whole walkthrough was scripted and ran inside this plugin. The page beside this note was drawn for the demo, and no code was run on your site.', 'code-snippets')}</p>
									<p>{__('In Code Snippets Pro, Preview runs your PHP, CSS, JavaScript or HTML draft on a real page or admin screen of your choosing, privately and before it is saved or activated, with JavaScript errors logged on the page and a clear error page if the draft fails.', 'code-snippets')}</p>
								</DemoUpsell>
							</div>
							: <DemoCallout docked callout={getCallout(stage)} onClose={skip} />}
					</div>
				</div>
			</section>
		</>
	)
}
