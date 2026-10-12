export type FeedbackType = 'bug' | 'feature' | 'feedback'

export interface FeedbackConfig {
	restUrl: string
	searchUrl: string
	nonce: string
	user: {
		name: string
		email: string
	}
	summary: Record<string, string>
	badge: string
	version: string
	edition: 'free' | 'pro'
}

export interface FeedbackIsolation {
	plugin_only: boolean
	blank_theme: boolean
	reproducible: boolean
}

export interface FeedbackDraft {
	type: FeedbackType | ''
	title: string
	description: string
	steps: string
	comments: string
	name: string
	email: string
	isolation: FeedbackIsolation
}

export interface FeedbackReportRequest {
	type: FeedbackType
	idempotency_key: string
	title: string
	description: string
	steps: string
	comments: string
	isolation: FeedbackIsolation
	name: string
	email: string
	page_url: string
	js_errors: string[]
	browser: {
		userAgent: string
		viewport: string
		screen: string
		language: string
	}
}

export interface FeedbackReportResponse {
	sent: boolean
	reference: string
	url: string
}

export interface DuplicateReport {
	title: string
	url: string
}

export interface DuplicateSearchResponse {
	results: DuplicateReport[]
}
