import React from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetsAPI } from '../../../../hooks/useSnippetsAPI'
import { Tooltip } from '../../../common/Tooltip'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'

export const LockControl: React.FC = () => {
	const { snippet, setSnippet, isWorking, setIsWorking, setCurrentNotice } = useSnippetForm()
	const { update } = useSnippetsAPI()

	const handleToggle = () => {
		setSnippet(previous => ({ ...previous, locked: !previous.locked }))
		setIsWorking(true)

		update({ id: snippet.id, network: snippet.network, locked: !snippet.locked })
			.then(result => {
				setSnippet(result)

				setCurrentNotice(['updated', result.locked
					? __('Snippet <strong>locked</strong>.', 'code-snippets')
					: __('Snippet <strong>unlocked</strong>.', 'code-snippets')])
			})
			.catch(() => setCurrentNotice(['error', __('Unable to lock snippet.', 'code-snippets')]))
			.finally(() => setIsWorking(false))
	}

	return (
		<div className="inline-form-field lock-control-container">
			<label htmlFor="snippet-lock">{__('Lock snippet', 'code-snippets')}</label>

			<Tooltip block end>
				{__('Mark this snippet as read-only to prevent accidental changes or deletion.', 'code-snippets')}
			</Tooltip>

			<span className="lock-status-text">
				{snippet.locked ? __('Locked', 'code-snippets') : __('Unlocked', 'code-snippets')}
			</span>

			<input
				id="snippet-lock"
				type="checkbox"
				checked={snippet.locked}
				disabled={isWorking}
				className="switch"
				onChange={handleToggle}
			/>
		</div>
	)
}
