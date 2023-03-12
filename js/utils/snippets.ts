import { Snippet, SnippetType } from '../types/Snippet'

export const getSnippetType = ({ scope }: Snippet): SnippetType => {
	if (scope.endsWith('-css')) {
		return 'css'
	}

	if (scope.endsWith('-js')) {
		return 'js'
	}

	if (scope.endsWith('content')) {
		return 'html'
	}

	if ('condition' === scope) {
		return 'cond'
	}

	return 'php'
}

export const isProType = (type: SnippetType): boolean =>
	'css' === type || 'js' === type
