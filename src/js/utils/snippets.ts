import { addQueryArgs } from '@wordpress/url'
import { __, sprintf } from '@wordpress/i18n'
import { parseSnippetObject } from './objects'
import { isNetworkAdmin } from './screen'
import type { SelectGroup, SelectOption } from '../types/SelectOption'
import type { Snippet, SnippetType } from '../types/Snippet'

const PRO_TYPES = new Set<SnippetType>(['css', 'js'])

const TYPE_LABELS: Record<SnippetType, string> = {
	php: __('Functions (PHP)', 'code-snippets'),
	html: __('Content (Mixed)', 'code-snippets'),
	css: __('Styles (CSS)', 'code-snippets'),
	js: __('Scripts (JS)', 'code-snippets'),
	cond: __('Conditions', 'code-snippets')
}

const defaults: Omit<Snippet, 'tags' | 'conditions'> = {
	id: 0,
	name: '',
	code: '',
	desc: '',
	scope: 'global',
	modified: '',
	active: false,
	network: isNetworkAdmin(),
	shared_network: null,
	priority: 10,
	conditionId: 0
}

export const createSnippetObject = (fields: unknown = null): Snippet =>
	parseSnippetObject(fields, { ...defaults, tags: [], conditions: {} })

export const getSnippetType = ({ scope }: Pick<Snippet, 'scope'>): SnippetType => {
	switch (true) {
		case scope.endsWith('-css'):
			return 'css'

		case scope.endsWith('-js'):
			return 'js'

		case scope.endsWith('content'):
			return 'html'

		case 'condition' === scope:
			return 'cond'

		default:
			return 'php'
	}
}

export const getSnippetEditUrl = ({ id }: Pick<Snippet, 'id'>): string =>
	addQueryArgs(window.CODE_SNIPPETS?.urls.edit, { id })

export const getSnippetDisplayName = (snippet: Pick<Snippet, 'name' |'id'>): string =>
	'' === snippet.name.trim()
		// translators: %s: snippet identifier.
		? sprintf(__('Snippet #%d', 'code-snippets'), snippet.id)
		: snippet.name

export const getConditionDisplayName = (condition: Pick<Snippet, 'name' |'id'>): string =>
	'' === condition.name.trim()
		// translators: %s: condition identifier.
		? sprintf(__('Condition #%d', 'code-snippets'), condition.id)
		: condition.name

export const isCondition = (snippet: Pick<Snippet, 'scope'>): boolean =>
	'condition' === snippet.scope

export const isProSnippet = (snippet: Pick<Snippet, 'scope'>): boolean =>
	PRO_TYPES.has(getSnippetType(snippet))

export const isProType = (type: SnippetType): boolean =>
	PRO_TYPES.has(type)

export const buildSnippetSelectOptions = (snippets: Snippet[]): SelectGroup<Snippet>[] => {
	const optionGroups = new Map<SnippetType, SelectOption<Snippet>[]>

	for (const snippet of snippets) {
		const option: SelectOption<Snippet> = {
			label: getSnippetDisplayName(snippet),
			value: snippet
		}

		const type = getSnippetType(snippet)
		const optionGroup = optionGroups.get(type)

		if (optionGroup) {
			optionGroup.push(option)
		} else {
			optionGroups.set(type, [option])
		}
	}

	return [...optionGroups].map(([type, options]) =>
		({ label: TYPE_LABELS[type], options }))
}
