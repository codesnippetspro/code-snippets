import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { isLicensed } from '../../utils/screen'
import { isCondition } from '../../utils/snippets/snippets'
import { Badge } from '../common/Badge'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import type { Dispatch, SetStateAction } from 'react'

export interface ConditionModalButtonProps {
	setIsDialogOpen: Dispatch<SetStateAction<boolean>>
}

export const ConditionModalButton: React.FC<ConditionModalButtonProps> = ({ setIsDialogOpen }) => {
	const { snippet, isReadOnly } = useSnippetForm()

	const hasCondition = 0 !== snippet.conditionId

	return (
		<div className={classnames('conditions-editor-open block-form-field', hasCondition ? 'has-condition' : 'no-condition')}>
			{isCondition(snippet) ? null
				: <>
					<h4>
						{__('Conditions', 'code-snippets')}
						<Badge name="beta" small>{__('beta', 'code-snippets')}</Badge>
						{!isLicensed() && <Badge name="pro" small>{__('Pro', 'code-snippets')}</Badge>}
					</h4>

					<Button large disabled={isReadOnly} onClick={() => setIsDialogOpen(true)}>
						<Badge name="cond" small />
						{hasCondition
							? __('Edit Conditions', 'code-snippets')
							: __('Add Conditions', 'code-snippets')}
					</Button>
				</>}
		</div>
	)
}
