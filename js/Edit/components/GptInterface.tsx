import { __, _x } from '@wordpress/i18n'
import React from 'react'
import { CodeEditorInstance } from '../../types/WordPressCodeEditor'
import { ActionButton } from '../../common/ActionButton'
import { Snippet } from '../../types/Snippet'


interface GpInterfaceProps {
	codeEditorInstance?: CodeEditorInstance
	snippet: Snippet
	isWorking: boolean
}

export const GpInterface: React.FC<GpInterfaceProps> = ({ codeEditorInstance, snippet, isWorking }) =>
	<>
		<ActionButton
			primary
			name="cs_gpt_prompt"
			text={__('Prompt', 'code-snippets')}
			onClick={event => {
				event.preventDefault()
			}
			}
			disabled={isWorking}
		/>
		<ActionButton
			primary
			name="cs_gpt_explain"
			text={__('Explain', 'code-snippets')}
			disabled={isWorking}
		/>
	</>

