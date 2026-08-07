import { __ } from '@wordpress/i18n'
import React, { useId } from 'react'
import Select from 'react-select'
import { useSnippetForm } from '../WithSnippetFormContext'
import { SNIPPET_TYPE_SCOPES } from '../../../../types/Snippet'
import { SNIPPET_SCOPE_DESCRIPTIONS, SNIPPET_SCOPE_ICONS, getSnippetType, isCondition } from '../../../../utils/snippets/snippets'
import type { SnippetCodeScope } from '../../../../types/Snippet'
import type { SelectOption } from '../../../../types/SelectOption'

export const SnippetLocationInput: React.FC = () => {
	const { snippet, setSnippet, isReadOnly } = useSnippetForm()
	const locationId = useId()

	const options: SelectOption<SnippetCodeScope>[] = SNIPPET_TYPE_SCOPES[getSnippetType(snippet)]
		.filter(scope => 'condition' !== scope)
		.map(scope => ({
			key: scope,
			value: scope,
			label: SNIPPET_SCOPE_DESCRIPTIONS[scope]
		}))

	return isCondition(snippet)
		? null
		: <div className="block-form-field">
			<label htmlFor={locationId}>
				{__('Location', 'code-snippets')}
			</label>

			<Select
				inputId={locationId}
				className="code-snippets-select code-snippets-select-location"
				options={options}
				isSearchable={false}
				isDisabled={isReadOnly}
				styles={{
					menu: provided => ({ ...provided, zIndex: 9999 }),
					input: provided => ({ ...provided, ':focus': { boxShadow: 'none' } })
				}}
				value={options.find(option => option.value === snippet.scope)}
				formatOptionLabel={({ label, value }) =>
					<>
						<span className={`dashicons dashicons-${SNIPPET_SCOPE_ICONS[value]}`} aria-hidden="true"></span>{` ${label}`}
					</>
				}
				onChange={option =>
					option?.value && setSnippet(previous => ({ ...previous, scope: option.value }))}
			/>
		</div>
}
