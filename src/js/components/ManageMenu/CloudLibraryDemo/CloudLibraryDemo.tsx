import React, { useEffect, useMemo, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { getSnippetType } from '../../../utils/snippets/snippets'
import { ListTable } from '../../common/ListTable'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { DemoCallout } from '../../common/demo/DemoCallout'
import { DemoPageHeader } from '../../common/demo/DemoPageHeader'
import { DemoSpotlight } from '../../common/demo/DemoSpotlight'
import { DemoUpsell } from '../../common/demo/DemoUpsell'
import { PreviewModal } from '../../common/snippets/SnippetPreviewModal'
import { cloudLibraryDemoColumns } from './cloudLibraryDemoColumns'
import { getCallout } from './callouts'
import { FEATURED_SNIPPET_ID, getDemoCloudSnippets } from './demoCloudSnippets'
import { useCloudLibraryDemo } from './useCloudLibraryDemo'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'
import type { DemoStage } from './types'

const FEATURED_ROW = '.cloud-library-snippets tbody tr.demo-featured-row'

/** The part of the page each step is talking about. */
const STAGE_SPOTLIGHTS: Partial<Record<DemoStage, { target: string, padding?: number }>> = {
	library: { target: '.cloud-library-snippets', padding: 6 },
	preview: { target: `${FEATURED_ROW} .demo-preview-button`, padding: 6 },
	download: { target: `${FEATURED_ROW} .demo-action-button`, padding: 6 },
	synced: { target: FEATURED_ROW, padding: 4 }
}

const STAGE_ANNOUNCEMENTS: Partial<Record<DemoStage, string>> = {
	library: __('Showing the snippets saved in your cloud.', 'code-snippets'),
	preview: __('Previewing the snippet code.', 'code-snippets'),
	download: __('Downloading the snippet to your site.', 'code-snippets'),
	synced: __('The snippet is now synced with your cloud.', 'code-snippets'),
	finished: __('Demo complete.', 'code-snippets')
}

// eslint-disable-next-line max-lines-per-function -- the page reads as one sequence.
export const CloudLibraryDemo: React.FC = () => {
	const { stage, hasStarted, isFinished, reducedMotion, play, skip, replay } = useCloudLibraryDemo()

	const snippets = useMemo(getDemoCloudSnippets, [])
	const [preview, setPreview] = useState<CloudSnippetSchema>()

	const featured = snippets.find(snippet => FEATURED_SNIPPET_ID === snippet.id)

	// The walkthrough opens the preview itself, and closes it again before it
	// moves on to the download.
	useEffect(() => {
		setPreview('preview' === stage ? featured : undefined)
	}, [featured, stage])

	const upsellRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (isFinished) {
			upsellRef.current?.scrollIntoView({
				behavior: reducedMotion ? 'auto' : 'smooth',
				block: 'end'
			})
		}
	}, [isFinished, reducedMotion])

	return (
		<>
			<ScreenMetaSlot />
			<div className="cloud-library-demo">
				<DemoPageHeader
					title={__('Cloud Library', 'code-snippets')}
					description={__('A guided walkthrough of the Pro Cloud Library. Press play and watch a snippet move from your cloud onto this site.', 'code-snippets')}
					hasStarted={hasStarted}
					isFinished={isFinished}
					onPlay={play}
					onSkip={skip}
					onReplay={replay}
				/>

				<div className="screen-reader-text" aria-live="polite">{STAGE_ANNOUNCEMENTS[stage]}</div>

				<DemoCallout callout={getCallout(stage)} />

				<DemoSpotlight {...STAGE_SPOTLIGHTS[stage]} />

				<div className="cloud-library-snippets snippets-list-view">
					<ListTable
						fixed
						striped
						items={snippets}
						columns={cloudLibraryDemoColumns({ stage, onPreview: setPreview })}
						getKey={snippet => snippet.id}
						rowClassName={snippet => FEATURED_SNIPPET_ID === snippet.id ? 'demo-featured-row' : ''}
					/>
				</div>

				{isFinished && <div ref={upsellRef} className="cloud-library-demo__closing">
					<DemoUpsell
						title={__('That was a demo — your cloud travels with you', 'code-snippets')}
						onReplay={replay}
					>
						<p>{__('The whole walkthrough was scripted and ran inside this plugin. These snippets are examples, and nothing was downloaded or saved.', 'code-snippets')}</p>
						<p>{__('With Code Snippets Pro your Cloud Library is available on every site you connect, so a snippet written once can be reused everywhere and kept in sync.', 'code-snippets')}</p>
					</DemoUpsell>
				</div>}

				{preview && <PreviewModal
					title={preview.name}
					code={preview.code}
					type={getSnippetType(preview)}
					onRequestClose={() => setPreview(undefined)}
				/>}
			</div>
		</>
	)
}
