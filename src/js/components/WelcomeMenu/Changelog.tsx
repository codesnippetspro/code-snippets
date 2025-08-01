import React, { Fragment } from 'react'
import { __, sprintf } from '@wordpress/i18n'
import { CHANGELOG_SECTIONS } from '../../types/schema/WelcomeSchema'
import type { ChangelogSectionTitle } from '../../types/schema/WelcomeSchema'

const CHANGELOG_LABELS: Record<ChangelogSectionTitle, string> = {
	Added: __('New features', 'code-snippets'),
	Changed: __('Improvements', 'code-snippets'),
	Deprecated: __('Deprecated features', 'code-snippets'),
	Removed: __('Removed features', 'code-snippets'),
	Fixed: __('Bug fixes', 'code-snippets'),
	Security: __('Security updates', 'code-snippets'),
	Other: __('Other', 'code-snippets')
}

const CHANGELOG_ICONS: Record<ChangelogSectionTitle, string> = {
	Added: 'lightbulb',
	Changed: 'chart-line',
	Deprecated: 'remove',
	Removed: 'trash',
	Fixed: 'buddicons-replies',
	Security: 'shield',
	Other: 'open-folder'
}

const PLUGIN_TYPE_LABELS: Record<string, string> = {
	core: __('Core', 'code-snippets'),
	pro: __('Pro', 'code-snippets')
}

const CHANGELOG_DATA = window.CODE_SNIPPETS_WELCOME?.changelog

interface ChangelogSectionProps {
	section: ChangelogSectionTitle
	entries: Record<string, string[]>
}

const ChangelogSection: React.FC<ChangelogSectionProps> = ({ section, entries }) =>
	<>
		<h4>
			<span className={`dashicons dashicons-${CHANGELOG_ICONS[section]}`}></span>
			{CHANGELOG_LABELS[section]}
		</h4>
		<ul>
			{Object.entries(entries).map(([pluginType, changes]) =>
				changes.map(change =>
					<li key={change}>
						<span className={`badge ${pluginType}-badge`}>
							{PLUGIN_TYPE_LABELS[pluginType] ?? pluginType}
						</span>
						<span>{change}</span>
					</li>)
			)}
		</ul>
	</>

export const Changelog = () =>
	<div className="code-snippets-changelog">
		<header>
			<h2>{__('Latest changes', 'code-snippets')}</h2>
			<a
				href="https://wordpress.org/plugins/code-snippets/changelog"
				className="button button-primary button-large"
				target="_blank" rel="noreferrer"
			>
				{__('View changelog', 'code-snippets')}
			</a>
		</header>
		<div className="code-snippets-changelog-entries">
			{CHANGELOG_DATA?.map(({ version, date, entries }) =>
				<Fragment key={version}>
					<header>
						{/* translators: %s: version number. */}
						<h3>{sprintf(__('Version %s', 'code-snippets'), version)}</h3>
						<p>{date}</p>
					</header>
					<article>
						{CHANGELOG_SECTIONS.map(section =>
							entries[section]
								? <ChangelogSection key={section} section={section} entries={entries[section]} />
								: null)}
					</article>
				</Fragment>)}
		</div>
	</div>
