import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { useSnippetsAPI } from '../../../../hooks/useSnippetsAPI'
import { TooltipButton } from '../../../common/TooltipButton'
import { useSnippetForm } from '../../SnippetForm/WithSnippetFormContext'

export const LockControl: React.FC = () => {
	const { update } = useSnippetsAPI()
	const { acceptSnippet, snippet, setSnippet, isWorking, setIsWorking, setCurrentNotice } = useSnippetForm()

	const handleToggle = () => {
		setIsWorking(true)
		setSnippet(previous => ({ ...previous, locked: !previous.locked }))

		update({ id: snippet.id, network: snippet.network, locked: !snippet.locked })
			.then(result => {
				acceptSnippet(result)

				setCurrentNotice(['updated', result.locked
					? __('Snippet <strong>locked</strong>.', 'code-snippets')
					: __('Snippet <strong>unlocked</strong>.', 'code-snippets')])
			})
			.catch(() => setCurrentNotice(['error', __('Unable to lock snippet.', 'code-snippets')]))
			.finally(() => setIsWorking(false))
	}

	return (
		<div className={classnames('snippet-lock-control', { 'tooltip tooltip-block tooltip-end': !isWorking })}>
			<TooltipButton
				small block end
				primary={snippet.locked}
				className="snippet-lock-button"
				containerClassName="snippet-lock-control"
				disabled={isWorking}
				onClick={handleToggle}
				tooltip={snippet.locked
					? __('Unlock this snippet to allow editing and deletion.', 'code-snippets')
					: __('Mark this snippet as read-only to prevent accidental changes or deletion.', 'code-snippets')}
			>
				{snippet.locked
					? <span className="dashicons dashicons-unlock" aria-hidden="true" />
					: <span className="dashicons dashicons-lock" aria-hidden="true" />}
			</TooltipButton>
		</div>
	)
}
