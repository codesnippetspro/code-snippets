import React from 'react'
import { __, _n, sprintf } from '@wordpress/i18n'

export interface EnvironmentDisclosureProps {
	summary: Record<string, string>
	errorCount: number
}

export const EnvironmentDisclosure: React.FC<EnvironmentDisclosureProps> = ({ summary, errorCount }) =>
	<details className="code-snippets-feedback-disclosure">
		<summary>{__('What gets sent with this report', 'code-snippets')}</summary>

		<dl className="code-snippets-feedback-disclosure__list">
			{Object.entries(summary).map(([label, value]) =>
				<React.Fragment key={label}>
					<dt>{label}</dt>
					<dd>{value}</dd>
				</React.Fragment>
			)}

			<dt>{__('JavaScript errors', 'code-snippets')}</dt>
			<dd>
				{0 === errorCount
					? __('none on this page', 'code-snippets')
					// translators: %d: number of JavaScript errors captured on the current page.
					: sprintf(_n('%d error captured', '%d errors captured', errorCount, 'code-snippets'), errorCount)}
			</dd>
		</dl>

		<p className="code-snippets-feedback-disclosure__note">
			{__('Your site address, email address and full plugin list stay with the Code Snippets team. Only the report itself and the version numbers appear on the public issue tracker.', 'code-snippets')}
		</p>
	</details>
