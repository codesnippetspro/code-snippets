import { useEffect, useState } from 'react'
import { type Importer, useImportersAPI } from '../../../../hooks/useImportersAPI'
import { fetchQueryParam, updateQueryParam } from '../../../../utils/urls'

const IMPORTER_QUERY_PARAM = 'from'

export const useImporterSelection = () => {
	const [importers, setImporters] = useState<Importer[]>([])
	const [selectedImporter, setSelectedImporter] = useState<string>(() => fetchQueryParam(IMPORTER_QUERY_PARAM) ?? '')
	const [isLoading, setIsLoading] = useState(true)
	const [error, setError] = useState<string | null>(null)
	const [tagValue, setTagValue] = useState<string>(() => `imported-${selectedImporter}`)

	const importersAPI = useImportersAPI()

	useEffect(() => {
		const fetchImporters = async () => {
			try {
				const response = await importersAPI.fetchAll()
				setImporters(response.data)
			} catch (error) {
				setError(error instanceof Error ? error.message : 'Unknown error')
			} finally {
				setIsLoading(false)
			}
		}

		void fetchImporters()
	}, [importersAPI])

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
