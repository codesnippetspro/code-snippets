import { useCallback, useEffect, useState } from 'react'
import { CONDITION_SUBJECTS } from '../utils/conditions/subjects'
import { handleUnknownError } from '../utils/errors'
import { REST_API_AXIOS_CONFIG } from '../utils/restAPI'
import { useAxios } from './useAxios'
import { useSnippetForm } from './useSnippetForm'
import type { ConditionSubjectDefinitions } from '../types/ConditionSubjectDefinitions'
import type { AxiosAPI } from './useAxios'
import type { Snippet } from '../types/Snippet'
import type { Dispatch, SetStateAction } from 'react'
import type { ConditionSubject, ConditionSubjects } from '../types/ConditionSubject'
import type { SelectGroups } from '../types/SelectOption'

export interface UseConditionOptions<S extends ConditionSubject> {
	loadedSubject: ConditionSubject | undefined
	objectOptions: SelectGroups<ConditionSubjects[S]> | undefined
	loadMoreOptions: VoidFunction
	clearObjectOptions: VoidFunction
}

type ConditionSubjectOptions = { [S in ConditionSubject]?: SelectGroups<ConditionSubjects[S]> }

const findSubjectDefinition = <S extends ConditionSubject>(currentSubject: S): {
	subject: S
	definition: ConditionSubjectDefinitions<ConditionSubjects>[S]
} => {
	const subject = (<S | undefined> CONDITION_SUBJECTS[currentSubject].useSubjectOptions) ?? currentSubject
	const definition = CONDITION_SUBJECTS[subject]
	return { subject, definition }
}

const resolveOptions = <S extends ConditionSubject>({
	restAPI,
	snippet,
	currentPage,
	snippetsList,
	optionsCache,
	currentSubject,
	setCurrentPage
}: {
	restAPI: AxiosAPI,
	snippet: Snippet,
	currentPage: Partial<Record<ConditionSubject, number>>,
	snippetsList: readonly Snippet[] | undefined,
	optionsCache: ConditionSubjectOptions,
	currentSubject: S,
	setCurrentPage: Dispatch<SetStateAction<Partial<Record<ConditionSubject, number>>>>
}): SelectGroups<ConditionSubjects[S]> | Promise<SelectGroups<ConditionSubjects[S]>> | undefined => {
	const { subject, definition } = findSubjectDefinition(currentSubject)

	if (optionsCache[subject]) {
		return optionsCache[subject]
	}

	if (definition.options) {
		return definition.options
	}

	if (definition.deriveOptions) {
		return snippetsList && definition.deriveOptions(snippet, snippetsList)
	}

	if (definition.fetchAllOptions) {
		return definition.fetchAllOptions(restAPI)
	}

	if (definition.fetchPagedOptions) {
		const page = (currentPage[subject] ?? 0) + 1
		setCurrentPage(previous => ({ ...previous, [subject]: page }))
		return definition.fetchPagedOptions(restAPI, page)
	}

	return undefined
}

export const useConditionOptions = <S extends ConditionSubject>(currentSubject: S | undefined): UseConditionOptions<S> => {
	const restAPI = useAxios(REST_API_AXIOS_CONFIG)
	const { snippet, snippetsList } = useSnippetForm()
	const [currentPage, setCurrentPage] = useState<Partial<Record<ConditionSubject, number>>>({})
	const [optionsCache, setOptionsCache] = useState<ConditionSubjectOptions>({})
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<SelectGroups<ConditionSubjects[S]> | undefined>(undefined)
	const [loadingOptions, setLoadingOptions] = useState(false)

	useEffect(() => {
		if (objectOptions === undefined && currentSubject !== undefined && !loadingOptions) {
			const resolvedOptions = resolveOptions(
				{ currentSubject, optionsCache, snippet, snippetsList, currentPage, setCurrentPage, restAPI })

			if (resolvedOptions instanceof Promise) {
				setLoadingOptions(true)

				resolvedOptions.then(options => {
					setObjectOptions(options)
					setLoadedSubject(currentSubject)
					setOptionsCache(previous => ({ ...previous, [currentSubject]: options }))
				}).catch(handleUnknownError).finally(() => {
					setLoadingOptions(false)
				})
			} else if (resolvedOptions !== undefined) {
				setObjectOptions(resolvedOptions)
				setLoadedSubject(currentSubject)
			}
		}
	}, [currentPage, currentSubject, loadingOptions, objectOptions, optionsCache, restAPI, snippet, snippetsList])

	const loadMoreOptions = useCallback(() => {
		if (currentSubject !== undefined && !loadingOptions) {
			const { subject, definition } = findSubjectDefinition(currentSubject)

			if (definition.fetchPagedOptions && -1 !== currentPage[subject]) {
				const newPage = (currentPage[subject] ?? 0) + 1
				setLoadingOptions(true)

				definition.fetchPagedOptions(restAPI, newPage).then(options => {
					setCurrentPage(previous => ({ ...previous, [subject]: newPage }))
					setLoadingOptions(false)
					setObjectOptions(previous => [...previous ?? [], ...options])
					setOptionsCache(previous => ({
						...previous,
						[subject]: [...previous[subject] ?? [], ...options]
					}))
				}).catch((error: unknown) => {
					console.error(error)
					setCurrentPage(previous => ({ ...previous, [subject]: -1 }))
					setLoadingOptions(false)
				})
			}
		}
	}, [currentPage, currentSubject, loadingOptions, restAPI])

	const clearObjectOptions = useCallback(() => {
		setLoadedSubject(undefined)
		setObjectOptions(undefined)
	}, [])

	return { clearObjectOptions, loadMoreOptions, loadedSubject, objectOptions }
}
