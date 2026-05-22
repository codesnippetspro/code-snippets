import { __ } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import { useCloudSearch } from './WithCloudSearchContext'
import type { AvailableCloudFilters , CloudSearchParams} from './WithCloudSearchContext'

export const STATUS_LABELS: Record<CloudStatus, string> = {
	[CloudStatus.Public]: __('Public', 'code-snippets'),
	[CloudStatus.Private]: __('Private', 'code-snippets'),
	[CloudStatus.Unverified]: __('Unverified', 'code-snippets'),
	[CloudStatus.AI_Verified]: __('AI Verified', 'code-snippets'),
	[CloudStatus.Pro_Verified]: __('Pro Verified', 'code-snippets')
}

interface FilterInfo {
	paramName: keyof CloudSearchParams
	filterName: keyof AvailableCloudFilters
	label: string
	allOptionLabel: string

}

const FILTERS: FilterInfo[] = [
	{
		paramName: 'category',
		filterName: 'categories',
		label: __('Snippet Category', 'code-snippets'),
		allOptionLabel: __('All Categories', 'code-snippets')
	},
	{
		paramName: 'type',
		filterName: 'types',
		label: __('Snippet Type', 'code-snippets'),
		allOptionLabel: __('All Types', 'code-snippets')
	},
	{
		paramName: 'status',
		filterName: 'statuses',
		label: __('Snippet Status', 'code-snippets'),
		allOptionLabel: __('All Statuses', 'code-snippets')
	}
]

const normaliseFilterValue = (filter: keyof CloudSearchParams, params: CloudSearchParams, value: string) => {
	if ('' === value) {
		return undefined
	}

	if ('number' === typeof params[filter]) {
		const numberValue = Number(value)
		return isNaN(numberValue) ? undefined : numberValue
	}

	return value
}

export const SearchFilters = () => {
	const { availableFilters, searchParams, updateSearchParams } = useCloudSearch()

	return FILTERS.map(({ label, allOptionLabel, filterName, paramName }) =>
		availableFilters[filterName] && 0 < availableFilters[filterName].length
			? <Fragment key={filterName}>
				<label htmlFor={`cloud-search-${paramName}`} className="screen-reader-text">
					{label}
				</label>

				<select
					id={`cloud-search-${filterName}`}
					className="cloud-search-category-filter"
					value={searchParams[paramName]}
					onChange={event =>
						updateSearchParams({ [paramName]: normaliseFilterValue(paramName, searchParams, event.target.value) })}
				>
					<option value="">{allOptionLabel}</option>
					{availableFilters[filterName].map(filterOption =>
						<option key={filterOption.id} value={filterOption.id}>{filterOption.name}</option>)}
				</select>
			</Fragment>
			: null)
}
