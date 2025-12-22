import React from 'react'
import { __ } from '@wordpress/i18n'
import { ImportCard } from '../../shared'

interface ImportResult {
	success: boolean
	message: string
	imported?: number
	warnings?: string[]
}

interface ImportResultDisplayProps {
	result: ImportResult
}

export const ImportResultDisplay: React.FC<ImportResultDisplayProps> = ({ result }) => {
	return (
		<ImportCard>
			<div style={{ display: 'flex', alignItems: 'flex-start', gap: '12px' }}>
				<div style={{ 
					backgroundColor: result.success ? '#00a32a' : '#d63638', 
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
						{result.success ? '✓' : '✕'}
					</span>
				</div>
				<div style={{ flex: 1 }}>
					<h3 style={{ margin: '0 0 8px 0', fontSize: '16px', fontWeight: '600' }}>
						{result.success 
							? __('Import Successful!', 'code-snippets')
							: __('Import Failed', 'code-snippets')
						}
					</h3>
					<p style={{ margin: '0 0 8px 0', color: '#666' }}>
						{result.message}
					</p>
					
					{result.success && (
						<p style={{ margin: '0', color: '#666' }}>
							{__('Go to ', 'code-snippets')}
							<a href="admin.php?page=snippets" style={{ color: '#2271b1', textDecoration: 'none' }}>
								{__('All Snippets', 'code-snippets')}
							</a>
							{__(' to activate your imported snippets.', 'code-snippets')}
						</p>
					)}

					{result.warnings && result.warnings.length > 0 && (
						<div style={{ marginTop: '12px' }}>
							<h4 style={{ margin: '0 0 8px 0', fontSize: '14px', color: '#d63638' }}>
								{__('Warnings:', 'code-snippets')}
							</h4>
							<ul style={{ margin: '0', paddingLeft: '20px' }}>
								{result.warnings.map((warning, index) => (
									<li key={index} style={{ color: '#666', fontSize: '14px' }}>
										{warning}
									</li>
								))}
							</ul>
						</div>
					)}
				</div>
			</div>
		</ImportCard>
	)
}
