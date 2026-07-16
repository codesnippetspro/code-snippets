import React from 'react'
import { Spinner } from '@wordpress/components'
import { __, isRTL } from '@wordpress/i18n'
import { buildUrl } from '../../../utils/urls'
import { useSnippetForm } from '../SnippetForm/WithSnippetFormContext'
import { isNetworkAdmin } from '../../../utils/screen'
import { isCondition } from '../../../utils/snippets/snippets'
import { DeleteButton } from '../../common/DeleteButton'
import { ConditionModalButton } from '../ConditionModal/ConditionModalButton'
import { SnippetLocationInput } from '../SnippetForm/fields/SnippetLocationInput'
import { Notices } from '../SnippetForm/page/Notices'
import { ShortcodeInfo } from './actions/ShortcodeInfo'
import { MultisiteSharingSettings } from './controls/MultisiteSharingSettings'
import { ExportButtons } from './actions/ExportButtons'
import { SubmitButtons } from './actions/SubmitButtons'
import { ActivationSwitch } from './controls/ActivationSwitch'
import { LockControl } from './controls/LockControl'
import { PriorityInput } from './controls/PriorityInput'
import { RTLControl } from './controls/RTLControl'
import type { Dispatch, SetStateAction } from 'react'

export interface EditorSidebarProps {
	setIsUpgradeDialogOpen: Dispatch<SetStateAction<boolean>>
}

export const EditorSidebar: React.FC<EditorSidebarProps> = ({ setIsUpgradeDialogOpen }) => {
	const { snippet, isWorking, setIsWorking, handleRequestError } = useSnippetForm()

	return (
		<div className="snippet-editor-sidebar">
			<div className="box">
				{snippet.id && !isCondition(snippet) ? <ActivationSwitch /> : null}

				{isNetworkAdmin() && <MultisiteSharingSettings />}

				{isRTL() && <RTLControl />}

				<ConditionModalButton setIsDialogOpen={setIsUpgradeDialogOpen} />
				<SnippetLocationInput />
				<ShortcodeInfo />
				<PriorityInput />

				{snippet.id
					? <div className="row-actions visible inline-form-field">
						<ExportButtons />
						<DeleteButton
							snippet={snippet}
							disabled={isWorking || snippet.locked}
							setIsWorking={setIsWorking}
							onSuccess={() => {
								window.location.replace(buildUrl(window.CODE_SNIPPETS?.urls.manage, { result: 'deleted' }))
							}}
							onError={error => {
								handleRequestError(error, __('Could not delete snippet.', 'code-snippets'))
							}}
						/>
						<LockControl />
					</div>
					: null}
			</div>

			<p className="submit">
				<SubmitButtons />
				{isWorking ? <Spinner /> : ''}
			</p>

			<Notices placement="sidebar" />
		</div>
	)
}
