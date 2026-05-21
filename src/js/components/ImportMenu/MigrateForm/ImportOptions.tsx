import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../common/ImportCard'
import { useMigrationOptions } from './WithMigrationOptions'

export const ImportOptions: React.FC = () => {
	const { autoAddTags, setAutoAddTags, tagValue, setTagValue } = useMigrationOptions()

	return (
		<ImportCard className="import-options-card">
			<h3>{__('Import options', 'code-snippets')}</h3>
			<label>
				<input
					type="checkbox"
					checked={autoAddTags}
					onChange={event => setAutoAddTags(event.target.checked)}
					aria-labelledby="code-snippets-import-auto-add-tags-label"
				/>
				<div>
					<div>
						<strong id="code-snippets-import-auto-add-tags-label">
							{__('Add tag automatically', 'code-snippets')}
						</strong>
						<br />
						<span className="description">
							{__('For your convenience, we can add a tag on every imported snippet.', 'code-snippets')}
						</span>
					</div>
					{autoAddTags && (
						<div className="import-tag-entry">
							<input
								id="import-auto-tag"
								type="text"
								value={tagValue}
								onChange={event => setTagValue(event.target.value)}
								placeholder={__('Add tag…', 'code-snippets')}
								aria-label={__('Tag to add to imported snippets', 'code-snippets')}
								className="regular-text"
							/>
						</div>)}
				</div>
			</label>
		</ImportCard>
	)
}
