import React from 'react'
import { BaseControl } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { Tooltip } from '../common/Tooltip'
import { ConditionEditor } from '../ConditionEditor'
import { CancelButton } from './CancelButton'
import type { FormEventHandler } from 'react'

export interface CreateConditionFormProps {
	closeModal: VoidFunction
}

export const CreateConditionForm: React.FC<CreateConditionFormProps> = ({ closeModal }) => {
	const handleSubmit: FormEventHandler<HTMLFormElement> = event => {
		event.preventDefault()
	}

	return (
		<form className="modal-form" onSubmit={handleSubmit}>
			<div className="modal-content">
				<BaseControl label={__('Set Conditions', 'code-snippets')}>
					<ConditionEditor />
				</BaseControl>
			</div>

			<div className="modal-footer">
				<CancelButton closeModal={closeModal} />

				<div>
					<Tooltip>
						{__('Give a name to this condition so you can reuse it on other snippets. Leave blank for an auto-generated name.', 'code-snippets')}
					</Tooltip>

					<input
						type="text"
						className="condition-title-input"
						placeholder={__('Add a title for this condition', 'code-snippets')}
					/>

					<button
						className="button button-primary button-large"
					>
						{__('Save and Apply', 'code-snippets')}
					</button>
				</div>
			</div>
		</form>
	)
}
