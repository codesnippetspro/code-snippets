import { __ } from '@wordpress/i18n'
import React, { useEffect, useState } from 'react'
import { Button } from '../common/Button'
import { CopyToClipboardButton } from '../common/CopyToClipboardButton'

// Ensure ajaxurl is recognized
declare const ajaxurl: string

interface RegenerateResponse {
	success: boolean
	data: {
		token: string
		message?: string
	}
}

export const TokenSettings: React.FC = () => {
	const [token, setToken] = useState<string>('')
	const [nonce, setNonce] = useState<string>('')
	const [isLoading, setIsLoading] = useState<boolean>(false)

	useEffect(() => {
		const container = document.getElementById('code-snippets-token-settings')
		if (container) {
			setToken(container.dataset.token || '')
			setNonce(container.dataset.nonce || '')
		}
	}, [])

	const handleRegenerate = async () => {
		if (!confirm(__('Are you sure you want to regenerate the token? The old token will be invalid.', 'code-snippets'))) {
			return
		}

		setIsLoading(true)

		const formData = new FormData()
		formData.append('action', 'code_snippets_regenerate_token')
		formData.append('nonce', nonce)

		try {
			const response = await fetch(ajaxurl, {
				method: 'POST',
				body: formData
			})

			const result: RegenerateResponse = await response.json()

			if (result.success) {
				setToken(result.data.token)
			} else {
				alert(result.data.message || __('Failed to regenerate token.', 'code-snippets'))
			}
		} catch (error) {
			console.error('Error regenerating token:', error)
			alert(__('An error occurred while regenerating the token.', 'code-snippets'))
		} finally {
			setIsLoading(false)
		}
	}

	if (!token) {
		return null
	}

	return (
		<div className="code-snippets-token-wrapper" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
			<input
				type="text"
				readOnly
				value={token}
				className="regular-text"
				style={{ background: '#f0f0f1', color: '#666', cursor: 'default' }}
			/>
			<CopyToClipboardButton text={token} secondary small />
			<Button
				secondary
				small
				onClick={handleRegenerate}
				disabled={isLoading}
				className="button-plugin-token"
			>
				{isLoading ? __('Regenerating...', 'code-snippets') : __('Regenerate', 'code-snippets')}
			</Button>
		</div>
	)
}
