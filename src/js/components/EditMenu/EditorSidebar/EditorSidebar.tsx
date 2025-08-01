import React from 'react'
import { Spinner } from '@wordpress/components'
import { __, isRTL } from '@wordpress/i18n'
import { addQueryArgs } from '@wordpress/url'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { isNetworkAdmin } from '../../../utils/screen'
import { getSnippetType, isCondition } from '../../../utils/snippets/snippets'
import { DeleteButton } from '../../common/DeleteButton'
import { Notices } from '../SnippetForm/page/Notices'
import { ShortcodeInfo } from './actions/ShortcodeInfo'
import { MultisiteSharingSettings } from './controls/MultisiteSharingSettings'
import { ExportButtons } from './actions/ExportButtons'
import { SubmitButtons } from './actions/SubmitButtons'
import { ActivationSwitch } from './controls/ActivationSwitch'
import { PriorityInput } from './controls/PriorityInput'
import { RTLControl } from './controls/RTLControl'
import { TagsInput } from './controls/TagsInput'

export const EditorSidebar = () => {
	const { snippet, isWorking, setIsWorking, handleRequestError } = useSnippetForm()

	return (
		<div className="snippet-editor-sidebar">
			<div className="box">
				{snippet.id && !isCondition(snippet) ? <ActivationSwitch /> : null}

				{isNetworkAdmin() ? <MultisiteSharingSettings /> : null}

				{isRTL() ? <RTLControl /> : null}

				<PriorityInput />

				{window.CODE_SNIPPETS_EDIT?.tagOptions.enabled ? <TagsInput /> : null}

				{snippet.id
					? <div className="row-actions visible">
						<ExportButtons />
						<DeleteButton
							snippet={snippet}
							disabled={isWorking}
							setIsWorking={setIsWorking}
							onSuccess={() =>
								window.location.replace(addQueryArgs(window.CODE_SNIPPETS?.urls.manage, { result: 'deleted' }))}
							onError={error =>
								handleRequestError(error, __('Could not delete snippet.', 'code-snippets'))}
						/>
					</div> : null}
			</div>

			<p className="submit">
				<SubmitButtons />
				{isWorking ? <Spinner /> : ''}
			</p>

			<Notices />

			{'html' === getSnippetType(snippet) ? <ShortcodeInfo /> : null}
		</div>
	)
}
