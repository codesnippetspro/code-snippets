import React, { useCallback, useState } from 'react'
import { createContextHook } from '../../../utils/bootstrap'
import { fetchQueryParam, updateQueryParams } from '../../../utils/urls'
import type { PropsWithChildren } from 'react'

const IMPORTER_QUERY_PARAM = 'from'

export interface MigrationOptionsContext {
	autoAddTags: boolean
	selectedImporter: string
	setAutoAddTags: (value: boolean) => void
	setSelectedImporter: (newImporter: string) => void
	setTagValue: (value: string) => void
	tagValue: string
}

const [Context, useMigrationOptions] = createContextHook<MigrationOptionsContext>('useMigrationOptions')

export const WithMigrationOptions: React.FC<PropsWithChildren> = ({ children }) => {
	const [selectedImporter, setSelectedImporterValue] = useState<string>(() => fetchQueryParam(IMPORTER_QUERY_PARAM) ?? '')
	const [autoAddTags, setAutoAddTags] = useState(false)
	const [tagValue, setTagValue] = useState<string>(() => `imported-${selectedImporter}`)

	const setSelectedImporter = useCallback((newImporter: string) => {
		updateQueryParams({ [IMPORTER_QUERY_PARAM]: newImporter })
		setTagValue(`imported-${newImporter}`)
		setSelectedImporterValue(newImporter)
	}, [])

	const value: MigrationOptionsContext = {
		autoAddTags,
		selectedImporter,
		setSelectedImporter,
		setAutoAddTags,
		setTagValue,
		tagValue
	}

	return <Context.Provider value={value}>{children}</Context.Provider>
}

export { useMigrationOptions }
