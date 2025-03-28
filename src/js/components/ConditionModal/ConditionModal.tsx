import { Modal } from '@wordpress/components'
import React, { useState } from 'react'
import classnames from 'classnames'
import { __ } from '@wordpress/i18n'
import { createInitialConditionRules } from '../../utils/conditions'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { ApplyConditionForm } from './ApplyConditionForm'
import { CreateConditionForm } from './CreateConditionForm'

const VIEWS = [
	__('Create new condition', 'code-snippets'),
	__('Apply existing condition', 'code-snippets')
]

interface ModalInnerProps {
	closeModal: VoidFunction
}

const ModalInner: React.FC<ModalInnerProps> = ({ closeModal }) => {
	const { snippet } = useSnippetForm()
	const [currentView, setCurrentView] = useState(() => snippet.conditional ? 1 : 0)

	return <>
		<nav className="modal-nav">
			{VIEWS.map((label, index) =>
				<label key={index}>
					<input
						type="radio"
						name="modal-view"
						checked={currentView === index}
						onChange={() => setCurrentView(index)}
					/>
					{label}
				</label>)}
		</nav>

		{0 === currentView
			? <CreateConditionForm closeModal={closeModal} />
			: <ApplyConditionForm closeModal={closeModal} />}
	</>
}

export const ConditionsModalButton: React.FC = () => {
	const { snippet, setSnippet } = useSnippetForm()
	const [isModalOpen, setIsModalOpen] = useState(true) // TODO: Change to false

	const hasCondition = 0 !== snippet.conditional

	const handleClose = () => {
		setSnippet(previous => ({ ...previous, conditions: createInitialConditionRules() }))
		setIsModalOpen(false)
	}

	return <>
		<div className={classnames('conditions-editor-open', hasCondition ? 'has-condition' : 'no-condition')}>
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
		</div>

		{isModalOpen
			? <Modal
				title="Snippet Conditions"
				size="large"
				className="code-snippets-condition-modal"
				onRequestClose={handleClose}
			>
				<ModalInner closeModal={handleClose} />
			</Modal>
			: null}
	</>
}
