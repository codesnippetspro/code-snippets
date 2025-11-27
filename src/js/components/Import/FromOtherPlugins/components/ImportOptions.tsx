import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../shared'

interface ImportOptionsProps {
	autoAddTags: boolean
	tagValue: string
	onAutoAddTagsChange: (enabled: boolean) => void
	onTagValueChange: (value: string) => void
}

export const ImportOptions: React.FC<ImportOptionsProps> = ({
	autoAddTags,
	tagValue,
	onAutoAddTagsChange,
	onTagValueChange
}) => {
	return (
		<ImportCard>
			<h2 style={{ margin: '0 0 1em 0' }}>{__('Import options', 'code-snippets')}</h2>
			<label style={{ display: 'flex', alignItems: 'flex-start', gap: '8px', cursor: 'pointer' }}>
				<input
					type="checkbox"
					checked={autoAddTags}
					onChange={(e) => onAutoAddTagsChange(e.target.checked)}
					style={{ marginTop: '2px' }}
				/>
				<div style={{ flex: 1 }}>
					<div>
						<strong>{__('Automatically add Tag', 'code-snippets')}</strong>
						<br />
						<span style={{ color: '#666', fontSize: '0.9em' }}>
							{__('For your convenience, we can add a tag on every imported snippet.', 'code-snippets')}
						</span>
					</div>
					{autoAddTags && (
						<div style={{ marginTop: '12px' }}>
							<input
								type="text"
								value={tagValue}
								onChange={(e) => onTagValueChange(e.target.value)}
								placeholder={__('Add tag...', 'code-snippets')}
								className="regular-text"
								style={{ width: '100%', maxWidth: '300px' }}
							/>
						</div>
					)}
				</div>
			</label>
		</ImportCard>
	)
}
