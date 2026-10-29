import React, { useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { createInterpolateElement } from '@wordpress/element'
import { ImportResultDisplay } from './SelectSnippets/ImportResultDisplay'
import { SelectSnippets } from './SelectSnippets/SelectSnippets'
import { SelectFiles } from './SelectFiles/SelectFiles'
import { DuplicateActionSelector } from './SelectFiles/DuplicateActionSelector'
import type { UploadedFile } from './SelectFiles/UploadButton'
import type { ImportableSnippetSchema } from '../../../types/schema/ImportableSnippetSchema'
import type { ImportResult } from './SelectSnippets/ImportResultDisplay'
import type { DuplicateAction } from './SelectFiles/DuplicateActionSelector'

type Step = 'upload' | 'select'

export const UploadForm: React.FC = () => {
	const [duplicateAction, setDuplicateAction] = useState<DuplicateAction>('ignore')
	const [currentStep, setCurrentStep] = useState<Step>('upload')
	const [importResult, setImportResult] = useState<ImportResult>()

	const fileInputRef = useRef<HTMLInputElement>(null)
	const [selectedFiles, setSelectedFiles] = useState<UploadedFile[]>()

	const [availableSnippets, setAvailableSnippets] = useState<ImportableSnippetSchema[]>([])

	return (
		<>
			<p>{__('Upload one or more Code Snippets export files and the snippets will be imported.', 'code-snippets')}</p>

			<p>
				{createInterpolateElement(
					__('Afterward, you will need to visit the <a>All Snippets</a> page to activate the imported snippets.', 'code-snippets'),
					{
						// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
						a: <a href={window.CODE_SNIPPETS?.urls.manage} />
					}
				)}
			</p>

			{'upload' === currentStep && !importResult?.success && (
				<>
					<DuplicateActionSelector value={duplicateAction} onChange={setDuplicateAction} />

					<SelectFiles
						{...{ fileInputRef, selectedFiles, setSelectedFiles, setImportResult }}
						onSuccess={uploadedSnippets => {
							setAvailableSnippets(uploadedSnippets)
							setCurrentStep('select')
						}}
					/>
				</>)}

			{'select' === currentStep && 0 < availableSnippets.length && !importResult?.success && (
				<SelectSnippets
					duplicateAction={duplicateAction}
					setImportResult={setImportResult}
					availableSnippets={availableSnippets}
					onCancel={() => {
						setSelectedFiles(undefined)

						if (fileInputRef.current) {
							fileInputRef.current.value = ''
						}

						setAvailableSnippets([])
						setImportResult(undefined)
						setCurrentStep('upload')
					}}
				/>)}

			{importResult && <ImportResultDisplay {...importResult} />}
		</>
	)
}
