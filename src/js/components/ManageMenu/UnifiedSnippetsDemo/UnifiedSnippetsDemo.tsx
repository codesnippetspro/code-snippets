import React, { useEffect, useMemo, useRef, useState } from 'react'
import classnames from 'classnames'
import { __, _n } from '@wordpress/i18n'
import { Spinner } from '@wordpress/components'
import { WithRestAPIContext } from '../../../hooks/useRestAPI'
import { Button } from '../../common/Button'
import { ScreenMetaSlot } from '../../common/ScreenMetaSlot'
import { DemoCallout } from '../common/demo/DemoCallout'
import { DemoPageHeader } from '../common/demo/DemoPageHeader'
import { DemoSpotlight } from '../common/demo/DemoSpotlight'
import { DemoUpsell } from '../common/demo/DemoUpsell'
import { useDemoTimeline } from '../common/demo/useDemoTimeline'
import { useMarkDemoSeen } from '../common/demo/useDemoSeen'
import { UnifiedSnippetsIcon } from '../../common/icons/UnifiedSnippetsIcon'
import { PreviewModal } from '../common/snippets/SnippetPreviewModal'
import { getCallout } from './callouts'
import { DemoFoundItemsTable } from './DemoFoundItemsTable'
import { getDemoFoundItems } from './demoFoundItems'
import { hasReached, isImportable, previewTypeFor } from './types'
import type { RefObject} from 'react'
import type { DemoTimeline } from '../common/demo/useDemoTimeline'
import type { DemoCodeType, DemoFoundItem, DemoStage } from './types'

const TIMELINE: DemoTimeline<DemoStage> = {
	idle: 'idle',
	first: 'scan-start',
	finished: 'finished',
	followUp: {
		'scan-start': 'scan-themes',
		'scan-themes': 'scan-customizer',
		'scan-customizer': 'scan-plugins',
		'scan-plugins': 'scan-content',
		'scan-content': 'results',
		'results': 'sources',
		'sources': 'select',
		'select': 'importing',
		'importing': 'imported',
		'imported': 'view-only',
		'view-only': 'finished'
	},
	durations: {
		'scan-start': 2400,
		'scan-themes': 1500,
		'scan-customizer': 1500,
		'scan-plugins': 1500,
		'scan-content': 1700,
		'results': 4600,
		'sources': 5000,
		'select': 3000,
		'importing': 2400,
		'imported': 4400,
		'view-only': 5400
	},
	defaultDuration: 3000
}

const STAGE_SPOTLIGHTS: Partial<Record<DemoStage, { target: string, padding?: number }>> = {
	'scan-start': { target: '.unified-snippets-demo-scan', padding: 6 },
	'results': { target: '.unified-snippets-stats', padding: 8 },
	'sources': { target: '.unified-snippets-demo-table', padding: 4 },
	'select': { target: '.unified-snippets-demo-table', padding: 4 },
	'importing': { target: '.unified-snippets-demo-bulk', padding: 6 },
	'imported': { target: '.unified-snippets-demo-table', padding: 4 },
	'view-only': { target: '.unified-snippets-demo-table tr.demo-featured-row', padding: 2 }
}

const STAGE_ANNOUNCEMENTS: Partial<Record<DemoStage, string>> = {
	'scan-start': __('Starting a scan of your site.', 'code-snippets'),
	'results': __('The scan has finished.', 'code-snippets'),
	'sources': __('Showing where each item was found.', 'code-snippets'),
	'select': __('Selecting the items to import.', 'code-snippets'),
	'importing': __('Importing the selected items.', 'code-snippets'),
	'imported': __('Items imported as inactive snippets.', 'code-snippets'),
	'view-only': __('Showing an item that is view only.', 'code-snippets'),
	'finished': __('Demo complete.', 'code-snippets')
}

/** The line under the scan button as the scripted scan works through the site. */
const SCAN_PROGRESS: Partial<Record<DemoStage, string>> = {
	'scan-themes': __('Reading theme files…', 'code-snippets'),
	'scan-customizer': __('Checking the Customizer…', 'code-snippets'),
	'scan-plugins': __('Looking through other plugins’ settings…', 'code-snippets'),
	'scan-content': __('Scanning page content and page builders…', 'code-snippets')
}

const isScanning = (stage: DemoStage): boolean =>
	stage in SCAN_PROGRESS

const WelcomeCard: React.FC<{ stage: DemoStage }> = ({ stage }) => {
	const isBusy = isScanning(stage)

	return (
		<div className="unified-snippets-empty">
			<h2 className="unified-snippets-empty__title">
				<span className="unified-snippets-empty__logo">
					<UnifiedSnippetsIcon aria-hidden="true" />
				</span>
				{__('Unified Snippets', 'code-snippets')}
			</h2>

			<p className="unified-snippets-empty__description">
				{__('Unified Snippets finds the custom code scattered across your site, in theme files, the Customizer, other plugins’ settings, page content and page builders, and brings it into one place. Each find shows where it came from, and importing it creates an inactive snippet for you to review before anything changes.', 'code-snippets')}
			</p>

			<Button
				primary
				large
				aria-hidden="true"
				tabIndex={-1}
				aria-busy={isBusy}
				disabled={isBusy}
				className={classnames('unified-snippets-demo-scan', { 'demo-click': 'scan-start' === stage })}
			>
				{isBusy && <Spinner />}
				{isBusy ? __('Scanning…', 'code-snippets') : __('Scan site', 'code-snippets')}
			</Button>

			<p className="unified-snippets-empty__status">
				{SCAN_PROGRESS[stage] ?? __('Scanning reads your site without changing anything.', 'code-snippets')}
			</p>
		</div>
	)
}

