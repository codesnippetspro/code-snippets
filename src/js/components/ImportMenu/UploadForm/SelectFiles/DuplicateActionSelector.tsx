import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../common/ImportCard'

export const ACTIONS = ['ignore', 'replace', 'skip'] as const

export type DuplicateAction = typeof ACTIONS[number]

const ACTION_LABELS: Record<DuplicateAction, string> = {
	ignore: __('Ignore any duplicate snippets: import all snippets from the file regardless and leave all existing snippets unchanged.', 'code-snippets'),
	replace: __('Replace any existing snippets with a newly imported snippet of the same name.', 'code-snippets'),
	skip: __('Do not import any duplicate snippets; leave all existing snippets unchanged.', 'code-snippets')
}

export interface DuplicateActionSelectorProps {
	value: DuplicateAction
	onChange: (action: DuplicateAction) => void
}

export const DuplicateActionSelector: React.FC<DuplicateActionSelectorProps> = ({ value, onChange }) =>
	<ImportCard className="duplicate-action-selector-card">
		<h2>{__('Duplicate snippets', 'code-snippets')}</h2>

		<p className="description">
			{__('What should happen if an existing snippet is found with an identical name to an imported snippet?', 'code-snippets')}
		</p>

		<fieldset>
			<div>
				{ACTIONS.map(action =>
					<label key={action}>
						<input
							type="radio"
							name="duplicate_action"
							value={action}
							checked={action === value}
							onChange={() => onChange(action)}
							aria-labelledby={`duplicate-action-${action}-label`}
						/>
						<span id={`duplicate-action-${action}-label`}>{ACTION_LABELS[action]}</span>
					</label>)}
			</div>
		</fieldset>
	</ImportCard>
