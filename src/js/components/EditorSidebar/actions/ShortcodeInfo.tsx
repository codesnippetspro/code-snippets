import React, { useState } from 'react'
import { ExternalLink } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { isNetworkAdmin } from '../../../utils/screen'
import { buildShortcodeTag } from '../../../utils/shortcodes'
import { CopyToClipboardButton } from '../../common/CopyToClipboardButton'
import type { ShortcodeAtts } from '../../../utils/shortcodes'
import type { Dispatch, SetStateAction} from 'react'

const SHORTCODE_TAG = 'code_snippet'

interface ShortcodeOptions {
	php: boolean
	format: boolean
	shortcodes: boolean
}

const ShortcodeTag: React.FC<{ atts: ShortcodeAtts }> = ({ atts }) =>
	<p>
		<pre><code className="shortcode-tag">{buildShortcodeTag(SHORTCODE_TAG, atts)}</code></pre>

		<CopyToClipboardButton
			title={__('Copy shortcode to clipboard', 'code-snippets')}
			text={buildShortcodeTag(SHORTCODE_TAG, atts)}
		/>
	</p>

interface ShortcodeOptionsProps {
	optionLabels: [keyof ShortcodeOptions, string][]
	options: ShortcodeOptions
	setOptions: Dispatch<SetStateAction<ShortcodeOptions>>
	isReadOnly: boolean
}

const ShortcodeOptions: React.FC<ShortcodeOptionsProps> = ({
	optionLabels,
	options,
	setOptions,
	isReadOnly
}) =>
	<p className="html-shortcode-options">
		<h4>{__('Shortcode Options', 'code-snippets')}</h4>
		<ul>
			{optionLabels.map(([option, label]) =>
				<li key={option}>
					<label>
						<input
							type="checkbox"
							value={option}
							checked={options[option]}
							disabled={isReadOnly}
							onChange={event =>
								setOptions(previous => ({ ...previous, [option]: event.target.checked }))}
						/>
						{` ${label}`}
					</label>
				</li>
			)}
		</ul>
	</p>

export const ShortcodeInfo: React.FC = () => {
	const { snippet, isReadOnly } = useSnippetForm()
	const [options, setOptions] = useState<ShortcodeOptions>(() => ({
		php: snippet.code.includes('<?'),
		format: true,
		shortcodes: false
	}))

	return 'content' === snippet.scope
		? <>
			<p className="description">
				{__('There are multiple options for inserting this snippet into a post, page or other content.', 'code-snippets')}
				{' '}
				{snippet.id
					// eslint-disable-next-line @stylistic/max-len
					? __('You can copy the below shortcode, or use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-snippets')
					// eslint-disable-next-line @stylistic/max-len
					: __('After saving, you can copy a shortcode, or use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-snippets')}
				{' '}
				<ExternalLink
					href={__('https://help.codesnippets.pro/article/50-inserting-snippets', 'code-snippets')}
				>
					{__('Learn more', 'code-snippets')}
				</ExternalLink>
			</p>

			{snippet.id
				? <>
					<ShortcodeTag atts={{
						id: snippet.id,
						network: snippet.network ?? isNetworkAdmin(),
						...options
					}} />

					<ShortcodeOptions
						options={options}
						setOptions={setOptions}
						isReadOnly={isReadOnly}
						optionLabels={[
							['php', __('Evaluate PHP code', 'code-snippets')],
							['format', __('Add paragraphs and formatting', 'code-snippets')],
							['shortcodes', __('Evaluate additional shortcode tags', 'code-snippets')]
						]}
					/>
				</> : null}
		</> : null
}
