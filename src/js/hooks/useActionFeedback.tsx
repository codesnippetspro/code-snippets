import React, { useCallback, useMemo, useState } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { createContextHook } from '../utils/bootstrap'
import { describeError, handleUnknownError } from '../utils/errors'
import type { PropsWithChildren } from 'react'

export interface ActionFailure {
	id: number
	/** What the person was trying to do, already translated. */
	action: string
	/** What went wrong, in terms they can act on. */
	detail: string
}

export interface ActionFeedbackContext {
	failures: readonly ActionFailure[]
	/**
	 * Report that an action did not complete.
	 *
	 * Every snippet action used to send its error to the console and nothing
	 * else, so a failed request looked identical to a click that had never
	 * registered: the row did not change and nothing explained why. That left
	 * people unable to tell a permissions problem from a plugin conflict, and
	 * left us unable to ask them anything useful.
	 */
	reportFailure: (action: string, error: unknown) => void
	dismissFailure: (id: number) => void
}

const [Context, useActionFeedback] = createContextHook<ActionFeedbackContext>('useActionFeedback')

export const WithActionFeedbackContext: React.FC<PropsWithChildren> = ({ children }) => {
	const [failures, setFailures] = useState<ActionFailure[]>([])

	const reportFailure = useCallback((action: string, error: unknown) => {
		// Still logged, so the full object remains available in the console.
		handleUnknownError(error)

		setFailures(current => [
			...current.filter(failure => failure.action !== action),
			{ id: Date.now() + current.length, action, detail: describeError(error) }
		])
	}, [])

	const dismissFailure = useCallback((id: number) => {
		setFailures(current => current.filter(failure => failure.id !== id))
	}, [])

	const value = useMemo<ActionFeedbackContext>(
		() => ({ failures, reportFailure, dismissFailure }),
		[failures, reportFailure, dismissFailure]
	)

	return <Context.Provider value={value}>{children}</Context.Provider>
}

/**
 * Build the sentence shown to the person, given what they were doing.
 */
export const failureMessage = (failure: ActionFailure): string =>
	sprintf(
		/* translators: 1: what the user was trying to do, 2: reason it did not work. */
		__('Could not %1$s. %2$s', 'code-snippets'),
		failure.action,
		failure.detail
	)

export { useActionFeedback }
