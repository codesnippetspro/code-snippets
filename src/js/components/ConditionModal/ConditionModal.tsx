import { Modal } from '@wordpress/components'
import React, { useMemo, useState } from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../common/Button'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { ApplyConditionForm } from './ApplyConditionForm'
import { EditConditionForm } from './EditConditionForm'

interface ModalSplashProps {
	setIsCreating: (isCreating: boolean) => void
}

const ModalSplash: React.FC<ModalSplashProps> = ({ setIsCreating }) =>
	<div className="modal-splash">
		<p>
			{`${__('Set conditions for running the snippet.', 'code-snippets')} `}
			<a href={'#' /* TODO */}>{__('Learn more.', 'code-snippets')}</a>
		</p>
		<p className="modal-splash-buttons">
			<Button primary onClick={() => setIsCreating(true)}>
				{__('Create new condition', 'code-snippets')}
			</Button>
			<Button primary onClick={() => setIsCreating(false)}>
				{__('Select existing condition', 'code-snippets')}
			</Button>
		</p>
	</div>

interface ModalInnerProps extends ConditionModalProps {
	isCreating: boolean
	setIsCreating: (isCreating?: boolean) => void
}

const ModalInner: React.FC<ModalInnerProps> = ({ isCreating, setIsCreating, closeModal }) => {
	const { snippetsList } = useSnippetForm()
	const [selectedConditionId, setSelectedConditionId] = useState<number>(0)

	const selectedCondition = useMemo(() =>
			snippetsList?.find(snippet => snippet.id === selectedConditionId),
		[snippetsList, selectedConditionId]
	)

	return isCreating
		? <EditConditionForm
			condition={selectedCondition}
			onClose={() => setIsCreating(false)}
		/>
		: <ApplyConditionForm
			onClose={closeModal}
			onEdit={() => setIsCreating(true)}
			selectedCondition={selectedCondition}
			setSelectedCondition={id => {
				setSelectedConditionId(id ?? 0)
			}}
		/>
}

export interface ConditionModalProps {
	closeModal: VoidFunction
}

export const ConditionModal: React.FC<ConditionModalProps> = ({ closeModal }) => {
	const { snippet } = useSnippetForm()
	const [isCreating, setIsCreating] = useState<boolean | undefined>(() => snippet.conditionId ? false : undefined)

	return (
		<Modal
			size="large"
			title="Snippet Conditions"
			className="code-snippets-condition-modal"
			onRequestClose={closeModal}
		>
			{isCreating === undefined
				? <ModalSplash
					setIsCreating={setIsCreating}
				/>
				: <ModalInner
					closeModal={closeModal}
					isCreating={isCreating}
					setIsCreating={setIsCreating}
				/>}
		</Modal>
	)
}
