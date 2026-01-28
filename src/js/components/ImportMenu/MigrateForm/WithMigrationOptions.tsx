import React, { useCallback, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { fetchQueryParam, updateQueryParam } from '../../../utils/urls'
import type { PropsWithChildren} from 'react'

const IMPORTER_QUERY_PARAM = 'from'


export interface MigrationOptionsContext {
	autoAddTags: boolean
	selectedImporter: string
	setAutoAddTags: (value: boolean) => void
	setSelectedImporter: (newImporter: string) => void
	setTagValue: (value: string) => void
	tagValue: string
}

export const [MigrationOptionsContext, useMigrationOptions] = createContextHook<MigrationOptionsContext>('useMigrationOptions')

export const WithMigrationOptions: React.FC<PropsWithChildren> = ({ children }) => {
	const [selectedImporter, setSelectedImporterValue] = useState<string>(() => fetchQueryParam(IMPORTER_QUERY_PARAM) ?? '')
	const [tagValue, setTagValue] = useState<string>(() => `imported-${selectedImporter}`)
	const [autoAddTags, setAutoAddTags] = useState(false)

	const setSelectedImporter = useCallback((newImporter: string) => {
		updateQueryParam(IMPORTER_QUERY_PARAM, newImporter)
		setTagValue(`imported-${newImporter}`)
		setSelectedImporterValue(newImporter)
	}, [])

	const value: MigrationOptionsContext = {
		autoAddTags,
		selectedImporter,
		setSelectedImporter,
		setAutoAddTags,
		setTagValue,
		tagValue,
	}

	return <MigrationOptionsContext.Provider value={value}>{children}</MigrationOptionsContext.Provider>
}
