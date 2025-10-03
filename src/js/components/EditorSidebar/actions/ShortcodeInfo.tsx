import  React, { useState } from 'react'
import { CheckboxControl, ExternalLink, Modal } from '@wordpress/components'
import { __ } from '@wordpress/i18n'
import { useSnippetForm } from '../../../hooks/useSnippetForm'
import { Button } from '../../common/Button'
import { CopyToClipboardButton } from '../../common/CopyToClipboardButton'
import type { Dispatch, SetStateAction } from 'react'

type ShortcodeAtts = Record<string, unknown>

const buildShortcodeTag = (tag: string, atts: ShortcodeAtts): string =>
	`[${[
		tag,
		...Object.entries(atts)
			.filter(([, value]) => Boolean(value))
			.map(([att, value]) =>
				'boolean' === typeof value ? att : `${att}=${JSON.stringify(value)}`)
	].filter(Boolean).join(' ')}]`

const SHORTCODE_TAG = 'code_snippet'

interface ShortcodeOptions {
	php: boolean
	format: boolean
	shortcodes: boolean
}

interface CheckboxListProps<T extends string> {
	options: T[]
	checked: Record<T, boolean>
	disabled: boolean
	setChecked: Dispatch<SetStateAction<Record<T, boolean>>>
	optionLabels: Partial<Record<T, string>>
	optionDescriptions: Partial<Record<T, string>>
}

const CheckboxList = <T extends string>({
	options,
	checked,
	disabled,
	setChecked,
	optionLabels,
	optionDescriptions
}: CheckboxListProps<T>) =>
	<ul>
		{options.map(option =>
			<li key={option}>
				<CheckboxControl
					label={optionLabels[option]}
					help={optionDescriptions[option]}
					checked={checked[option]}
					disabled={disabled}
					onChange={value =>
						setChecked(previous => ({ ...previous, [option]: value }))}
				/>
			</li>)}
	</ul>

const ShortcodeDescription = () =>
	<p className="description">
		{__('Copy the below shortcode to insert this snippet into a post, page, or other content.', 'code-snippets')}{'\n'}
		{__('You can also use the Classic Editor button, Block editor (Pro) or Elementor widget (Pro).', 'code-snippets')}{'\n'}

		<ExternalLink
			href={__('https://codesnippets.pro/doc/inserting-content-snippets/', 'code-snippets')}
		>
			{__('Learn more', 'code-snippets')}
		</ExternalLink>
	</p>

const OPTION_LABELS: Record<keyof ShortcodeOptions, string> = {
	php: __('Evaluate PHP code', 'code-snippets'),
	format: __('Add paragraphs and formatting', 'code-snippets'),
	shortcodes: __('Evaluate additional shortcode tags', 'code-snippets')
}

const OPTION_DESCRIPTIONS: Record<keyof ShortcodeOptions, string> = {
	php: __('Run code within <?php ?> tags.', 'code-snippets'),
	format: __('Wrap output in paragraphs and apply formatting.', 'code-snippets'),
	shortcodes: __('Replace [shortcodes] embedded within the snippet.', 'code-snippets')
}

const ModalContent = () => {
	const { snippet, isReadOnly } = useSnippetForm()

	const [options, setOptions] = useState<ShortcodeOptions>(() => ({
		php: snippet.code.includes('<?'),
		format: true,
		shortcodes: false
	}))

	const shortcodeAtts: ShortcodeAtts = {
		id: snippet.id,
		network: snippet.network,
		...options,
		name: snippet.name
	}

	const shortcodeTag = buildShortcodeTag(SHORTCODE_TAG, shortcodeAtts)

	return (
		<>
			<ShortcodeDescription />

			<p className="shortcode-tag-wrapper">
				<code className="shortcode-tag">{shortcodeTag}</code>
				<CopyToClipboardButton primary text={shortcodeTag} />
			</p>

			<p>
				<h4>{__('Shortcode Options', 'code-snippets')}</h4>

				<CheckboxList
					options={['php', 'format', 'shortcodes']}
					checked={options}
					disabled={isReadOnly}
					setChecked={setOptions}
					optionLabels={OPTION_LABELS}
					optionDescriptions={OPTION_DESCRIPTIONS}
				/>
			</p>
		</>
	)
}

export const ShortcodeInfo: React.FC = () => {
	const { snippet, isReadOnly } = useSnippetForm()
	const [isModalOpen, setIsModalOpen] = useState(false)

	return 'content' === snippet.scope && snippet.id
		? <div className="inline-form-field">
			<h4>{__('Shortcode', 'code-snippets')}</h4>
			<Button onClick={() => setIsModalOpen(true)} disabled={isReadOnly}>
				{__('See options', 'code-snippets')}
			</Button>

			{isModalOpen
				? <Modal
					size="medium"
					className="code-snippets-modal"
					title={__('Embed Snippet with Shortcode', 'code-snippets')}
					onRequestClose={() => setIsModalOpen(false)}
				>
					<div className="modal-content">
						<ModalContent />
					</div>

					<div className="modal-footer">
						<Button link large onClick={() => setIsModalOpen(false)}>
							{__('Close Popup', 'code-snippets')}
						</Button>
					</div>
				</Modal>
				: null}
		</div>
		: null
}
