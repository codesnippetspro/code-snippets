import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button, Flex, Modal } from '@wordpress/components'
import type { ReactNode } from 'react'

export interface ConfirmDialogProps {
	open?: boolean
	title: string
	onConfirm?: VoidFunction
	onCancel: VoidFunction
	confirmLabel?: string
	cancelLabel?: string
	children?: ReactNode,
	confirmButtonClassName?: string
}

export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
	open,
	title,
	onConfirm,
	onCancel,
	children,
	confirmLabel = __('OK', 'code-snippets'),
	cancelLabel = __('Cancel', 'code-snippets'),
	confirmButtonClassName
}) =>
	open
		? <Modal
			title={title}
			onRequestClose={onCancel}
			closeButtonLabel={cancelLabel}
			isDismissible={true}
			onKeyDown={event => {
				if ('Enter' === event.key) {
					onConfirm?.()
				}
			}}
		>
			{children}
			<Flex direction="row" justify="flex-end">
				<Button variant="tertiary" onClick={onCancel}>
					{cancelLabel}
				</Button>
				<Button variant="primary" onClick={onConfirm} className={confirmButtonClassName}>
					{confirmLabel}
				</Button>
			</Flex>
		</Modal>
		: null
