import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../common/ImportCard'

export interface ImportOptionsProps {
	tagValue: string
	autoAddTags: boolean
	onAutoAddTagsChange: (enabled: boolean) => void
	onTagValueChange: (value: string) => void
}

export const ImportOptions: React.FC<ImportOptionsProps> = ({
	autoAddTags,
	tagValue,
	onAutoAddTagsChange,
	onTagValueChange
}) =>
	<ImportCard className="import-options-card">
		<h2>{__('Import options', 'code-snippets')}</h2>
		<label>
			<input
				type="checkbox"
				checked={autoAddTags}
				onChange={event => onAutoAddTagsChange(event.target.checked)}
			/>
			<div>
				<div>
					<strong>{__('Add tag automatically', 'code-snippets')}</strong>
					<br />
					<span className="description">
						{__('For your convenience, we can add a tag on every imported snippet.', 'code-snippets')}
					</span>
				</div>
				{autoAddTags &&
					<div className="import-tag-entry">
						<input
							type="text"
							value={tagValue}
							onChange={event => onTagValueChange(event.target.value)}
							placeholder={__('Add tag…', 'code-snippets')}
							className="regular-text"
						/>
					</div>}
			</div>
		</label>
	</ImportCard>
