import { __ } from '@wordpress/i18n'
import React, { Fragment } from 'react'
import { useCloudSearch } from './WithCloudSearchContext'
import type { AvailableCloudFilters, CloudSearchParams } from './WithCloudSearchContext'

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
	const { availableFilters, searchParams, doSearch } = useCloudSearch()

	const visibleFilters = FILTERS.filter(({ filterName }) =>
		availableFilters[filterName] && 0 < availableFilters[filterName].length)

	return 0 < visibleFilters.length
		? <div className="alignleft actions">
			{visibleFilters.map(({ label, allOptionLabel, filterName, paramName }) =>
				<Fragment key={filterName}>
					<label htmlFor={`cloud-search-${filterName}`} className="screen-reader-text">
						{label}
					</label>

					<select
						id={`cloud-search-${filterName}`}
						className="cloud-search-category-filter"
						value={searchParams[paramName]}
						onChange={event =>
							doSearch({ [paramName]: normaliseFilterValue(paramName, searchParams, event.target.value) })}
					>
						<option value="">{allOptionLabel}</option>
						{availableFilters[filterName]?.map(filterOption =>
							<option key={filterOption.id} value={filterOption.id}>{filterOption.name}</option>)}
					</select>
				</Fragment>)}
		</div>
		: null
}
