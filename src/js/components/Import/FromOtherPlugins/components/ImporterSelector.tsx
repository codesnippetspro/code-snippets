import React from 'react'
import { __ } from '@wordpress/i18n'
import type { Importer } from '../../../../hooks/useImportersAPI'
import { ImportCard } from '../../shared'

interface ImporterSelectorProps {
	importers: Importer[]
	selectedImporter: string
	onImporterChange: (importerName: string) => void
	isLoading: boolean
}

export const ImporterSelector: React.FC<ImporterSelectorProps> = ({
	importers,
	selectedImporter,
	onImporterChange,
	isLoading
}) => {
	return (
		<ImportCard variant="controls">
			<label htmlFor="importer-select">
				<h2 style={{ margin: '0 0 1em 0' }}>{__('Select Plugin', 'code-snippets')}</h2>
			</label>
			<select 
				id="importer-select" 
				value={selectedImporter}
				onChange={(event) => onImporterChange(event.target.value)}
				className="regular-text"
				style={{ display: 'block', marginTop: '5px', width: '100%', maxWidth: '300px' }}
				disabled={isLoading}
			>
				<option value="">{__('-- Select an importer --', 'code-snippets')}</option>
				{importers.map(importer => (
					<option 
						key={importer.name} 
						value={importer.name}
						disabled={!importer.is_active}
					>
						{importer.title} {!importer.is_active ? __('(Inactive)', 'code-snippets') : ''}
					</option>
				))}
			</select>
			{isLoading && (
				<p style={{ margin: '10px 0 0 0', color: '#666', fontSize: '14px' }}>
					{__('Loading snippets...', 'code-snippets')}
				</p>
			)}
		</ImportCard>
	)
}
