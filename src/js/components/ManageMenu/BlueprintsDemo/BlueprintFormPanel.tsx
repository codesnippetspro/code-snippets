import React from 'react'
import { __ } from '@wordpress/i18n'
import classnames from 'classnames'
import type { DemoField, DemoRepeater, DemoSection } from './types'

/**
 * Every control is presented in its native form but held read-only — the
 * walkthrough supplies the values, so nothing here accepts input.
 */
const ReadOnlyControl: React.FC<{ field: DemoField, id: string }> = ({ field, id }) => {
	switch (field.type) {
		case 'textarea':
			return <textarea
				id={id}
				aria-label={field.label}
				className="widefat"
				rows={5}
				readOnly
				value={field.value}
			/>

		case 'select':
			return (
				<select id={id} aria-label={field.label} disabled value={field.value}>
					<option value={field.value}>{field.value}</option>
				</select>
			)

		default:
			return <input type="text" id={id} aria-label={field.label} readOnly value={field.value} />
	}
}

const FormField: React.FC<{ field: DemoField }> = ({ field }) =>
	<div className="blueprint-form-field">
		<label htmlFor={field.name}>
			{field.label} {field.required && <span className="required">*</span>}
		</label>

		<ReadOnlyControl field={field} id={field.name} />

		{field.description && <p className="description">{field.description}</p>}
	</div>

const FieldRepeater: React.FC<{ repeater: DemoRepeater }> = ({ repeater }) =>
	<div className="blueprint-form-field blueprint-form-repeater">
		<label htmlFor={`${repeater.columns[0].name}-0`}>{repeater.label}</label>

		<div className="blueprint-form-repeater">
			{repeater.rows.map((row, rowIndex) =>
				<div key={row.id} className="blueprint-form-repeater__row">
					{repeater.columns.map((column, columnIndex) => {
						const id = `${column.name}-${rowIndex}`

						return (
							<div key={column.name} className="blueprint-form-field blueprint-form-repeater__cell">
								<label htmlFor={id}>{column.label}</label>
								<ReadOnlyControl field={{ ...column, value: row.values[columnIndex] }} id={id} />
								{column.description && <p className="description">{column.description}</p>}
							</div>
						)
					})}

					<div className="blueprint-form-repeater__actions">
						<button type="button" className="button button-secondary button-small" disabled>
							{__('Remove', 'code-snippets')}
						</button>
					</div>
				</div>)}

			<button type="button" className="button button-secondary blueprint-form-repeater__add" disabled>
				{repeater.addLabel}
			</button>
		</div>
	</div>

interface BlueprintFormPanelProps {
	section: DemoSection
	isFading: boolean
}

export const BlueprintFormPanel: React.FC<BlueprintFormPanelProps> = ({ section, isFading }) =>
	<div className={classnames('blueprint-form-content', { 'is-fading': isFading })}>
		<h4>{section.title}</h4>

		{section.description && <p className="blueprint-form-section-description">{section.description}</p>}

		<div className="blueprint-form-fields">
			{section.fields.map(field => <FormField key={field.name} field={field} />)}
			{section.repeater && <FieldRepeater repeater={section.repeater} />}
		</div>
	</div>
