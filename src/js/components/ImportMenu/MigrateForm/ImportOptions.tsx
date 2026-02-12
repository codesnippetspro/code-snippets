import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../common/ImportCard'
import { useMigrationOptions } from './WithMigrationOptions'

export const ImportOptions: React.FC = () => {
	const { autoAddTags, setAutoAddTags, tagValue, setTagValue } = useMigrationOptions()

	return (
		<ImportCard className="import-options-card">
			<h2>{__('Import options', 'code-snippets')}</h2>
			<label>
				<input
					type="checkbox"
					checked={autoAddTags}
					onChange={event => setAutoAddTags(event.target.checked)}
				/>
				<div>
					<div>
						<strong>{__('Add tag automatically', 'code-snippets')}</strong>
						<br />
						<span className="description">
							{__('For your convenience, we can add a tag on every imported snippet.', 'code-snippets')}
						</span>
					</div>
					{autoAddTags && (
						<div className="import-tag-entry">
							<input
								type="text"
								value={tagValue}
								onChange={event => setTagValue(event.target.value)}
								placeholder={__('Add tag…', 'code-snippets')}
								className="regular-text"
							/>
						</div>)}
				</div>
			</label>
		</ImportCard>
	)
}
