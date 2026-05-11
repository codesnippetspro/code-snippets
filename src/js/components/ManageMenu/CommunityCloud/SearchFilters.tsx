import React, { useEffect, useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import { updateQueryParam } from '../../../utils/urls'
import { useRestAPI } from '../../../hooks/useRestAPI'
import { REST_BASES } from '../../../utils/restAPI'
import { useCloudSearch } from './WithCloudSearchContext'
import type { Dispatch, SetStateAction } from 'react'

export const STATUS_LABELS: Record<CloudStatus, string> = {
	[CloudStatus.Public]: __('Public', 'code-snippets'),
	[CloudStatus.Private]: __('Private', 'code-snippets'),
	[CloudStatus.Unverified]: __('Unverified', 'code-snippets'),
	[CloudStatus.AI_Verified]: __('AI Verified', 'code-snippets'),
	[CloudStatus.Pro_Verified]: __('Pro Verified', 'code-snippets')
}

export interface CloudSearchFilters {
	category: string
	type: string
	status: number
}

interface CloudType {
	id: number
	name: string
	snippet_count: number
}

interface SearchFilterProps {
	label: string
	filter: keyof CloudSearchFilters
	filters: CloudSearchFilters
	setFilters: Dispatch<SetStateAction<CloudSearchFilters>>
	options: [string | number, string][]
	allOptionLabel: string
}

const SearchFilter: React.FC<SearchFilterProps> = ({ options, filter, filters, setFilters, label, allOptionLabel }) =>
	<>
		<label htmlFor={`cloud-search-${filter}`} className="screen-reader-text">
			{label}
		</label>

		<select
			id={`cloud-search-${filter}`}
			className="cloud-search-category-filter"
			value={filters[filter]}
			onChange={event => {
				setFilters(previous => ({
					...previous,
					[filter]: 'number' === typeof filters[filter]
						? Number(event.target.value)
						: event.target.value
				}))
				updateQueryParam(filter, event.target.value)
			}}
		>
			<option value="">{allOptionLabel}</option>
			{options.map(([value, label]) =>
				<option key={value} value={value}>{label}</option>)}
		</select>
	</>

export const SearchFilters = () => {
	const { searchResults: snippets, filters, setFilters } = useCloudSearch()
	const { api } = useRestAPI()

	const [cloudTypes, setCloudTypes] = useState<CloudType[]>([])

	useEffect(() => {
		api.get<CloudType[]>(`${REST_BASES.cloud}/types`)
			.then(setCloudTypes)
			.catch(() => setCloudTypes([]))
	}, [api])

	const options: { category: [string, string][], status: [number, string][] } = useMemo(
		() => {
			const categories = new Set<string>()
			const statuses = new Set<CloudStatus>()

			snippets?.forEach(snippet => {
				snippet.tags.forEach(tag => categories.add(tag))
				statuses.add(snippet.status)
			})

			return {
				category: Array.from(categories).sort().map(cat => [cat, cat]),
				status: Array.from(statuses).sort().map(status => [status, STATUS_LABELS[status]])
			}
		},
		[snippets])

	const typeOptions: [string, string][] = useMemo(
		() => cloudTypes.map(t => [t.name, t.name]),
		[cloudTypes])

	return (
		<>
			<SearchFilter
				filter="category"
				filters={filters}
				setFilters={setFilters}
				options={options.category}
				label={__('Snippet Category', 'code-snippets')}
				allOptionLabel={__('All Categories', 'code-snippets')}
			/>

			<SearchFilter
				filter="type"
				filters={filters}
				setFilters={setFilters}
				options={typeOptions}
				label={__('Snippet Type', 'code-snippets')}
				allOptionLabel={__('All Types', 'code-snippets')}
			/>

			<SearchFilter
				filter="status"
				filters={filters}
				setFilters={setFilters}
				options={options.status}
				label={__('Snippet Status', 'code-snippets')}
				allOptionLabel={__('All Snippet Statuses', 'code-snippets')}
			/>
		</>
	)
}
