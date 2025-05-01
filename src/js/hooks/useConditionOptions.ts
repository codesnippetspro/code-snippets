import { useCallback, useEffect, useState } from 'react'
import { CONDITION_SUBJECTS } from '../utils/conditions/subjects'
import { handleUnknownError } from '../utils/errors'
import { REST_API_AXIOS_CONFIG } from '../utils/restAPI'
import { useAxios } from './useAxios'
import { useSnippetForm } from './useSnippetForm'
import type { ConditionSubject, ConditionSubjects } from '../types/ConditionSubject'
import type { SelectGroups } from '../types/SelectOption'

export interface UseConditionOptions<S extends ConditionSubject> {
	loadedSubject: ConditionSubject | undefined
	objectOptions: SelectGroups<ConditionSubjects[S]> | undefined
	clearObjectOptions: VoidFunction
}

export const useConditionOptions = <S extends ConditionSubject>(subject: S | undefined): UseConditionOptions<S> => {
	const restAPI = useAxios(REST_API_AXIOS_CONFIG)
	const { snippet, snippetsList } = useSnippetForm()
	const [optionsCache, setOptionsCache] = useState<{ [S in ConditionSubject]?: SelectGroups<ConditionSubjects[S]> }>({})
	const [loadedSubject, setLoadedSubject] = useState<ConditionSubject>()
	const [objectOptions, setObjectOptions] = useState<SelectGroups<ConditionSubjects[S]> | undefined>(undefined)

	useEffect(() => {
		if (objectOptions === undefined && subject !== undefined) {
			const definition = CONDITION_SUBJECTS[subject]

			if (optionsCache[subject]) {
				setLoadedSubject(subject)
				setObjectOptions(optionsCache[subject])
			} else if (definition.options) {
				setLoadedSubject(subject)
				setObjectOptions(definition.options)
			} else if (definition.deriveOptions && snippetsList !== undefined) {
				setLoadedSubject(subject)
				setObjectOptions(definition.deriveOptions(snippet, snippetsList))
			} else if (definition.fetchOptions) {
				setLoadedSubject(undefined)

				definition.fetchOptions(restAPI)
					.then(options => {
						setObjectOptions(options)
						setLoadedSubject(subject)
						setOptionsCache(previous => ({ ...previous, [subject]: options }))
					})
					.catch(handleUnknownError)
			}
		}
	}, [subject, objectOptions, optionsCache, restAPI, snippet, snippetsList])

	const clearObjectOptions = useCallback(() => {
		setLoadedSubject(undefined)
		setObjectOptions(undefined)
	}, [])

	return { clearObjectOptions, loadedSubject, objectOptions }
}
