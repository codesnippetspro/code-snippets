import React, { useMemo } from 'react'
import { __ } from '@wordpress/i18n'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import { updateQueryParam } from '../../../utils/urls'
import { useCloudSearch } from './WithCloudSearchContext'
import { useCloudSearchFilters } from './WithCloudSearchFiltersContext'
import type { Dispatch, SetStateAction } from 'react'

export const STATUS_LABELS: Record<CloudStatus, string> = {
	[CloudStatus.Public]: __('Public', 'code-snippets'),
	[CloudStatus.Private]: __('Private', 'code-snippets'),
	[CloudStatus.Unverified]: __('Unverified', 'code-snippets'),
	[CloudStatus.AI_Verified]: __('AI Verified', 'code-snippets'),
	[CloudStatus.Pro_Verified]: __('Pro Verified', 'code-snippets')
}

export interface CloudSearchFilters {
	tags: string
	status: number
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
			id="cloud-search-category"
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
	const { searchResults: snippets } = useCloudSearch()
	const { filters, setFilters } = useCloudSearchFilters()

	const options: { [K in keyof CloudSearchFilters]: [CloudSearchFilters[K], string][] } = useMemo(
		() => {
			const tags = new Set<string>()
			const statuses = new Set<CloudStatus>()

			snippets?.forEach(snippet => {
				snippet.tags.forEach(tag => tags.add(tag))
				statuses.add(snippet.status)
			})

			return {
				tags: Array.from(tags).sort().map(tag => [tag, tag]),
				status: Array.from(statuses).sort().map(status => [status, STATUS_LABELS[status]])
			}
		},
		[snippets])

	return (
		<>
			<SearchFilter
				filter="tags"
				filters={filters}
				setFilters={setFilters}
				options={options.tags}
				label={__('Snippet Category', 'code-snippets')}
				allOptionLabel={__('All Categories', 'code-snippets')}
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
