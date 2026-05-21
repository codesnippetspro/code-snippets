import React, { useMemo } from 'react'
import { __ } from '@wordpress/i18n'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import { updateQueryParam } from '../../../utils/urls'
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
	category: number
	type: number
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
	const { availableFilters, filters, setFilters } = useCloudSearch()

	const categoryOptions: [number, string][] = useMemo(
		() => (availableFilters.categories ?? []).map(c => [c.id, c.name]),
		[availableFilters.categories]
	)

	const typeOptions: [number, string][] = useMemo(
		() => (availableFilters.types ?? []).map(t => [t.id, t.name]),
		[availableFilters.types]
	)

	const statusOptions: [number, string][] = useMemo(
		() => (availableFilters.statuses ?? []).map(s => [s.id, s.name]),
		[availableFilters.statuses]
	)

	return (
		<>
			<SearchFilter
				filter="category"
				filters={filters}
				setFilters={setFilters}
				options={categoryOptions}
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
				options={statusOptions}
				label={__('Snippet Status', 'code-snippets')}
				allOptionLabel={__('All Snippet Statuses', 'code-snippets')}
			/>
		</>
	)
}
