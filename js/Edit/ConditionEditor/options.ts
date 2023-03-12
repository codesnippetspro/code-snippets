import { __ } from '@wordpress/i18n'
import { Condition, Option } from './types'

export const conditionOptions: Record<keyof Condition, Option[]> = {
	subject: [
		{ value: 'post_type', label: __('Post type', 'code-snippets') },
		{ value: 'page', label: __('Page', 'code-snippets') }
	],
	operator: [
		{ value: 'eq', label: __('is equal to', 'code-snippets') },
		{ value: 'neq', label: __('is not equal to', 'code-snippets') }
	],
	object: []
}
