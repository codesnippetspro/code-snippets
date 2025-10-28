import React, { useEffect, useRef } from 'react'
import { __ } from '@wordpress/i18n'
import { useSubmitSnippet } from '../../../hooks/useSubmitSnippet'
import { handleUnknownError } from '../../../utils/errors'
import { isMacOS } from '../../../utils/screen'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { Button } from '../../common/Button'
import { ExpandIcon } from '../../common/icons/ExpandIcon'
import { MinimiseIcon } from '../../common/icons/MinimiseIcon'
import { CloudAIButton } from '../../EditorSidebar/actions/CloudAIButton'
import { ExplainSnippetButton } from './ExplainSnippetButton'
import { CodeEditorShortcuts } from './CodeEditorShortcuts'
import type { Dispatch, RefObject, SetStateAction } from 'react'
interface EditorTextareaProps {
	textareaRef: RefObject<HTMLTextAreaElement>
}

const EditorTextarea: React.FC<EditorTextareaProps> = ({ textareaRef }) => {
	const { snippet, setSnippet } = useSnippetForm()

	return (
		<div className="snippet-editor">
			<textarea
				ref={textareaRef}
				id="snippet-code"
				name="snippet_code"
				value={snippet.code}
				rows={200}
				spellCheck={false}
				onChange={event => {
					setSnippet(previous => ({ ...previous, code: event.target.value }))
				}}
			/>
			<CodeEditorShortcuts editorTheme={window.CODE_SNIPPETS_EDIT?.editorTheme ?? 'default'} />
		</div>
	)
}

const ExplainCodeButton: React.FC = () => {
	return (
		<ExplainSnippetButton field="code" title={__('Explain this snippet with AI.', 'code-snippets')}>
			{__('Explain', 'code-snippets')}
		</ExplainSnippetButton>
	)
}

interface GenerateCodeButtonProps {
	setShowCreateModal: Dispatch<SetStateAction<boolean>>
}

const GenerateCodeButton: React.FC<GenerateCodeButtonProps> = ({ setShowCreateModal }) => {
	const { snippet, isWorking, isReadOnly } = useSnippetForm()

	return (
		<CloudAIButton
			primary={0 === snippet.id}
			snippet={snippet}
			disabled={isWorking || isReadOnly}
			title={__('Generate a new snippet with AI.', 'code-snippets')}
			onClick={() => setShowCreateModal(true)}
		>
			{'' === snippet.code.trim()
				? __('Generate', 'code-snippets')
				: __('Generate New', 'code-snippets')}
		</CloudAIButton>
	)
}

export interface CodeEditorProps {
	isExpanded: boolean
	setIsExpanded: Dispatch<SetStateAction<boolean>>
	setIsGenerateModalOpen: Dispatch<SetStateAction<boolean>>
}

export const CodeEditor: React.FC<CodeEditorProps> = ({ isExpanded, setIsExpanded, setIsGenerateModalOpen }) => {
	const { snippet, setSnippet, codeEditorInstance, setCodeEditorInstance } = useSnippetForm()
	const { submitSnippet } = useSubmitSnippet()
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	useEffect(() => {
		setCodeEditorInstance(editorInstance => {
			if (textareaRef.current && !editorInstance) {
				editorInstance = window.wp.codeEditor.initialize(textareaRef.current)

				editorInstance.codemirror.on('changes', instance => {
					setSnippet(previous => ({ ...previous, code: instance.getValue() }))
				})
			}

			return editorInstance
		})
	}, [setCodeEditorInstance, textareaRef, setSnippet])

	useEffect(() => {
		if (codeEditorInstance) {
			const extraKeys = codeEditorInstance.codemirror.getOption('extraKeys') ?? {}
			const controlKey = isMacOS() ? 'Cmd' : 'Ctrl'
			const onSave = () => {
				submitSnippet()
					.then(() => undefined)
					.catch(handleUnknownError)
			}

			codeEditorInstance.codemirror.setOption('extraKeys', {
				...'object' === typeof extraKeys ? extraKeys : undefined,
				[`${controlKey}-S`]: onSave,
				[`${controlKey}-Enter`]: onSave
			})
		}
	}, [submitSnippet, codeEditorInstance, snippet])

	return (
		<div className="snippet-code-container">
			<div className="above-snippet-code">
				<h2><label htmlFor="snippet-code">{__('Snippet Content', 'code-snippets')}</label></h2>

				<Button small className="expand-editor-button" onClick={() => setIsExpanded(current => !current)}>
					{isExpanded ? <MinimiseIcon /> : <ExpandIcon />}
					{isExpanded ? __('Minimize', 'code-snippets') : __('Expand', 'code-snippets')}
				</Button>

				<ExplainCodeButton />
				<GenerateCodeButton setShowCreateModal={setIsGenerateModalOpen} />
			</div>

			<EditorTextarea textareaRef={textareaRef} />
		</div>
	)
}
