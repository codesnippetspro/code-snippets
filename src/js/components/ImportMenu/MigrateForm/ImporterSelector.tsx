import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { ImportCard } from '../common/ImportCard'
import type { Importer } from './WithMigrationData'

export interface ImporterSelectorProps {
	value: string
	onChange: (newValue: string) => void
	isLoading: boolean
	options: Importer[]
}

export const ImporterSelector: React.FC<ImporterSelectorProps> = ({ value, onChange, options, isLoading }) =>
	<ImportCard variant="controls" className="importer-selector-card">
		<h2 id="importer-select-heading">{__('Select plugin', 'code-snippets')}</h2>
		<label htmlFor="importer-select" className="screen-reader-text">
			{__('Select plugin to migrate from', 'code-snippets')}
		</label>

		<select
			aria-labelledby="importer-select-heading"
			id="importer-select"
			value={value}
			onChange={event => onChange(event.target.value)}
			className="regular-text"
			disabled={isLoading}
		>
			<option value="">{__('-- Select an importer --', 'code-snippets')}</option>
			{options.map(importer =>
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

		{isLoading && <p role="status" aria-live="polite">{__('Loading snippets…', 'code-snippets')}</p>}
	</ImportCard>
