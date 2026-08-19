import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Badge } from '../../common/Badge'
import { Button } from '../../common/Button'
import { PreviewModal } from '../../common/snippets/SnippetPreviewModal'
import { DemoPromptBox } from './DemoPromptBox'
import { hasReached, languageToSnippetType } from './types'
import type { DemoSnippet, DemoStage } from './types'

interface DemoResultCardProps {
	stage: DemoStage
	snippets: DemoSnippet[]
	typedRefinement: string
}

interface RowProps {
	snippet: DemoSnippet
	stage: DemoStage
	onPreview: VoidFunction
}

const ResultRow: React.FC<RowProps> = ({ snippet, stage, onPreview }) =>
	<li className="ai-agent-result__row">
		{hasReached(stage, 'refine-open') &&
			<input type="checkbox" checked readOnly aria-label={snippet.name} />}

		<Badge name={languageToSnippetType(snippet.language)} small />
		<span className="ai-agent-result__name">{snippet.name}</span>

		<Button link type="button" onClick={onPreview}>
			{__('Preview code', 'code-snippets')}
		</Button>

		{'applying' !== stage &&
			<Button link type="button" className="ai-agent-result__link" aria-hidden="true" tabIndex={-1}>
				{__('Edit', 'code-snippets')}
			</Button>}
	</li>

const RefineSection: React.FC<{ stage: DemoStage, typedRefinement: string }> = ({ stage, typedRefinement }) =>
	hasReached(stage, 'refine-open')
		? <>
			<div className="ai-agent-result__edit-head">
				<span>{__('Select the snippets to change, then describe the change.', 'code-snippets')}</span>
			</div>

			<DemoPromptBox
				value={typedRefinement}
				typing={'typing-refinement' === stage}
				pressed={'applying' === stage}
				submitLabel={__('Apply changes', 'code-snippets')}
				placeholder={__('Describe the changes to the selected snippets…', 'code-snippets')}
			/>
		</>
		: <Button link type="button" className="ai-agent-result__make-changes" aria-hidden="true" tabIndex={-1}>
			{__('Make changes', 'code-snippets')}
		</Button>

export const DemoResultCard: React.FC<DemoResultCardProps> = ({ stage, snippets, typedRefinement }) => {
	const [preview, setPreview] = useState<DemoSnippet>()

	return (
		<div className="ai-agent-result">
			<div className="ai-agent-plan__header">
				<h3 className="ai-agent-plan__title">{__('Dismissible welcome banner', 'code-snippets')}</h3>
				<span className="ai-agent-plan__badge is-done">
					{'applying' === stage ? __('Updating…', 'code-snippets') : __('Created', 'code-snippets')}
				</span>
			</div>

			<p className="ai-agent-result__lead">
				{__('In Pro these snippets would now be on your site, inactive. Preview the code, or make changes below.', 'code-snippets')}
			</p>

			<ul className="ai-agent-result__rows">
				{snippets.map(snippet =>
					<ResultRow
						key={snippet.key}
						snippet={snippet}
						stage={stage}
						onPreview={() => setPreview(snippet)}
					/>)}
			</ul>

			<div className="ai-agent-result__edit">
				<RefineSection stage={stage} typedRefinement={typedRefinement} />
			</div>

			{preview && <PreviewModal
				title={preview.name}
				code={preview.code}
				type={languageToSnippetType(preview.language)}
				onRequestClose={() => setPreview(undefined)}
			/>}
		</div>
	)
}
