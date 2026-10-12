import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { Badge } from '../../common/Badge'
import { BLUEPRINT_TITLE } from './demoBlueprint'

/**
 * Stands in for the code preview the real blueprint produces. The walkthrough
 * generates nothing, so this only confirms what Pro would have built.
 */
export const GeneratedNotice = React.forwardRef<HTMLDivElement>((_props, ref) =>
	<div className="blueprints-demo-generated" ref={ref} role="status">
		<Badge name="php" />

		<p className="blueprints-demo-generated__text">
			{sprintf(
				/* translators: %s: name of the blueprint, e.g. "Create a Shortcode". */
				__('Snippet “%s” has been generated.', 'code-snippets'),
				BLUEPRINT_TITLE
			)}
		</p>
	</div>)

GeneratedNotice.displayName = 'GeneratedNotice'
