import React from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import { DEMO_SECTIONS } from './demoBlueprint'

interface BlueprintSidebarProps {
	activeSection: string
	browsable: boolean
	/** Whether the walkthrough is clicking the generate button right now. */
	generating: boolean
	onSelect: (id: string) => void
}

export const BlueprintSidebar: React.FC<BlueprintSidebarProps> = ({
	activeSection,
	browsable,
	generating,
	onSelect
}) =>
	<nav className="blueprint-form-sidebar" aria-label={__('Blueprint sections', 'code-snippets')}>
		<ul>
			{DEMO_SECTIONS.map(section => {
				const isActive = section.id === activeSection

				return (
					<li key={section.id}>
						<button
							type="button"
							className={classnames('blueprint-form-sidebar__item', { 'is-active': isActive })}
							aria-current={isActive ? 'step' : undefined}
							disabled={!browsable}
							onClick={() => onSelect(section.id)}
						>
							<span>{section.title}</span>
						</button>
					</li>
				)
			})}
		</ul>

		<div className="blueprint-form-sidebar__generate-wrap">
			<button
				type="button"
				className={classnames('blueprint-form-sidebar__generate', { 'demo-click': generating })}
				aria-hidden="true"
				tabIndex={-1}
			>
				{__('Generate Code', 'code-snippets')}
			</button>
		</div>
	</nav>
