import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { isCondition } from '../../utils/snippets/snippets'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { ConditionModal } from './ConditionModal'

export const ConditionModalButton: React.FC = () => {
	const { snippet, setSnippet } = useSnippetForm()
	const [isModalOpen, setIsModalOpen] = useState(true) // TODO: false)

	const hasCondition = 0 !== snippet.conditionId

	const handleClose = () => {
		setSnippet(previous => ({ ...previous, conditions: {} }))
		setIsModalOpen(false)
	}

	return (
		<>
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

			{isModalOpen ? <ConditionModal closeModal={handleClose} /> : null}
		</>
	)
}
