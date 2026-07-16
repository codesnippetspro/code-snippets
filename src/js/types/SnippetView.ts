export const SNIPPET_VIEWS = <const> ['card', 'table']

export type SnippetView = typeof SNIPPET_VIEWS[number]

export const DEFAULT_SNIPPET_VIEW: SnippetView = 'table'
