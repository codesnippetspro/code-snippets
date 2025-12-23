import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { CONDITION_SUBJECTS } from '../utils/conditions/subjects'
import { useRestAPI } from './useRestAPI'
import { useSnippetForm } from './useSnippetForm'
import { useSnippetsList } from './useSnippetsList'
import type { RestAPI } from './useRestAPI'
import type { Dispatch, SetStateAction } from 'react'
import type { ConditionSubjectDefinitions } from '../types/ConditionSubjectDefinitions'
import type { ConditionSubject, ConditionSubjects } from '../types/ConditionSubject'
import type { SelectGroups } from '../types/SelectOption'

type ConditionSubjectOptions = { [S in ConditionSubject]?: SelectGroups<ConditionSubjects[S]> }

interface SubjectWithDefinition<S extends ConditionSubject> {
	subject: S
	definition: ConditionSubjectDefinitions<ConditionSubjects>[S] | undefined
}

const findSubjectDefinition = <S extends ConditionSubject>(selectedSubject: S): SubjectWithDefinition<S> => {
	const subject = (<S | undefined> CONDITION_SUBJECTS[selectedSubject].useSubjectOptions) ?? selectedSubject
	const definition = CONDITION_SUBJECTS[subject]
	return { subject, definition }
}

const usePagedConditionOptions = <S extends ConditionSubject>(
	restAPI: RestAPI,
	selectedSubject: S | undefined,
	setOptionsCache: Dispatch<SetStateAction<ConditionSubjectOptions>>,
	handleOptionsLoaded: (setOptions: SetStateAction<SelectGroups<ConditionSubjects[S]> | undefined>) => void
) => {
	const [currentPage, setCurrentPage] = useState<Partial<Record<ConditionSubject, number>>>({})
	const [loadingOptions, setLoadingOptions] = useState(false)

	const loadPagedOptions = useCallback(({ subject, definition }: SubjectWithDefinition<S>) => {
		if (definition?.fetchPagedOptions && !loadingOptions) {
			const newPage = (currentPage[subject] ?? 0) + 1
			setLoadingOptions(true)

			definition.fetchPagedOptions(restAPI, newPage)
				.then(options => {
					setCurrentPage(previous => ({ ...previous, [subject]: newPage }))
					setOptionsCache(previous => ({ ...previous, [subject]: [...previous[subject] ?? [], ...options] }))
					handleOptionsLoaded(previous => [...previous ?? [], ...options])
					setLoadingOptions(false)
				})
				.catch((error: unknown) => {
					console.error(error)
					setCurrentPage(previous => ({ ...previous, [subject]: -1 }))
					setLoadingOptions(false)
				})
		}
	}, [restAPI, currentPage, loadingOptions, setOptionsCache, handleOptionsLoaded])

	const loadMoreOptions = useCallback(() => {
		if (selectedSubject !== undefined && !loadingOptions) {
			const { subject, definition } = findSubjectDefinition(selectedSubject)

			if (definition?.fetchPagedOptions && -1 !== currentPage[subject]) {
				loadPagedOptions({ subject, definition })
			}
		}
	}, [currentPage, selectedSubject, loadingOptions, loadPagedOptions])

	return { loadMoreOptions, loadPagedOptions }
}

export interface UseConditionOptions<S extends ConditionSubject> {
	loadedSubject: ConditionSubject | undefined
	objectOptions: SelectGroups<ConditionSubjects[S]> | undefined
	loadMoreOptions: VoidFunction
	clearObjectOptions: VoidFunction
	setSearchQuery: (query: string) => void
	searchingOptions: boolean
}

const SEARCH_DEBOUNCE_MS = 250

const stripTags = (value: string): string => value.replace(/<[^>]*>/g, '')

const groupHasMatchingOption = <T>(groups: SelectGroups<T> | undefined, query: string): boolean => {
	if (!groups || '' === query) {
		return false
	}

	const normalizedQuery = query.toLowerCase()

	for (const group of groups) {
		const options = 'options' in group ? group.options : [group]

		for (const option of options) {
			const label = stripTags(option.label).toLowerCase()
			if (label.includes(normalizedQuery)) {
				return true
			}
		}
	}

	return false
}

const groupHasOptionValue = <T>(groups: SelectGroups<T> | undefined, value: T): boolean => {
	if (!groups) {
		return false
	}

	for (const group of groups) {
		const options = 'options' in group ? group.options : [group]
		for (const option of options) {
			if (Object.is(option.value, value)) {
				return true
			}
		}
	}

	return false
}

