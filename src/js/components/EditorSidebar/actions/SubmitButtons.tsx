import React from 'react'
import { __ } from '@wordpress/i18n'
import { SubmitSnippetAction } from '../../../hooks/useSubmitSnippet'
import { isCondition } from '../../../utils/snippets/snippets'
import { isNetworkAdmin } from '../../../utils/screen'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { SubmitButton } from '../../common/SubmitButton'
import type { SubmitButtonProps } from '../../common/SubmitButton'

const SaveButton = (props: SubmitButtonProps) => {
	const { snippet } = useSnippetForm()

	return (
		<SubmitButton
			large
			name={SubmitSnippetAction.SAVE}
			text={isCondition(snippet)
				? __('Save Condition', 'code-snippets')
				: __('Save Snippet', 'code-snippets')}
			{...props}
		/>
	)
}

interface ActivateOrDeactivateButtonProps {
	primaryActivate: boolean
}

const ActivateOrDeactivateButton: React.FC<ActivateOrDeactivateButtonProps> = ({ primaryActivate }) => {
	const { snippet, isWorking } = useSnippetForm()

	switch (true) {
		case isCondition(snippet) || snippet.shared_network && isNetworkAdmin():
			return null

		case 'single-use' === snippet.scope:
			return (
				<SubmitButton
					large
					name={SubmitSnippetAction.SAVE_AND_EXECUTE}
					disabled={isWorking}
					text={__('Save and Execute Once', 'code-snippets')}
				/>
			)

		case snippet.active:
			return (
				<SubmitButton
					name={SubmitSnippetAction.SAVE_AND_DEACTIVATE}
					disabled={isWorking}
					large
					text={__('Save and Deactivate', 'code-snippets')}
				/>
			)

		default:
		case !snippet.active:
			return (
				<SubmitButton
					name={SubmitSnippetAction.SAVE_AND_ACTIVATE}
					primary={primaryActivate}
					disabled={isWorking}
					large
					text={__('Save and Activate', 'code-snippets')}
				/>
			)
	}
}

export const SubmitButtons: React.FC = () => {
	const { snippet } = useSnippetForm()

	const activateByDefault =
		!!window.CODE_SNIPPETS_EDIT?.activateByDefault &&
		!snippet.active && 'single-use' !== snippet.scope &&
		(!snippet.shared_network || !isNetworkAdmin())

	return <>
		{activateByDefault && <SaveButton primary={!activateByDefault} />}
		<ActivateOrDeactivateButton primaryActivate={activateByDefault} />
		{!activateByDefault && <SaveButton primary />}
	</>
}
