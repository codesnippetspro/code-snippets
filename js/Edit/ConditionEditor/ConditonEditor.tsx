import React from 'react'
import { __ } from '@wordpress/i18n'
import { SnippetInputProps } from '../../types/SnippetInputProps'
import { ConditionGroup } from './ConditionGroup'

export const ConditionEditor: React.FC<SnippetInputProps> = ({ ...inputProps }) =>
	<div id="snippet_conditions" className="snippet-condition-editor">
		<ConditionGroup
			group="AND"
			heading={__('AND Conditions', 'code-snippets')}
			insertLabel={__('Add AND condition', 'code-snippets')}
			description={__('All conditions in this group must be true in order for the snippet to run.', 'code-snippets')}
			{...inputProps}
		/>

		<ConditionGroup
			group="OR"
			heading={__('OR Conditions', 'code-snippets')}
			insertLabel={__('Add OR condition', 'code-snippets')}
			description={__('At least one condition in this group must be true in order for the snippet to run.', 'code-snippets')}
			{...inputProps}
		/>
	</div>
