import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../common/components/ImportCard'

export type DuplicateAction = 'ignore' | 'replace' | 'skip'

export interface DuplicateActionSelectorProps {
	value: DuplicateAction
	onChange: (action: DuplicateAction) => void
}

export const DuplicateActionSelector: React.FC<DuplicateActionSelectorProps> = ({
	value,
	onChange
}) =>
	<ImportCard>
		<h2 style={{ margin: '0 0 1em 0' }}>{__('Duplicate Snippets', 'code-snippets')}</h2>
		<p className="description" style={{ marginBottom: '1em' }}>
			{__('What should happen if an existing snippet is found with an identical name to an imported snippet?', 'code-snippets')}
		</p>

		<fieldset>
			<div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
				<label style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', cursor: 'pointer' }}>
					<input
						type="radio"
						name="duplicate_action"
						value="ignore"
						checked={'ignore' === value}
						onChange={e => onChange(e.target.value as DuplicateAction)}
						style={{ marginTop: '2px' }}
					/>
					<span>
						{__('Ignore any duplicate snippets: import all snippets from the file regardless and leave all existing snippets unchanged.', 'code-snippets')}
					</span>
				</label>

				<label style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', cursor: 'pointer' }}>
					<input
						type="radio"
						name="duplicate_action"
						value="replace"
						checked={'replace' === value}
						onChange={e => onChange(e.target.value as DuplicateAction)}
						style={{ marginTop: '2px' }}
					/>
					<span>
						{__('Replace any existing snippets with a newly imported snippet of the same name.', 'code-snippets')}
					</span>
				</label>

				<label style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', cursor: 'pointer' }}>
					<input
						type="radio"
						name="duplicate_action"
						value="skip"
						checked={'skip' === value}
						onChange={e => onChange(e.target.value as DuplicateAction)}
						style={{ marginTop: '2px' }}
					/>
					<span>
						{__('Do not import any duplicate snippets; leave all existing snippets unchanged.', 'code-snippets')}
					</span>
				</label>
			</div>
		</fieldset>
	</ImportCard>
