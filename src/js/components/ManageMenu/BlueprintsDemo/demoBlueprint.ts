import { __ } from '@wordpress/i18n'
import type { DemoSection } from './types'

export const BLUEPRINT_TITLE = __('Create a Shortcode', 'code-snippets')

export const BLUEPRINT_DESCRIPTION = __('Create a custom shortcode to display dynamic content using a simple tag.', 'code-snippets')

export const BLUEPRINT_INTRO = __('Create a custom shortcode to add dynamic content anywhere on your site using a simple tag like [my_shortcode].', 'code-snippets')

export const BLUEPRINT_DOCS_URL = 'https://codesnippets.pro/doc/blueprint-create-a-shortcode/'

const SHORTCODE_OUTPUT = `// Build the card markup from the shortcode attributes.
return sprintf(
	'<div class="staff-profile"><h3>%s</h3><p>%s</p></div>',
	esc_html( $atts['name'] ),
	esc_html( $atts['role'] )
);`

/**
 * The blueprint as the walkthrough presents it: a worked example rather than
 * the empty defaults, so the fields read as a job someone would actually do.
 */
export const DEMO_SECTIONS: DemoSection[] = [
	{
		id: 'general',
		title: __('General', 'code-snippets'),
		fields: [
			{
				name: 'functionName',
				label: __('Function Name', 'code-snippets'),
				type: 'text',
				value: 'render_staff_profile',
				required: true,
				description: __('The callback function name for your shortcode.', 'code-snippets')
			},
			{
				name: 'shortcodeTag',
				label: __('Shortcode Tag', 'code-snippets'),
				type: 'text',
				value: 'staff_profile',
				required: true,
				description: __('The shortcode tag used in content (e.g., [my_shortcode]). Lowercase, no spaces.', 'code-snippets')
			},
			{
				name: 'shortcodeDescription',
				label: __('Description', 'code-snippets'),
				type: 'text',
				value: __('Displays a staff member card with a name and role.', 'code-snippets'),
				description: __('Optional description for documentation purposes.', 'code-snippets')
			},
			{
				name: 'textDomain',
				label: __('Text Domain', 'code-snippets'),
				type: 'text',
				value: 'code-snippets-blueprints',
				description: __('Translation text domain. Optional.', 'code-snippets')
			},
			{
				name: 'isEnclosing',
				label: __('Enclosing Shortcode', 'code-snippets'),
				type: 'select',
				value: __('No (self-closing)', 'code-snippets'),
				required: true,
				description: __('Enclosing shortcodes wrap content: [shortcode]content[/shortcode]', 'code-snippets')
			},
			{
				name: 'supportsNestedShortcodes',
				label: __('Support Nested Shortcodes', 'code-snippets'),
				type: 'select',
				value: __('No', 'code-snippets'),
				required: true,
				description: __('Process shortcodes within the content using do_shortcode().', 'code-snippets')
			}
		]
	},
	{
		id: 'attributes',
		title: __('Attributes', 'code-snippets'),
		description: __('Add shortcode attributes (name and default value). Use $atts in your output code to read them.', 'code-snippets'),
		fields: [],
		repeater: {
			label: __('Shortcode attributes', 'code-snippets'),
			addLabel: __('Add attribute', 'code-snippets'),
			columns: [
				{
					name: 'name',
					label: __('Attribute name', 'code-snippets'),
					type: 'text',
					value: ''
				},
				{
					name: 'default',
					label: __('Default value', 'code-snippets'),
					type: 'text',
					value: ''
				},
				{
					name: 'type',
					label: __('Attribute type', 'code-snippets'),
					type: 'select',
					value: ''
				}
			],
			rows: [
				{ id: 'name', values: ['name', 'Jane Doe', __('Text', 'code-snippets')] },
				{ id: 'role', values: ['role', 'Director', __('Text', 'code-snippets')] }
			]
		}
	},
	{
		id: 'output',
		title: __('Output', 'code-snippets'),
		fields: [
			{
				name: 'useOutputBuffering',
				label: __('Use Output Buffering', 'code-snippets'),
				type: 'select',
				value: __('No (use return statement)', 'code-snippets'),
				required: true,
				description: __('Enable output buffering to capture echoed content instead of returning.', 'code-snippets')
			},
			{
				name: 'shortcodeCode',
				label: __('Shortcode Code', 'code-snippets'),
				type: 'textarea',
				value: SHORTCODE_OUTPUT,
				required: true,
				description: __('The PHP code that generates the shortcode output. Use $atts for attributes and $content for enclosed content.', 'code-snippets')
			}
		]
	}
]

export const getSection = (id: string): DemoSection =>
	DEMO_SECTIONS.find(section => section.id === id) ?? DEMO_SECTIONS[0]
