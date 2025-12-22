import { useState, useEffect } from 'react'
import { useImportersAPI, type Importer } from '../../../../hooks/useImportersAPI'

export const useImporterSelection = () => {
	const [importers, setImporters] = useState<Importer[]>([])
	const [selectedImporter, setSelectedImporter] = useState<string>('')
	const [isLoading, setIsLoading] = useState(true)
	const [error, setError] = useState<string | null>(null)
	const [tagValue, setTagValue] = useState<string>('')
	
	const importersAPI = useImportersAPI()

	useEffect(() => {
		const fetchImporters = async () => {
			try {
				const response = await importersAPI.fetchAll()
				setImporters(response.data)
			} catch (err) {
				setError(err instanceof Error ? err.message : 'Unknown error')
			} finally {
				setIsLoading(false)
			}
		}

		fetchImporters()
	}, [importersAPI])

	const handleImporterChange = (newImporter: string) => {
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
