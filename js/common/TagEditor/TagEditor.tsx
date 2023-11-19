/**
 * Code based on Tagger, copyright (c) 2018-2022 Jakub T. Jankiewicz <https://jcubic.pl/me>
 * Released under the MIT license.
 */
import React, { InputHTMLAttributes, KeyboardEventHandler, useRef, useState } from 'react'
import { SuggestionList } from './SuggestionList'
import { TagList } from './TagList'

const COMPLETION_MIN_LENGTH = 2
const SPECIAL_CHARS_RE = /(?<specialChar>[-\\^$[\]()+{}?*.|])/g

const escapeRegex = (str: string) =>
	str.replace(SPECIAL_CHARS_RE, '\\$1')

const isNotEmpty = (value: string | null | undefined): value is string =>
	!['', '""', "''", '``', undefined, null].includes(value)

const isTagSelected = (selected: string[], tag: string) => {
	if (!selected?.includes(tag)) {
		return false
	}
	const re = new RegExp(`^${escapeRegex(tag)}`)
	return 1 === selected.filter(test_tag => re.test(test_tag)).length
}

export type TagEditorCompletions = string[] | ((value?: string) => string[]) | ((value?: string) => Promise<string[]>) | undefined

const buildCompletions = (completions: TagEditorCompletions, value: string | undefined, onBuild: (result: string[]) => void) => {
	if (completions) {
		if ('function' === typeof completions) {
			const result = completions(value)
			if (result && 'then' in result && 'function' === typeof result.then) {
				result.then(list => onBuild(list))

			} else if (result instanceof Array) {
				onBuild(result)
			}
		} else {
			onBuild(completions)
		}
	}
}

export interface TagEditorProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'onChange'> {
	id: string
	tags: string[]
	onChange: (tags: string[]) => void
	tagLimit?: number
	completions?: TagEditorCompletions
	addOnBlur?: boolean
	allowSpaces?: boolean
	allowDuplicates?: boolean
	completionMinLength?: number
}

// eslint-disable-next-line max-lines-per-function
export const TagEditor: React.FC<TagEditorProps> = ({
	id,
	tags,
	onChange,
	tagLimit,
	completions,
	addOnBlur = false,
	allowSpaces = true,
	allowDuplicates = false,
	completionMinLength = COMPLETION_MIN_LENGTH,
	...inputProps
}) => {
	const inputRef = useRef<HTMLInputElement>(null)
	const [inputValue, setInputValue] = useState<string>('')
	const [completionOpen, setCompletionOpen] = useState(false)
	const [completionList, setCompletionList] = useState<string[]>([])
	const [lastCompletionList, setLastCompletionList] = useState<string[]>()

	const isTagLimit = () => Boolean(tagLimit && 0 < tagLimit && tags.length >= tagLimit)

	const addTag = (tag: string = inputValue.trim()) => {
		const isTagValid = isNotEmpty(tag) && !isTagLimit() && (allowDuplicates || !tags.includes(tag))

		if (isTagValid) {
			onChange([...tags, tag])
		}

		setInputValue('')
		return isTagValid
	}

	const removeTag = (tag?: string) =>
		onChange(tag ? tags.filter(item => item !== tag) : tags.slice(0, -1))

	const triggerCompletion = (openList = inputValue.length >= completionMinLength) => {
		if (openList) {
			buildCompletions(completions, inputValue, list => {
				setLastCompletionList(completionList)

				if (list.length) {
					setCompletionList(allowDuplicates ? list : list.filter(item => !tags.includes(item)))
				}
			})
		}

		setCompletionOpen(openList)
	}

	const keyboardHandler: KeyboardEventHandler<HTMLInputElement> = event => {
		const { key, ctrlKey, metaKey } = event

		if ('Enter' === key || ',' === key || ' ' === key && !allowSpaces) {
			addTag()

		} else if ('Backspace' === key && !inputValue) {
			removeTag()

		} else if (' ' === key && (ctrlKey || metaKey)) {
			triggerCompletion(true)

		} else if (!isTagLimit() || 'Tab' === key) {
			return
		}

		event.preventDefault()
		event.stopPropagation()
	}

	const inputHandler = () => {
		if (completionOpen && lastCompletionList && isTagSelected(lastCompletionList, inputValue.trim())) {
			if (addTag()) {
				setCompletionOpen(false)
			}
		} else {
			triggerCompletion()
		}
	}

	return <div className="tagger">
		<ul onClick={() => inputRef.current?.focus()}>
			<TagList tags={tags} onRemove={removeTag} />
			<li className="tagger-new">
				<input
					{...inputProps}
					id={id}
					type="text"
					ref={inputRef}
					value={inputValue}
					list={`tagger-completion-${completionOpen ? '' : '-disabled'}${id}`}
					onBlur={() => addOnBlur ? addTag() : undefined}
					onChange={event => setInputValue(event.target.value)}
					onKeyDown={keyboardHandler}
					onInput={inputHandler}
				/>
				<SuggestionList
					id={`tagger-completion-${id}`}
					suggestions={completionList.filter(suggestion => !tags.includes(suggestion))}
					onSelect={suggestion => {
						addTag(suggestion)
						setCompletionList([])
						setCompletionOpen(false)
					}}
				/>
			</li>
		</ul>
	</div>
}
