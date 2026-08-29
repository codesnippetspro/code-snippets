import React from 'react'
import { __ } from '@wordpress/i18n'
import { failureMessage, useActionFeedback } from '../../../hooks/useActionFeedback'
import { DismissibleNotice } from '../../common/Notice'

/**
 * Show any snippet action that did not complete.
 *
 * Rendered above the table so a failure appears where the person is already
 * looking, rather than only in the browser console.
 */
export const ActionFeedback: React.FC = () => {
	const { failures, dismissFailure } = useActionFeedback()

	if (0 === failures.length) {
		return null
	}

	return (
		<>
			{failures.map(failure =>
				<DismissibleNotice
					key={failure.id}
					type="error"
					className="code-snippets-action-failure"
					onDismiss={() => dismissFailure(failure.id)}
				>
					<p>
						{failureMessage(failure)}{' '}
						{__('Nothing has been changed.', 'code-snippets')}
					</p>
				</DismissibleNotice>)}
		</>
	)
}
