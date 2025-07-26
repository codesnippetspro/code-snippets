import React, { Fragment } from 'react'
import { CHANGELOG_SECTIONS } from '../../types/schema/WelcomeSchema'
import type { ChangelogSectionTitle } from '../../types/schema/WelcomeSchema'
import { __ } from '@wordpress/i18n'

const CHANGELOG_LABELS: Record<ChangelogSectionTitle, string> = {
	Added: __('New features', 'code-snippets'),
	Changed: __('Improvements', 'code-snippets'),
	Fixed: __('Bug fixes', 'code-snippets'),
	Other: __('Other', 'code-snippets')
}

const CHANGELOG_ICONS: Record<ChangelogSectionTitle, string> = {
	Added: 'lightbulb',
	Changed: 'chart-line',
	Fixed: 'buddicons-replies',
	Other: 'open-folder'
}

const PLUGIN_TYPE_LABELS: Record<string, string> = {
	core: __('Core', 'code-snippets'),
	pro: __('Pro', 'code-snippets')
}

const CHANGELOG_DATA = window.CODE_SNIPPETS_WELCOME?.changelog

interface ChangelogSectionProps {
	section: ChangelogSectionTitle
	versionNumber: string
	changes: Record<string, Record<string, string[]>>
}

const ChangelogSection: React.FC<ChangelogSectionProps> = ({ section, versionNumber, changes }) =>
	<>
		<h4>
			<span className={`dashicons dashicons-${CHANGELOG_ICONS[section]}`}></span>
			{CHANGELOG_LABELS[section]}
		</h4>
		<ul>
			{Object.entries(changes[section]).map(([pluginType, changes]) =>
				changes.map(change =>
					<li key={`${versionNumber}-${section}-${pluginType}-${change}`}>
						<span className={`badge ${pluginType}-badge`}>
							{PLUGIN_TYPE_LABELS[pluginType] ?? pluginType}
						</span>
						<span>{change}</span>
					</li>)
			)}
		</ul>
	</>

export const Changelog = () =>
	<a
		target="_blank"
		className="code-snippets-card csp-changelog-wrapper"
		href="https://wordpress.org/plugins/code-snippets/changelog"
		title={__('Read the full changelog', 'code-snippets')}
	>
		<header>
			<span className="dashicons dashicons-external"></span>
			<h2>{__('Latest changes', 'code-snippets')}</h2>
		</header>
		<div className="csp-section-changelog">
			{CHANGELOG_DATA && Object.entries(CHANGELOG_DATA).map(([versionNumber, versionChanges]) =>
				<Fragment key={versionNumber}>
					<h3>{versionNumber}</h3>
					<article>
						{CHANGELOG_SECTIONS
							.filter(section => versionChanges[section])
							.map(section =>
								<ChangelogSection
									key={`${versionNumber}-${section}`}
									section={section}
									versionNumber={versionNumber}
									changes={versionChanges}
								/>)}
					</article>
				</Fragment>)}
		</div>
	</a>
