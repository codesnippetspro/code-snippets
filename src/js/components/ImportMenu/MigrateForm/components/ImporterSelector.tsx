import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { ImportCard } from '../../common/ImportCard'
import type { Importer } from '../hooks/useSnippetImport'

export interface ImporterSelectorProps {
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
}) =>
	<ImportCard variant="controls" className="importer-selector-card">
		<label htmlFor="importer-select">
			<h2>{__('Select plugin', 'code-snippets')}</h2>
		</label>

		<select
			id="importer-select"
			value={selectedImporter}
			onChange={event => onImporterChange(event.target.value)}
			className="regular-text"
			disabled={isLoading}
		>
			<option value="">{__('-- Select an importer --', 'code-snippets')}</option>
			{importers.map(importer =>
				<option
					key={importer.name}
					value={importer.name}
					disabled={!importer.is_active}
				>
					{importer.is_active
						? importer.title
						// translators: %s: importer title.
						: sprintf(__('%s (Inactive)', 'code-snippets'), importer.title)}
				</option>)}
		</select>

		{isLoading && <p>{__('Loading snippets…', 'code-snippets')}</p>}
	</ImportCard>
