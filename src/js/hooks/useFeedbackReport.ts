import { useCallback, useMemo, useRef, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useDuplicateReports } from './useDuplicateReports'
import { useRestAPI } from './useRestAPI'
import type {
	DuplicateReport,
	FeedbackConfig,
	FeedbackDraft,
	FeedbackReportRequest,
	FeedbackReportResponse
} from '../types/Feedback'

/** Most captured errors to attach to a report. */
const MAX_JS_ERRORS = 10

/** Shortest title that summarises anything. */
const MIN_TITLE_LENGTH = 8

/** Shortest free-text answer that describes anything. */
const MIN_TEXT_LENGTH = 20

interface AxiosLikeError {
	response?: {
		data?: {
			message?: string
		}
	}
}

export interface FeedbackValidity {
	title: boolean
	description: boolean
	steps: boolean
}

export interface FeedbackReport {
	draft: FeedbackDraft
	duplicates: DuplicateReport[]
	errorMessage: string
	invalidFields: FeedbackValidity
	isSending: boolean
	result: FeedbackReportResponse | undefined
	updateDraft: (changes: Partial<FeedbackDraft>) => void
	submit: VoidFunction
}

const emptyDraft = (config: FeedbackConfig): FeedbackDraft => ({
	type: '',
	title: '',
	description: '',
	steps: '',
	comments: '',
	name: config.user.name,
	email: config.user.email,
	isolation: {
		plugin_only: false,
		blank_theme: false,
		reproducible: false
	}
})

const describeBrowser = (): FeedbackReportRequest['browser'] => ({
	userAgent: navigator.userAgent,
	viewport: `${window.innerWidth}×${window.innerHeight}`,
	screen: `${window.screen.width}×${window.screen.height}`,
	language: navigator.language
})

/**
 * The message an error carries, whether it came from WordPress, from the cloud, or from
 * the request never arriving.
 */
const describeError = (error: unknown): string =>
	(<AxiosLikeError>error).response?.data?.message ??
		__('The report could not be sent. Check the connection and try again.', 'code-snippets')

/** The first message describing what is still missing from a report. */
const firstValidationMessage = (invalid: FeedbackValidity): string => {
	if (invalid.title) {
		return __('Give the report a one-line title.', 'code-snippets')
	}

	if (invalid.description) {
		return __('Add a little more detail to the description.', 'code-snippets')
	}

	if (invalid.steps) {
		return __('List the steps that reproduce the bug.', 'code-snippets')
	}

	return ''
}

export const useFeedbackReport = (config: FeedbackConfig): FeedbackReport => {
	const { api } = useRestAPI()

	const [draft, setDraft] = useState<FeedbackDraft>(() => emptyDraft(config))
	const [errorMessage, setErrorMessage] = useState('')
	const [isSending, setIsSending] = useState(false)
	const [result, setResult] = useState<FeedbackReportResponse>()

	const idempotencyKey = useRef(window.crypto.randomUUID())

	const invalidFields = useMemo(() => ({
		title: MIN_TITLE_LENGTH > draft.title.trim().length,
		description: MIN_TEXT_LENGTH > draft.description.trim().length,
		steps: 'bug' === draft.type && MIN_TEXT_LENGTH > draft.steps.trim().length
	}), [draft.type, draft.title, draft.description, draft.steps])

	const updateDraft = useCallback((changes: Partial<FeedbackDraft>) => {
		setDraft(current => ({ ...current, ...changes }))
	}, [])

	const duplicates = useDuplicateReports(config.searchUrl, draft.title)

	const submit = useCallback(() => {
		const type = draft.type

		if (!type) {
			return
		}

		const message = firstValidationMessage(invalidFields)

		if (message) {
			setErrorMessage(message)
			return
		}

		setErrorMessage('')
		setIsSending(true)

		const request: FeedbackReportRequest = {
			type,
			idempotency_key: idempotencyKey.current,
			title: draft.title.trim(),
			description: draft.description.trim(),
			steps: 'bug' === type ? draft.steps.trim() : '',
			comments: draft.comments.trim(),
			isolation: draft.isolation,
			name: draft.name.trim(),
			email: draft.email.trim(),
			page_url: window.location.href,
			js_errors: (window.codeSnippetsErrors ?? []).slice(0, MAX_JS_ERRORS),
			browser: describeBrowser()
		}

		api.post<FeedbackReportResponse, FeedbackReportRequest>(config.restUrl, request)
			.then(setResult)
			.catch((error: unknown) => setErrorMessage(describeError(error)))
			.finally(() => setIsSending(false))
	}, [api, config.restUrl, draft, invalidFields])

	return { draft, duplicates, errorMessage, invalidFields, isSending, result, updateDraft, submit }
}
