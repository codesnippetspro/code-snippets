import React, { useState } from 'react'
import { LineWidget } from 'codemirror'
import { Spinner } from '@wordpress/components'
import { __, isRTL } from '@wordpress/i18n'
import { Button } from '../../common/Button'
import { CloudAIButton } from '../buttons/CloudAIButton'
import { ExplainSnippetButton } from '../buttons/ExplainSnippetButton'
import { GenerateCodeModal } from '../components/GenerateCodeModal'
import { isNetworkAdmin } from '../../utils/general'
import { useSnippetForm } from '../SnippetForm/context'

const InlineActivateButton: React.FC = () => {
	const { snippet, isWorking, submitAndActivateSnippet, submitAndDeactivateSnippet } = useSnippetForm()

	if (snippet.shared_network && isNetworkAdmin()) {
		return null
	}

	if ('single-use' === snippet.scope) {
		return (
			<Button
				small
				id="save_snippet_execute_extra"
				title={__('Save Snippet and Execute Once', 'code-snippets')}
				onClick={() => submitAndActivateSnippet()}
				disabled={isWorking}
			>
				{__('Execute Once', 'code-snippets')}
			</Button>
		)
	}

	return snippet.active ?
		<Button
			small
			id="save_snippet_deactivate_extra"
			title={__('Save Snippet and Deactivate', 'code-snippets')}
			onClick={() => submitAndDeactivateSnippet()}
			disabled={isWorking}
		>
			{__('Deactivate', 'code-snippets')}
		</Button> :
		<Button
			small
			id="save_snippet_activate_extra"
			title={__('Save Snippet and Activate', 'code-snippets')}
			onClick={() => submitAndActivateSnippet()}
			disabled={isWorking}
		>
			{__('Activate', 'code-snippets')}
		</Button>
}

const ExplainCodeButton: React.FC = () => {
	const { snippet, isWorking, isReadOnly, codeEditorInstance } = useSnippetForm()
	const [, setWidgets] = useState<LineWidget[]>([])

	return (
		<ExplainSnippetButton
			field="code"
			snippet={snippet}
			disabled={isWorking || isReadOnly}
			title={__('Explain this snippet with AI.', 'code-snippets')}
			onRequest={() => {
				setWidgets(widgets => {
					widgets.forEach(widget => widget.clear())
					return []
				})
			}}
			onResponse={generated => {
				const doc = codeEditorInstance?.codemirror.getDoc()
				console.info('lines', generated.lines)

				setWidgets(() => doc && generated.lines ?
					Object.entries(generated.lines).map(([line, message]) => {
						const lineNumber = parseInt(line, 10) - 1

						const widget = document.createElement('div')
						widget.className = 'code-line-explanation'

						const icon = document.createElement('img')
						icon.setAttribute('src', `${window.CODE_SNIPPETS?.urls.plugin}/assets/generate.svg`)

						widget.appendChild(icon)
						widget.appendChild(document.createTextNode(message))

						return doc.addLineWidget(lineNumber, widget, { above: true })
					}) : [])
			}}
		>
			{__('Explain', 'code-snippets')}
		</ExplainSnippetButton>
	)
}

const GenerateCodeButton: React.FC = () => {
	const { snippet, isWorking, isReadOnly } = useSnippetForm()
	const [showCreateModal, setShowCreateModal] = useState(false)

	return <>
		<CloudAIButton
			primary={0 === snippet.id}
			snippet={snippet}
			disabled={isWorking || isReadOnly}
			title={__('Generate a new snippet with AI.', 'code-snippets')}
			onClick={() => setShowCreateModal(true)}
		>
			{'' === snippet.code.trim() ?
				__('Generate', 'code-snippets') :
				__('Generate New', 'code-snippets')}
		</CloudAIButton>

		<GenerateCodeModal show={showCreateModal} onClose={() => setShowCreateModal(false)} />
	</>
}

const InlineActionButtons: React.FC = () => {
	const { snippet, isWorking, submitSnippet } = useSnippetForm()

	return (
		<>
			{isWorking ? <Spinner /> : ''}

			<GenerateCodeButton />
			{'' === snippet.code.trim() ? null : <ExplainCodeButton />}

			<Button
				small
				id="save_snippet_extra"
				title={__('Save Snippet', 'code-snippets')}
				onClick={() => submitSnippet()}
				disabled={isWorking}
			>
				{__('Save Changes', 'code-snippets')}
			</Button>

			<InlineActivateButton />
		</>
	)
}

const RTLControl: React.FC = () => {
	const { codeEditorInstance } = useSnippetForm()

	return (
		<>
			<label htmlFor="snippet-code-direction" className="screen-reader-text">
				{__('Code Direction', 'code-snippets')}
			</label>

			<select id="snippet-code-direction" onChange={event =>
				codeEditorInstance?.codemirror.setOption('direction', 'rtl' === event.target.value ? 'rtl' : 'ltr')
			}>
				<option value="ltr">{__('LTR', 'code-snippets')}</option>
				<option value="rtl">{__('RTL', 'code-snippets')}</option>
			</select>
		</>
	)
}

export const SnippetEditorToolbar: React.FC = () =>
	<div className="submit-inline">
		{window.CODE_SNIPPETS_EDIT?.extraSaveButtons ? <InlineActionButtons /> : null}
		{isRTL() ? <RTLControl /> : null}
	</div>
