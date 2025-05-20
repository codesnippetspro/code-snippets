import { useCallback, useEffect, useState } from 'react'
import { CONDITION_SUBJECTS } from '../utils/conditions/subjects'
import { REST_API_AXIOS_CONFIG } from '../utils/restAPI'
import { useAxios } from './useAxios'
import { useSnippetForm } from './useSnippetForm'
import type { AxiosAPI } from './useAxios'
import type { Dispatch, SetStateAction } from 'react'
import type { ConditionSubjectDefinitions } from '../types/ConditionSubjectDefinitions'
import type { ConditionSubject, ConditionSubjects } from '../types/ConditionSubject'
import type { SelectGroups } from '../types/SelectOption'

type ConditionSubjectOptions = { [S in ConditionSubject]?: SelectGroups<ConditionSubjects[S]> }

interface SubjectWithDefinition<S extends ConditionSubject> {
	subject: S
	definition: ConditionSubjectDefinitions<ConditionSubjects>[S]
}

const findSubjectDefinition = <S extends ConditionSubject>(selectedSubject: S): SubjectWithDefinition<S> => {
	const subject = (<S | undefined> CONDITION_SUBJECTS[selectedSubject].useSubjectOptions) ?? selectedSubject
	const definition = CONDITION_SUBJECTS[subject]
	return { subject, definition }
}

const usePagedConditionOptions = <S extends ConditionSubject>(
	restAPI: AxiosAPI,
	selectedSubject: S | undefined,
	setOptionsCache: Dispatch<SetStateAction<ConditionSubjectOptions>>,
	handleOptionsLoaded: (setOptions: SetStateAction<SelectGroups<ConditionSubjects[S]> | undefined>) => void
) => {
	const [currentPage, setCurrentPage] = useState<Partial<Record<ConditionSubject, number>>>({})
	const [loadingOptions, setLoadingOptions] = useState(false)

	const loadPagedOptions = useCallback(({ subject, definition }: SubjectWithDefinition<S>) => {
		if (definition.fetchPagedOptions && !loadingOptions) {
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

			if (definition.fetchPagedOptions && -1 !== currentPage[subject]) {
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
}

export const useConditionOptions = <S extends ConditionSubject>(selectedSubject: S | undefined): UseConditionOptions<S> => {
	const restAPI = useAxios(REST_API_AXIOS_CONFIG)
	const { snippet, snippetsList } = useSnippetForm()
	const [optionsCache, setOptionsCache] = useState<ConditionSubjectOptions>({})
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<SelectGroups<ConditionSubjects[S]> | undefined>(undefined)
	const [loadingOptions, setLoadingOptions] = useState(false)

	const handleOptionsLoaded = useCallback((options: SetStateAction<SelectGroups<ConditionSubjects[S]> | undefined>) => {
		setLoadedSubject(selectedSubject)
		setObjectOptions(options)
	}, [selectedSubject])

	const { loadPagedOptions, loadMoreOptions } = usePagedConditionOptions(restAPI, selectedSubject, setOptionsCache, handleOptionsLoaded)

	const loadAllOptions = useCallback(({ subject, definition }: SubjectWithDefinition<S>) => {
		if (definition.fetchAllOptions && !loadingOptions) {
			setLoadingOptions(true)

			definition.fetchAllOptions(restAPI)
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
	}, [handleOptionsLoaded, loadingOptions, restAPI])

	useEffect(() => {
		if (objectOptions === undefined && selectedSubject !== undefined) {
			const { subject, definition } = findSubjectDefinition(selectedSubject)

			if (optionsCache[subject]) {
				handleOptionsLoaded(optionsCache[subject])
			} else if (definition.options) {
				handleOptionsLoaded(definition.options)
			} else if (definition.deriveOptions && snippetsList) {
				handleOptionsLoaded(definition.deriveOptions(snippet, snippetsList))
			} else if (definition.fetchAllOptions) {
				loadAllOptions({ subject, definition })
			} else if (definition.fetchPagedOptions) {
				loadPagedOptions({ subject, definition })
			}
		}
	}, [handleOptionsLoaded, loadAllOptions, loadPagedOptions, objectOptions, optionsCache, selectedSubject, snippet, snippetsList])

	const clearObjectOptions = useCallback(() => {
		setLoadedSubject(undefined)
		setObjectOptions(undefined)
	}, [])

	return { clearObjectOptions, loadMoreOptions, loadedSubject, objectOptions }
}
