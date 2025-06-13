import React, { Dispatch, SetStateAction } from 'react'
import type { ConditionOperator } from '../../types/ConditionGroups'
import type { ConditionSubject, ConditionSubjects } from '../../types/ConditionSubject'
import type { SelectGroups, SelectOptions } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import { getConditionRule, updateConditionRule } from '../../utils/conditions/rules'
import { Select } from '../common/Select'
import { ConditionRuleEditorProps } from './ConditionRuleEditor'
import { _x } from '@wordpress/i18n'

interface ObjectSelectProps<S extends ConditionSubject> extends ConditionRuleEditorProps {
	isMulti?: boolean
	options: SelectGroups<ConditionSubjects[S]>
	optionsLoaded: boolean
	onLoadMore: VoidFunction
}

const ObjectSelect = <S extends ConditionSubject>({
	ruleId,
	groupId,
	options,
	condition,
	onLoadMore,
	setCondition,
	optionsLoaded,
	isMulti = false
}: ObjectSelectProps<S>) => {
	const rule = getConditionRule(condition, groupId, ruleId)

	return (
		<Select
			required
			isMulti={isMulti}
			options={options}
			currentValue={isMulti ? rule?.object : rule?.object?.[0]}
			isLoading={!optionsLoaded}
			onMenuScrollToBottom={onLoadMore}
			onSelect={value => {
				setCondition(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: undefined === value ? [] : [value] }))
			}}
			onSelectMulti={values => {
				setCondition(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: values }))
			}}
		/>
	)
}

interface OperatorSelectProps {
	ruleId: string
	groupId: string
	options: SelectGroups<ConditionOperator>
	setCondition: Dispatch<SetStateAction<Snippet>>
	currentOperator: ConditionOperator | undefined
}

const unaryOperations = new Set<ConditionOperator>(['is', 'not', 'true', 'false'])

const OperatorSelect: React.FC<OperatorSelectProps> = ({ options, currentOperator, groupId, ruleId, setCondition }) =>
	<Select
		required
		options={options}
		currentValue={currentOperator}
		onChange={selected => {
			const operator = selected?.value ?? undefined

			setCondition(previous =>
				updateConditionRule(previous, groupId, ruleId, previousRule => ({
					operator,
					...operator && unaryOperations.has(operator) && previousRule.object
						? { object: [previousRule.object[0]] }
						: {}
				})))
		}}
	/>

interface DateSelectProps extends ConditionRuleEditorProps {
	objectIndex?: number
}

const DateSelect: React.FC<DateSelectProps> = ({ ruleId, groupId, condition, setCondition, objectIndex = 0 }) => {
	const rule = getConditionRule(condition, groupId, ruleId)
	const [value, setValue] = React.useState<string>(() =>
		'string' === typeof rule?.object?.[objectIndex] ? rule.object[objectIndex] : '')

	const type = 'timeOfDay' === rule?.subject ? 'time' : 'datetime-local'

	return (
		<input
			type={type}
			name={`snippet-condition-date-${objectIndex}`}
			value={value}
			required
			onChange={event => {
				setValue(event.target.value)

				setCondition(previous =>
					updateConditionRule(previous, groupId, ruleId, ({ object }) => {
						const updated = object ? [...object] : []
						updated[objectIndex] = event.target.value
						return { object: updated }
					}))
			}} />
	)
}

const DateRangeSelect: React.FC<ConditionRuleEditorProps> = ({ ...ruleProps }) =>
	<div className="code-snippets-date-range">
		<DateSelect {...ruleProps} objectIndex={0} />
		<span className="sep">{_x('and', 'date separator', 'code-snippets')}</span>
		<DateSelect {...ruleProps} objectIndex={1} />
	</div>

export interface ConditionObjectEditorProps<S extends ConditionSubject> extends ConditionRuleEditorProps {
	objectOptions: SelectGroups<ConditionSubjects[S]>
	currentOperator: ConditionOperator | undefined
	operatorOptions: SelectOptions<ConditionOperator>
	objectOptionsLoaded: boolean
	loadMoreOptions: VoidFunction
}

export const ConditionObjectEditor = <S extends ConditionSubject>({
	objectOptions,
	currentOperator,
	operatorOptions,
	loadMoreOptions,
	objectOptionsLoaded,
	...ruleProps
}: ConditionObjectEditorProps<S>) => {
	const objectClassName = 'snippet-condition-field snippet-condition-object'
	const operatorSelectProps: OperatorSelectProps = { ...ruleProps, currentOperator, options: operatorOptions }

	const isSingleOperator = 'is' === currentOperator || 'not' === currentOperator
	const isMultiOperator = 'in' === currentOperator || 'not in' === currentOperator
	const isBooleanOperator = 'true' === currentOperator || 'false' === currentOperator
	const isDateOperator = 'before' === currentOperator || 'after' === currentOperator || 'between' === currentOperator

	return (
		<>
			{isSingleOperator || isMultiOperator || isDateOperator
				? <td className="snippet-condition-field snippet-condition-operator">
					<OperatorSelect {...operatorSelectProps} />
				</td>
				: null}

			{isSingleOperator || isMultiOperator || isBooleanOperator
				? <td className={objectClassName}>
					<ObjectSelect
						{...ruleProps}
						options={objectOptions}
						optionsLoaded={objectOptionsLoaded}
						isMulti={isMultiOperator}
						onLoadMore={loadMoreOptions}
					/>
				</td> : null}

			{'before' === currentOperator || 'after' === currentOperator
				? <td className={objectClassName}><DateSelect {...ruleProps} /></td> : null}

			{'between' === currentOperator
				? <td className={objectClassName}><DateRangeSelect {...ruleProps} /></td> : null}

			{isBooleanOperator
				? <td className={objectClassName}><OperatorSelect {...operatorSelectProps} /></td> : null}
		</>
	)
}
