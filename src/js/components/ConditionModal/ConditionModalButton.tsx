import React from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { isCondition } from '../../utils/snippets/snippets'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import type { Dispatch, SetStateAction } from 'react'

export interface ConditionModalButtonProps {
	setIsModalOpen: Dispatch<SetStateAction<boolean>>
}

export const ConditionModalButton: React.FC<ConditionModalButtonProps> = ({ setIsModalOpen }) => {
	const { snippet } = useSnippetForm()

	const hasCondition = 0 !== snippet.conditionId

	return (
		<div className={classnames('conditions-editor-open', hasCondition ? 'has-condition' : 'no-condition')}>
			{isCondition(snippet) ? null
				: <>
					<h3>{__('Conditions', 'code-snippets')}</h3>

					<Button
						large
						primary={hasCondition}
						onClick={() => setIsModalOpen(true)}
					>
						<span className="dashicons dashicons-randomize"></span>
						{hasCondition
							? __('Conditions', 'code-snippets')
							: __('Set Conditions', 'code-snippets')}
						<span className="badge">{__('beta', 'code-snippets')}</span>
					</Button>
				</>}
		</div>
	)
}
