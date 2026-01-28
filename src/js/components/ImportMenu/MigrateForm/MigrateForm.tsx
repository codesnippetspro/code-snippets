import { createInterpolateElement } from '@wordpress/element'
import { __, sprintf } from '@wordpress/i18n'
import React from 'react'
import { ImportCard } from '../common/ImportCard'
import { ImporterSelector } from './ImporterSelector'
import { ImportOptions } from './ImportOptions'
import { SimpleSnippetTable } from './SimpleSnippetTable'
import { MigrationStep, WithMigrationContext, useMigrationContext } from './WithMigrationContext'
import { useMigrationOptions } from './WithMigrationOptions'
import type { ReactNode } from 'react'

interface StatusDisplayProps {
	type: 'error' | 'success'
	title: string
	message: ReactNode
}

const StatusDisplay: React.FC<StatusDisplayProps> = ({ type, title, message }) =>
	<ImportCard variant="controls" className="import-section-status">
		{'error' === type
			? <div className="error"><span>✕</span></div>
			: <div className="success"><span>✓</span></div>}

		<div>
			<h3>{title}</h3>
			<p>{message}</p>
		</div>
	</ImportCard>

const StatusMessages: React.FC = () => {
	const { selectedImporter } = useMigrationOptions()
	const { error, isWorking, snippetSelection, importedIds } = useMigrationContext()

	return (
		<>
			{error?.step === MigrationStep.FetchSnippets && (
				<StatusDisplay
					type="error"
					title={__('Error loading snippets', 'code-snippets')}
					message={error.message}
				/>)}

			{error?.step === MigrationStep.MigrateSnippets && (
				<StatusDisplay
					type="error"
					title={__('Error importing snippets', 'code-snippets')}
					message={error.message}
				/>)}

			{selectedImporter &&
				isWorking !== MigrationStep.FetchSnippets &&
				error?.step !== MigrationStep.FetchSnippets &&
				0 === snippetSelection.availableItems.length &&
				0 === importedIds.length && (
				<ImportCard className="no-snippets-card">
					<div className="card-inner">
						<div className="card-icon">📭</div>
						<h3>{__('No snippets found', 'code-snippets')}</h3>
						<p>{__('No snippets were found for the selected plugin. Make sure the plugin is installed and has snippets configured.', 'code-snippets')}</p>
					</div>
				</ImportCard>)}
		</>
	)
}

const MigrateTable = () => {
	const { snippetSelection, importedIds, isWorking, importSnippets } = useMigrationContext()

	return (
		<>
			{0 < importedIds.length && (
				<StatusDisplay
					type="success"
					title={sprintf(
						// translators: %d: number of imported snippets.
						__('%d Snippets imported!', 'code-snippets'),
						importedIds.length
					)}
					message={
						createInterpolateElement(
							__('We successfully imported all snippets to your library. Go to <a>Code Snippets Library</a>.', 'code-snippets'),
							{ a: <a href={window.CODE_SNIPPETS?.urls.manage} /> }
						)}
				/>)}

			{0 < snippetSelection.availableItems.length && (
				<>
					<ImportOptions />

					<SimpleSnippetTable
						snippets={snippetSelection.availableItems}
						selectedSnippets={snippetSelection.selectedItems}
						onSnippetToggle={snippetSelection.toggleItem}
						onSelectAll={snippetSelection.selectAll}
						onImport={importSnippets}
						isImporting={isWorking === MigrationStep.MigrateSnippets}
					/>
				</>
			)}</>
	)
}

const MigrateFormInner: React.FC = () => {
	const { selectedImporter } = useMigrationOptions()
	const { isWorking, error, importers, changeSelectedImporter } = useMigrationContext()

	if (isWorking === MigrationStep.LoadImporters) {
		return <p>{__('Loading importers…', 'code-snippets')}</p>
	}

	if (error?.step === MigrationStep.LoadImporters) {
		return (
			<div className="notice notice-error">
				<p>{sprintf(
					// translators: %s: error message.
					__('Error loading importers: %s', 'code-snippets'),
					error.message
				)}</p>
			</div>
		)
	}

	return (
		<div className="migrate-form-container">
			<p>{__('If you are using another snippets plugin, you can import those existing snippets to your Code Snippets library.', 'code-snippets')}</p>

			<ImporterSelector
				value={selectedImporter}
				options={importers}
				isLoading={isWorking === MigrationStep.FetchSnippets}
				onChange={value => changeSelectedImporter(value)}
			/>

			<StatusMessages />
			<MigrateTable />
		</div>
	)
}

export const MigrateForm: React.FC = () =>
	<WithMigrationContext>
		<div className="wrap">
			<MigrateFormInner />
		</div>
	</WithMigrationContext>