const getOptionKey = (value: unknown, key?: string | number): string => {
	if (key !== undefined) {
		return 'string' === typeof key ? key : key.toString()
	}

	if ('string' === typeof value) {
		return value
	}

	if ('number' === typeof value) {
		return value.toString()
	}

	return JSON.stringify(value)
}

const mergeOptionGroups = <T>(existing: SelectGroups<T>, extra: SelectGroups<T>): SelectGroups<T> => {
	const seen = new Set<string>()
	const merged: ((typeof existing)[number])[] = []

	const addGroup = (group: (typeof existing)[number]) => {
		const options = 'options' in group ? group.options : [group]
		const filteredOptions = options.filter(option => {
			const key = getOptionKey(option.value, option.key)
			if (seen.has(key)) {
				return false
			}
			seen.add(key)
			return true
		})

		if ('options' in group) {
			if (0 < filteredOptions.length) {
				merged.push({ ...group, options: filteredOptions })
			}
		} else if (filteredOptions[0]) {
			merged.push(filteredOptions[0])
		}
	}

	for (const group of existing) {
		addGroup(group)
	}

	for (const group of extra) {
		addGroup(group)
	}

	return merged
}

export const useConditionOptions = <S extends ConditionSubject>(
	selectedSubject: S | undefined,
	selectedValues: readonly ConditionSubjects[S][] = []
): UseConditionOptions<S> => {
	const { api } = useRestAPI()
	const { snippet } = useSnippetForm()
	const { snippetsList } = useSnippetsList()
	const [optionsCache, setOptionsCache] = useState<ConditionSubjectOptions>({})
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<SelectGroups<ConditionSubjects[S]> | undefined>(undefined)
	const [loadingOptions, setLoadingOptions] = useState(false)
	const [searchQuery, setSearchQuery] = useState('')
	const [searchingOptions, setSearchingOptions] = useState(false)
	const searchRequestId = useRef(0)
	const searchTimeout = useRef<ReturnType<typeof setTimeout> | undefined>(undefined)
	const lastSearchKey = useRef<string>('')
	const selectedOptionsRequestId = useRef(0)
	const lastSelectedOptionsKey = useRef<string>('')

	const handleOptionsLoaded = useCallback((options: SetStateAction<SelectGroups<ConditionSubjects[S]> | undefined>) => {
		setLoadedSubject(selectedSubject)
		setObjectOptions(options)
	}, [selectedSubject])

	const { loadPagedOptions, loadMoreOptions } = usePagedConditionOptions(api, selectedSubject, setOptionsCache, handleOptionsLoaded)

	const loadAllOptions = useCallback(({ subject, definition }: SubjectWithDefinition<S>) => {
		if (definition?.fetchAllOptions && !loadingOptions) {
			setLoadingOptions(true)

			definition.fetchAllOptions(api)
				.then(options => {
					setOptionsCache(previous => ({ ...previous, [subject]: options }))
					handleOptionsLoaded(options)
					setLoadingOptions(false)
				})
				.catch((error: unknown) => {
					console.error(error)
					setLoadingOptions(false)
				})
		}
	}, [handleOptionsLoaded, loadingOptions, api])

	useEffect(() => {
		if (objectOptions === undefined && selectedSubject !== undefined) {
			const { subject, definition } = findSubjectDefinition(selectedSubject)

			if (optionsCache[subject]) {
				handleOptionsLoaded(optionsCache[subject])
			} else if (definition?.options) {
				handleOptionsLoaded(definition.options)
			} else if (definition?.deriveOptions && snippetsList) {
				handleOptionsLoaded(definition.deriveOptions(snippet, snippetsList))
			} else if (definition?.fetchAllOptions) {
				loadAllOptions({ subject, definition })
			} else if (definition?.fetchPagedOptions) {
				loadPagedOptions({ subject, definition })
			}
		}
	}, [handleOptionsLoaded, loadAllOptions, loadPagedOptions, objectOptions, optionsCache, selectedSubject, snippet, snippetsList])

	const activeSubjectDefinition = useMemo(
		() => selectedSubject === undefined ? undefined : findSubjectDefinition(selectedSubject),
		[selectedSubject]
	)

	useEffect(() => {
		setSearchQuery('')
		setSearchingOptions(false)
		searchRequestId.current = searchRequestId.current + 1
		lastSearchKey.current = ''
		lastSelectedOptionsKey.current = ''
		if (searchTimeout.current) {
			clearTimeout(searchTimeout.current)
		}
		selectedOptionsRequestId.current = selectedOptionsRequestId.current + 1
	}, [selectedSubject])

	useEffect(() => {
		if (selectedSubject === undefined) {
			return
		}

		const query = searchQuery.trim()
		const { subject, definition } = activeSubjectDefinition ?? findSubjectDefinition(selectedSubject)

		if ('' === query) {
			setSearchingOptions(false)
			lastSearchKey.current = ''

			if (optionsCache[subject]) {
				const cachedOptions: SelectGroups<ConditionSubjects[S]> | undefined = optionsCache[subject]
				handleOptionsLoaded(cachedOptions)
			}

			return
		}

		if (!definition?.fetchSearchOptions) {
			return
		}

		const currentSearchKey = `${subject}:${query}`
		if (currentSearchKey === lastSearchKey.current) {
			return
		}

		const cachedOptions: SelectGroups<ConditionSubjects[S]> | undefined = optionsCache[subject]
		if (groupHasMatchingOption(cachedOptions ?? objectOptions, query)) {
			setSearchingOptions(false)
			return
		}

		searchRequestId.current = searchRequestId.current + 1
		const requestId = searchRequestId.current

		if (searchTimeout.current) {
			clearTimeout(searchTimeout.current)
		}

		searchTimeout.current = setTimeout(() => {
			setSearchingOptions(true)
			lastSearchKey.current = currentSearchKey

			definition.fetchSearchOptions?.(api, query)
				.then((options: SelectGroups<ConditionSubjects[S]>) => {
					if (requestId !== searchRequestId.current) {
						return
					}

					setOptionsCache(previous => ({
						...previous,
						[subject]: mergeOptionGroups(previous[subject] ?? [], options)
					}))
					handleOptionsLoaded(previous => mergeOptionGroups(previous ?? [], options))
					setSearchingOptions(false)
				})
				.catch((error: unknown) => {
					if (requestId !== searchRequestId.current) {
						return
					}

					lastSearchKey.current = ''
					console.error(error)
					setSearchingOptions(false)
				})
		}, SEARCH_DEBOUNCE_MS)

		return () => {
			if (searchTimeout.current) {
				clearTimeout(searchTimeout.current)
			}
		}
	}, [activeSubjectDefinition, api, handleOptionsLoaded, objectOptions, optionsCache, searchQuery, selectedSubject])

	useEffect(() => {
		if (selectedSubject === undefined) {
			return
		}

		if ('' !== searchQuery.trim()) {
			return
		}

		const { subject, definition } = activeSubjectDefinition ?? findSubjectDefinition(selectedSubject)
		if (!definition?.fetchSelectedOptions) {
			return
		}

		const uniqueSelectedValues = Array.from(new Set(selectedValues))
		if (0 === uniqueSelectedValues.length) {
			return
		}

		const cachedOptions: SelectGroups<ConditionSubjects[S]> | undefined = optionsCache[subject]
		const currentOptions = cachedOptions ?? objectOptions

		const missingValues = uniqueSelectedValues.filter(value => !groupHasOptionValue(currentOptions, value))
		if (0 === missingValues.length) {
			return
		}

		const selectedKey = `${subject}:${missingValues.map(value => getOptionKey(value)).sort().join('|')}`
		if (selectedKey === lastSelectedOptionsKey.current) {
			return
		}

		selectedOptionsRequestId.current = selectedOptionsRequestId.current + 1
		const requestId = selectedOptionsRequestId.current
		lastSelectedOptionsKey.current = selectedKey

		definition.fetchSelectedOptions(api, missingValues)
			.then(extraOptions => {
				if (requestId !== selectedOptionsRequestId.current) {
					return
				}

				setOptionsCache(previous => ({
					...previous,
					[subject]: mergeOptionGroups(previous[subject] ?? [], extraOptions)
				}))
				handleOptionsLoaded(previous => mergeOptionGroups(previous ?? [], extraOptions))
			})
			.catch((error: unknown) => {
				if (requestId !== selectedOptionsRequestId.current) {
					return
				}
				lastSelectedOptionsKey.current = ''
				console.error(error)
			})
	}, [activeSubjectDefinition, api, objectOptions, optionsCache, searchQuery, selectedSubject, selectedValues, handleOptionsLoaded])

	const clearObjectOptions = useCallback(() => {
		setLoadedSubject(undefined)
		setObjectOptions(undefined)
		setSearchQuery('')
		setSearchingOptions(false)
		lastSearchKey.current = ''
		lastSelectedOptionsKey.current = ''
	}, [])

	return { clearObjectOptions, loadMoreOptions, loadedSubject, objectOptions, setSearchQuery, searchingOptions }
}
