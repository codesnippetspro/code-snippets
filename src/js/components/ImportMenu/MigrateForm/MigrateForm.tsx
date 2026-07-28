import React from 'react'
import { createInterpolateElement } from '@wordpress/element'
import { __, sprintf } from '@wordpress/i18n'
import { Notice } from '../../common/Notice'
import { ImportCard } from '../common/ImportCard'
import { ImporterSelector } from './ImporterSelector'
import { ImportOptions } from './ImportOptions'
import { SimpleSnippetTable } from './SimpleSnippetTable'
import { MigrationStep, WithMigrationData, useMigrationData } from './WithMigrationData'
import { WithMigrationOptions, useMigrationOptions } from './WithMigrationOptions'
import type { ReactNode } from 'react'

interface StatusDisplayProps {
	type: 'error' | 'success'
	title: ReactNode
	children: ReactNode
}

const StatusDisplay: React.FC<StatusDisplayProps> = ({ type, title, children }) =>
	<ImportCard
		variant="controls"
		className="import-section-status"
		role={'error' === type ? 'alert' : 'status'}
		aria-live={'error' === type ? 'assertive' : 'polite'}
	>
		<div className={type} aria-hidden="true"><span>{'error' === type ? '✕' : '✓'}</span></div>
		<div>
			<h4>{title}</h4>
			<p>{children}</p>
		</div>
	</ImportCard>

const StatusMessages: React.FC = () => {
	const { selectedImporter } = useMigrationOptions()
	const { error, isWorking, snippetSelection, importedIds } = useMigrationData()

	return (
		<>
			{error?.step === MigrationStep.FetchSnippets && (
				<StatusDisplay type="error" title={__('Error loading snippets', 'code-snippets')}>
					{error.message}
				</StatusDisplay>)}

			{error?.step === MigrationStep.MigrateSnippets && (
				<StatusDisplay type="error" title={__('Error importing snippets', 'code-snippets')}>
					{error.message}
				</StatusDisplay>)}

			{0 < importedIds.length && (
			// translators: %d: number of imported snippets.
				<StatusDisplay type="success" title={sprintf(__('%d snippets imported!', 'code-snippets'), importedIds.length)}>
					{createInterpolateElement(
						__('Selected snippets have been successfully imported to your <a>Code Snippets library</a>.', 'code-snippets'),
						{
							// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
							a: <a href={window.CODE_SNIPPETS?.urls.manage} />
						}
					)}
				</StatusDisplay>)}

			{selectedImporter &&
					isWorking !== MigrationStep.FetchSnippets && error?.step !== MigrationStep.FetchSnippets &&
					0 === snippetSelection.availableItems.length && 0 === importedIds.length && (
				<ImportCard className="no-snippets-card" role="status" aria-live="polite">
					<div className="card-inner">
						<div className="card-icon" aria-hidden="true">📭</div>
						<h4>{__('No snippets found', 'code-snippets')}</h4>
						<p>{__('No snippets were found for the selected plugin. Make sure the plugin is installed and has snippets configured.', 'code-snippets')}</p>
					</div>
				</ImportCard>)}
		</>
	)
}

const MigrateFormInner: React.FC = () => {
	const { selectedImporter } = useMigrationOptions()
	const { isWorking, error, importers, snippetSelection, importSnippets, changeSelectedImporter } = useMigrationData()

	if (isWorking === MigrationStep.LoadImporters) {
		return <p role="status" aria-live="polite">{__('Loading importers…', 'code-snippets')}</p>
	}

	if (error?.step === MigrationStep.LoadImporters) {
		return (
			<Notice type="error">
				{/* translators: %s: error message. */}
				<p>{sprintf(__('Error loading importers: %s', 'code-snippets'), error.message)}</p>
			</Notice>
		)
	}

	return (
		<>
			<p>{__('If you are using another snippets plugin, you can import those existing snippets to your Code Snippets library.', 'code-snippets')}</p>

			<ImporterSelector
				value={selectedImporter}
				options={importers}
				isLoading={isWorking === MigrationStep.FetchSnippets}
				onChange={value => changeSelectedImporter(value)}
			/>

			<StatusMessages />

			{0 < snippetSelection.availableItems.length && (
				<>
					<ImportOptions />

					<SimpleSnippetTable
						onImport={importSnippets}
						selection={snippetSelection}
						isImporting={isWorking === MigrationStep.MigrateSnippets}
					/>
				</>)}
		</>
	)
}

export const MigrateForm: React.FC = () =>
	<WithMigrationOptions>
		<WithMigrationData>
			<MigrateFormInner />
		</WithMigrationData>
	</WithMigrationOptions>
