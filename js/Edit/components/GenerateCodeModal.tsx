import { Button, Modal, TextareaControl, Flex, Spinner } from '@wordpress/components'
import { Warning } from '@wordpress/block-editor'
import { __ } from '@wordpress/i18n'
import { isAxiosError } from 'axios'
import React, { Dispatch, MouseEventHandler, SetStateAction, useState } from 'react'
import { Snippet } from '../../types/Snippet'
import { useGenerativeAPI } from '../../utils/api/gpt'
import { GenerateIcon } from '../buttons/GenerateButton'
import { useSnippetForm } from '../SnippetForm/context'

interface PromptFormProps {
	snippet: Snippet
	prompt: string
	setPrompt: Dispatch<SetStateAction<string>>
	isWaiting: boolean
	errorMessage: string | undefined
	onGenerate: MouseEventHandler<HTMLButtonElement>
}

const PromptForm: React.FC<PromptFormProps> = ({
	snippet,
	prompt,
	setPrompt,
	isWaiting,
	errorMessage,
	onGenerate
}) =>
	<form>
		<TextareaControl
			label={__('What would you like to do?', 'code-snippets')}
			value={prompt}
			onChange={value => setPrompt(value)}
			disabled={isWaiting}
			rows={2}
		/>

		{'string' === typeof errorMessage ?
			<Warning>
				{`${__('An error occurred attempting to contact the API.')} ${errorMessage}`}
			</Warning> : null}

		{snippet.name || snippet.code || snippet.tags.length || snippet.desc ?
			<p><strong>{__('This action will overwrite the current snippet.')}</strong></p> :
			null}

		<Flex direction="row" justify="flex-end">
			{isWaiting ? <Spinner /> : ''}
			<Button variant="primary" onClick={onGenerate} type="submit" disabled={isWaiting}>
				{isWaiting ? __('Generating…', 'code-snippets') : __('Generate', 'code-snippets')}
			</Button>
		</Flex>
	</form>

export interface GenerateCodeModalProps {
	onClose: VoidFunction
}

export const GenerateCodeModal: React.FC<GenerateCodeModalProps> = ({ onClose }) => {
	const { snippet, updateSnippet } = useSnippetForm()
	const { generateSnippet } = useGenerativeAPI()
	const [errorMessage, setErrorMessage] = useState<string | undefined>(undefined)
	const [isWaiting, setIsWaiting] = useState(false)
	const [prompt, setPrompt] = useState('')

	const onGenerate: MouseEventHandler<HTMLButtonElement> = event => {
		event.preventDefault()
		setIsWaiting(true)
		setErrorMessage(undefined)

		generateSnippet(prompt)
			.then(generated => {
				updateSnippet(previous => ({ ...previous, ...generated }))
				setIsWaiting(false)
				onClose()
			})
			.catch(error => {
				setIsWaiting(false)
				setErrorMessage(isAxiosError(error) ? error.message : '')
			})
	}

	return (
		<Modal
			icon={<GenerateIcon />}
			title={__('Generate with Cloud AI', 'code-snippets')}
			onRequestClose={() => onClose()}
			className="cloud-create-modal"
		>
			<PromptForm
				snippet={snippet}
				prompt={prompt}
				setPrompt={setPrompt}
				isWaiting={isWaiting}
				errorMessage={errorMessage}
				onGenerate={onGenerate}
			/>
		</Modal>
	)
}
