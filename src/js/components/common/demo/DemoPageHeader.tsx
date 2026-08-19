import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../Button'

export interface DemoPageHeaderProps {
	title: string
	description: string
	/** Whether the walkthrough has left its idle state. */
	hasStarted: boolean
	isFinished: boolean
	onPlay: VoidFunction
	onSkip: VoidFunction
	onReplay: VoidFunction
}

/**
 * Page header shared by the feature walkthroughs: the screen title, a chip
 * marking the page as a demo, and the playback controls for whichever stage
 * the walkthrough is currently in.
 */
export const DemoPageHeader: React.FC<DemoPageHeaderProps> = ({
	title,
	description,
	hasStarted,
	isFinished,
	onPlay,
	onSkip,
	onReplay
}) =>
	<>
		<div className="snippets-page-header demo-page-header">
			<h1>
				{title}
				<span className="demo-chip">{__('Demo', 'code-snippets')}</span>
			</h1>

			<div className="demo-controls">
				{!hasStarted && <Button primary large type="button" className="demo-play" onClick={onPlay}>
					{__('Play demo', 'code-snippets')}
				</Button>}

				{hasStarted && !isFinished && <Button secondary type="button" onClick={onSkip}>
					{__('Skip animation', 'code-snippets')}
				</Button>}

				{isFinished && <Button primary type="button" onClick={onReplay}>
					{__('Run demo again', 'code-snippets')}
				</Button>}
			</div>
		</div>

		<p className="snippets-page-description">{description}</p>
	</>
