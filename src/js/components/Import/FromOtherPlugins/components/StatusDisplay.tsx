import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../shared'

interface StatusDisplayProps {
	type: 'error' | 'success'
	title: string
	message: string
	showSnippetsLink?: boolean
}

export const StatusDisplay: React.FC<StatusDisplayProps> = ({
	type,
	title,
	message,
	showSnippetsLink = false
}) => {
	const isError = type === 'error'
	
	return (
		<ImportCard variant="controls" style={{ display: 'flex', alignItems: 'flex-start', gap: '12px', marginBottom: '20px' }}>
			<div style={{ 
				backgroundColor: isError ? '#d63638' : '#00a32a', 
				borderRadius: '50%', 
				width: '24px', 
				height: '24px', 
				display: 'flex', 
				alignItems: 'center', 
				justifyContent: 'center',
				flexShrink: 0,
				marginTop: '2px'
			}}>
				<span style={{ color: 'white', fontSize: '14px', fontWeight: 'bold' }}>
					{isError ? '✕' : '✓'}
				</span>
			</div>
			<div>
				<h3 style={{ margin: '0 0 8px 0', fontSize: '16px', fontWeight: '600' }}>
					{title}
				</h3>
				<p style={{ margin: '0', color: '#666' }}>
					{message}
					{showSnippetsLink && (
						<>
							{' '}
							<a href="admin.php?page=snippets" style={{ color: '#2271b1', textDecoration: 'none' }}>
								{__('Code Snippets Library', 'code-snippets')}
							</a>.
						</>
					)}
				</p>
			</div>
		</ImportCard>
	)
}
