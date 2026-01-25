import { useEffect, useState } from 'react'
import { useRestAPI } from '../../../../hooks/useRestAPI'
import { REST_NAMESPACED, REST_SNIPPETS_BASE } from '../../../../utils/restAPI'
import { fetchQueryParam, updateQueryParam } from '../../../../utils/urls'
import type { Importer } from './useSnippetImport'

const IMPORTER_QUERY_PARAM = 'from'

export const useImporterSelection = () => {
	const [importers, setImporters] = useState<Importer[]>([])
	const [selectedImporter, setSelectedImporter] = useState<string>(() => fetchQueryParam(IMPORTER_QUERY_PARAM) ?? '')
	const [isLoading, setIsLoading] = useState(true)
	const [error, setError] = useState<string | null>(null)
	const [tagValue, setTagValue] = useState<string>(() => `imported-${selectedImporter}`)

	const { api } = useRestAPI()

	useEffect(() => {
		const fetchImporters = () =>
			api.get<Importer[]>(`${REST_NAMESPACED}1/importers`)
				.then(response => {
					setImporters(response)
				})
				.catch((error: unknown) => {
					setError(error instanceof Error ? error.message : 'Unknown error')
				})
				.finally(() => setIsLoading(false))

		void fetchImporters()
	}, [api])

	const handleImporterChange = (newImporter: string) => {
		updateQueryParam(IMPORTER_QUERY_PARAM, newImporter)
		setSelectedImporter(newImporter)
		setTagValue(`imported-${newImporter}`)
	}

	return {
		importers,
		selectedImporter,
		isLoading,
		error,
		tagValue,
		setTagValue,
		handleImporterChange
	}
}
