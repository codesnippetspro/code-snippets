import React from 'react'
import { Spinner } from '@wordpress/components'
import { isRTL } from '@wordpress/i18n'
import { useSnippetForm } from '../../hooks/useSnippetForm'
import { isNetworkAdmin } from '../../utils/screen'
import { isCondition } from '../../utils/snippets/snippets'
import { ConditionModalButton } from '../ConditionModal/ConditionModalButton'
import { SnippetLocationInput } from '../SnippetForm/fields/SnippetLocationInput'
import { SnippetTypeInput } from '../SnippetForm/fields/SnippetTypeInput'
import { Notices } from '../SnippetForm/page/Notices'
import { ShortcodeInfo } from './actions/ShortcodeInfo'
import { MultisiteSharingSettings } from './controls/MultisiteSharingSettings'
import { ExportButtons } from './actions/ExportButtons'
import { SubmitButtons } from './actions/SubmitButtons'
import { ActivationSwitch } from './controls/ActivationSwitch'
import { DeleteButton } from './actions/DeleteButton'
import { PriorityInput } from './controls/PriorityInput'
import { RTLControl } from './controls/RTLControl'
import type { Dispatch, SetStateAction } from 'react'

export interface EditorSidebarProps {
	setIsUpgradeDialogOpen: Dispatch<SetStateAction<boolean>>
}

export const EditorSidebar: React.FC<EditorSidebarProps> = ({ setIsUpgradeDialogOpen }) => {
	const { snippet, isWorking } = useSnippetForm()

	return (
		<div className="snippet-editor-sidebar">
			<div className="box">
				{snippet.id && !isCondition(snippet) ? <ActivationSwitch /> : null}

				{isNetworkAdmin() ? <MultisiteSharingSettings /> : null}

				{isRTL() ? <RTLControl /> : null}

				<ConditionModalButton setIsDialogOpen={setIsUpgradeDialogOpen} />
				<SnippetLocationInput />
				<ShortcodeInfo />
				<PriorityInput />

				{snippet.id
					? <div className="row-actions visible inline-form-field">
						<ExportButtons />
						<DeleteButton />
					</div> : null}
			</div>

			<p className="submit">
				<SubmitButtons />
				{isWorking ? <Spinner /> : ''}
			</p>

			<Notices />
		</div>
	)
}
