import { __ } from '@wordpress/i18n'
import React from 'react'

export interface CancelButtonProps {
	closeModal: VoidFunction
}

export const CancelButton: React.FC<CancelButtonProps> = ({ closeModal }) =>
	<button
		type="button"
		className="button cancel-button"
		onClick={event => {
			event.preventDefault()
			closeModal()
		}}
	>
		{__('Cancel', 'code-snippets')}
	</button>
