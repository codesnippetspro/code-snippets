import { Modal } from '@wordpress/components'
import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { useSnippetsList } from '../../hooks/useSnippetsList'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { ApplyConditionForm } from './ApplyConditionForm'
import { EditConditionForm } from './EditConditionForm'

enum ModalState {
	Initial,
	ApplyCondition,
	ApplyConditionAfterSave,
	CreateCondition
}

interface ModalSplashProps {
	setModelState: (state: ModalState) => void
}

const ModalSplash: React.FC<ModalSplashProps> = ({ setModelState }) =>
	<div className="modal-splash">
		<p>{__('Set conditions for running the snippet.', 'code-snippets')}</p>
		<p className="modal-splash-buttons">
			<Button primary onClick={() => setModelState(ModalState.CreateCondition)}>
				{__('Create new condition', 'code-snippets')}
			</Button>
			<Button primary onClick={() => setModelState(ModalState.ApplyCondition)}>
				{__('Select existing condition', 'code-snippets')}
			</Button>
		</p>
	</div>

interface ModalInnerProps {
	closeModal: VoidFunction
}

const ModalInner: React.FC<ModalInnerProps> = ({ closeModal }) => {
	const { snippet } = useSnippetForm()
	const { snippetsList } = useSnippetsList()
	const [selectedConditionId, setSelectedConditionId] = useState<number>(snippet.conditionId)
	const [modelState, setModelState] = useState(() => selectedConditionId ? ModalState.ApplyCondition : ModalState.Initial)

	const selectedCondition = useMemo(
		() => snippetsList?.find(snippet => snippet.id === selectedConditionId),
		[snippetsList, selectedConditionId]
	)

	switch (modelState) {
		default:
			return <ModalSplash setModelState={setModelState} />

		case ModalState.ApplyCondition:
		case ModalState.ApplyConditionAfterSave:
			return (
				<ApplyConditionForm
					onClose={closeModal}
					onEdit={() => setModelState(ModalState.CreateCondition)}
					selectedCondition={selectedCondition}
					showConfirmationNotice={modelState === ModalState.ApplyConditionAfterSave}
					dismissConfirmationNotice={() => setModelState(ModalState.ApplyCondition)}
					setSelectedCondition={id => {
						setSelectedConditionId(id ?? 0)
						setModelState(ModalState.ApplyCondition)
					}}
				/>
			)

		case ModalState.CreateCondition:
			return (
				<EditConditionForm
					condition={selectedCondition}
					setSelectedConditionId={setSelectedConditionId}
					onSave={() => setModelState(ModalState.ApplyConditionAfterSave)}
					onCancel={() => setModelState(ModalState.ApplyCondition)}
				/>
			)
	}
}

export interface ConditionModalProps {
	isOpen: boolean
	setIsOpen: (isOpen: boolean) => void
}

export const ConditionModal: React.FC<ConditionModalProps> = ({ isOpen, setIsOpen }) =>
	isOpen
		? <Modal
			size="large"
			title="Snippet Conditions"
			className="code-snippets-condition-modal"
			onRequestClose={() => setIsOpen(false)}
		>
			<ModalInner closeModal={() => setIsOpen(false)} />
		</Modal>
		: null