interface SummaryCard {
	key: string
	count: number
	label: string
	detail: string
}

const IMPORTABLE_TYPES: DemoCodeType[] = ['php', 'html', 'css', 'js']

const buildCards = (items: DemoFoundItem[]): SummaryCard[] => {
	const importable = items.filter(isImportable)

	const cards: SummaryCard[] = [
		...IMPORTABLE_TYPES.map(type => {
			const count = importable.filter(item => type === item.type).length

			return {
				key: type,
				count,
				label: type.toUpperCase(),
				detail: _n('snippet ready to import', 'snippets ready to import', count, 'code-snippets')
			}
		}),
		{
			key: 'view-only',
			count: items.length - importable.length,
			label: __('View only', 'code-snippets'),
			detail: __('cannot run as a snippet', 'code-snippets')
		}
	]

	return cards.filter(card => 0 < card.count)
}

const SummaryCards: React.FC<{ items: DemoFoundItem[] }> = ({ items }) =>
	<ul className="unified-snippets-stats" aria-label={__('Scan summary', 'code-snippets')}>
		{buildCards(items).map(card =>
			<li key={card.key}>
				<div className={classnames('unified-snippets-stat', `is-${card.key}`)}>
					<span className="unified-snippets-stat__count">{card.count}</span>{' '}
					<span className="unified-snippets-stat__label">{card.label}</span>{' '}
					<span className="unified-snippets-stat__detail">{card.detail}</span>
				</div>
			</li>)}
	</ul>

interface ResultsProps {
	items: DemoFoundItem[]
	stage: DemoStage
	onPreview: (item: DemoFoundItem) => void
}

const Results: React.FC<ResultsProps> = ({ items, stage, onPreview }) =>
	<div className="unified-snippets-results">
		<p className="unified-snippets-demo-scanned">
			<span className="unified-snippets-last-scanned">{__('Last scanned just now', 'code-snippets')}</span>
		</p>

		<SummaryCards items={items} />

		<DemoFoundItemsTable items={items} stage={stage} onPreview={onPreview} />
	</div>

interface DemoContentProps {
	stage: DemoStage
	items: DemoFoundItem[]
	onClose: VoidFunction
	onReplay: VoidFunction
	upsellRef: RefObject<HTMLDivElement>
	isFinished: boolean
}

const DemoContent: React.FC<DemoContentProps> = ({ stage, items, onClose, onReplay, isFinished, upsellRef }) => {
	const [preview, setPreview] = useState<DemoFoundItem>()

	return (
		<section className="unified-snippets unified-snippets-demo" aria-label={__('Unified Snippets demo', 'code-snippets')}>
			<div className="screen-reader-text" aria-live="polite">{STAGE_ANNOUNCEMENTS[stage]}</div>

			<DemoCallout callout={getCallout(stage)} onClose={onClose} />

			<DemoSpotlight {...STAGE_SPOTLIGHTS[stage]} />

			{hasReached(stage, 'results')
				? <Results items={items} stage={stage} onPreview={setPreview} />
				: <WelcomeCard stage={stage} />}

			{isFinished && (
				<div ref={upsellRef} className="unified-snippets-demo__closing">
					<DemoUpsell
						title={__('That was a demo: the real scan reads your site', 'code-snippets')}
						onReplay={onReplay}
					>
						<p>{__('The whole walkthrough was scripted and ran inside this plugin. These finds are examples: your site was not scanned and nothing was imported.', 'code-snippets')}</p>
						<p>{__('In Code Snippets Pro, Unified Snippets scans your own themes, Customizer, plugins, page content and page builders, then imports what you choose as inactive snippets, so you can manage all of your custom code in one place.', 'code-snippets')}</p>
					</DemoUpsell>
				</div>)}

			{preview && (
				<PreviewModal
					title={preview.name}
					code={preview.code}
					type={previewTypeFor(preview.type)}
					onRequestClose={() => setPreview(undefined)}
				/>)}
		</section>
	)
}

const UnifiedSnippetsDemoPage: React.FC = () => {
	const { stage, hasStarted, isFinished, reducedMotion, play, skip, replay } = useDemoTimeline(TIMELINE)

	const items = useMemo(getDemoFoundItems, [])

	const markSeen = useMarkDemoSeen('unified-snippets')
	const upsellRef = useRef<HTMLDivElement>(null)

	useEffect(() => {
		if (!isFinished) {
			return
		}

		markSeen()

		upsellRef.current?.scrollIntoView({
			behavior: reducedMotion ? 'auto' : 'smooth',
			block: 'end'
		})
	}, [isFinished, markSeen, reducedMotion])

	return (
		<>
			<ScreenMetaSlot />

			<DemoPageHeader
				title={__('Unified Snippets', 'code-snippets')}
				description={__('A guided walkthrough of Pro Unified Snippets. Press play and watch it find custom code stored around your site and bring it into Code Snippets.', 'code-snippets')}
				hasStarted={hasStarted}
				isFinished={isFinished}
				onPlay={play}
				onSkip={skip}
				onReplay={replay}
			/>

			<DemoContent
				stage={stage}
				items={items}
				onClose={skip}
				onReplay={replay}
				upsellRef={upsellRef}
				isFinished={isFinished}
			/>
		</>
	)
}

export const UnifiedSnippetsDemo: React.FC = () =>
	<WithRestAPIContext>
		<UnifiedSnippetsDemoPage />
	</WithRestAPIContext>
