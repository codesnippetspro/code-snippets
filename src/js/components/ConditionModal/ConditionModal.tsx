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

interface ModalInnerProps {
	isCreating: boolean
	closeModal: VoidFunction
	setIsCreating: (isCreating?: boolean) => void
}

const ModalInner: React.FC<ModalInnerProps> = ({ isCreating, setIsCreating, closeModal }) => {
	const { snippet, snippetsList } = useSnippetForm()
	const [selectedConditionId, setSelectedConditionId] = useState<number>(snippet.conditionId)

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
	isOpen: boolean
	setIsOpen: (isOpen: boolean) => void
}

export const ConditionModal: React.FC<ConditionModalProps> = ({ isOpen, setIsOpen }) => {
	const { snippet } = useSnippetForm()
	const [isCreating, setIsCreating] = useState<boolean | undefined>(() => snippet.conditionId ? false : undefined)

	return isOpen
		? <Modal
			size="large"
			title="Snippet Conditions"
			className="code-snippets-condition-modal"
			onRequestClose={() => setIsOpen(false)}
		>
			{isCreating === undefined
				? <ModalSplash
					setIsCreating={setIsCreating}
				/>
				: <ModalInner
					closeModal={() => setIsOpen(false)}
					isCreating={isCreating}
					setIsCreating={setIsCreating}
				/>}
		</Modal>
		: null
}
