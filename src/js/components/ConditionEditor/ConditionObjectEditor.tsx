import React from 'react'
import { _x } from '@wordpress/i18n'
import { getConditionRule, updateConditionRule } from '../../utils/conditions/rules'
import { Select } from '../common/Select'
import type { Dispatch, SetStateAction } from 'react'
import type { ConditionOperator } from '../../types/ConditionGroups'
import type { ConditionSubject, ConditionSubjects } from '../../types/ConditionSubject'
import type { SelectGroups, SelectOption, SelectOptions } from '../../types/SelectOption'
import type { Snippet } from '../../types/Snippet'
import type { ConditionRuleEditorProps } from './ConditionRuleEditor'
import type { InputActionMeta } from 'react-select'

interface ObjectSelectProps<S extends ConditionSubject> extends ConditionRuleEditorProps {
	isMulti?: boolean
	options: SelectGroups<ConditionSubjects[S]>
	optionsLoaded: boolean
	onLoadMore: VoidFunction
	onSearch?: (query: string) => void
	searchingOptions?: boolean
}

const getValueKey = (value: unknown): string => {
	if ('string' === typeof value) {
		return value
	}

	if ('number' === typeof value) {
		return value.toString()
	}

	return JSON.stringify(value)
}

const getOptionKey = <T, >(option: SelectOption<T>): string =>
	getValueKey(option.key ?? option.value)

const prioritizeSelectedOptions = <T, >(options: SelectGroups<T>, selectedValues: readonly T[]): SelectGroups<T> => {
	if (0 === selectedValues.length) {
		return options
	}

	const selectedKeys = new Set<string>(selectedValues.map(getValueKey))
	const selectedOptions: SelectOption<T>[] = []
	const selectedSeen = new Set<string>()
	const remaining: ((typeof options)[number])[] = []

	const shouldKeepOption = (option: SelectOption<T>): boolean => {
		const optionKey = getOptionKey(option)
		if (!selectedKeys.has(optionKey)) {
			return true
		}

		if (!selectedSeen.has(optionKey)) {
			selectedSeen.add(optionKey)
			selectedOptions.push(option)
		}

		return false
	}

	for (const entry of options) {
		if ('options' in entry) {
			const filteredOptions = entry.options.filter(shouldKeepOption)

			if (0 < filteredOptions.length) {
				remaining.push({ ...entry, options: filteredOptions })
			}
		} else if (shouldKeepOption(entry)) {
			remaining.push(entry)
		}
	}

	return [...selectedOptions, ...remaining]
}

const ObjectSelect = <S extends ConditionSubject>({
	ruleId,
	groupId,
	options,
	condition,
	onLoadMore,
	setCondition,
	optionsLoaded,
	isMulti = false,
	onSearch,
	searchingOptions
}: ObjectSelectProps<S>) => {
	const rule = getConditionRule(condition, groupId, ruleId)

	const prioritizedOptions = React.useMemo(
		() => {
			const selectedValues = isMulti
				? rule?.object ?? []
				: rule?.object?.[0] !== undefined
					? [rule.object[0]]
					: []

			return prioritizeSelectedOptions(options, selectedValues)
		},
		[isMulti, options, rule?.object]
	)

	return (
		<Select
			required
			className="snippet-condition-field snippet-condition-object"
			isDisabled={setCondition === undefined}
			isMulti={isMulti}
			menuShouldBlockScroll={true}
			options={prioritizedOptions}
			currentValue={isMulti ? rule?.object : rule?.object?.[0]}
			isLoading={!optionsLoaded || !!searchingOptions}
			onMenuScrollToBottom={searchingOptions ? undefined : onLoadMore}
			onInputChange={(value, actionMeta: InputActionMeta) => {
				switch (actionMeta.action) {
					case 'input-change':
						onSearch?.(value)
						break

					case 'input-blur':
					case 'menu-close':
						onSearch?.('')
						break
				}

				return value
			}}
			onMenuClose={() => onSearch?.('')}
			onSelect={value => {
				setCondition?.(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: undefined === value ? [] : [value] }))
			}}
			onSelectMulti={values => {
				setCondition?.(previous =>
					updateConditionRule(previous, groupId, ruleId, { object: values }))
			}}
		/>
	)
}

interface OperatorSelectProps {
	ruleId: string
	groupId: string
	options: SelectGroups<ConditionOperator>
	setCondition?: Dispatch<SetStateAction<Snippet>>
	currentOperator: ConditionOperator | undefined
}

const unaryOperations = new Set<ConditionOperator>(['is', 'not', 'true', 'false'])

const OperatorSelect: React.FC<OperatorSelectProps> = ({ options, currentOperator, groupId, ruleId, setCondition }) =>
	<Select
		required
		className="snippet-condition-field snippet-condition-operator"
		isDisabled={setCondition === undefined}
		options={options}
		currentValue={currentOperator}
		onChange={selected => {
			const operator = selected?.value ?? undefined

			setCondition?.(previous =>
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
			readOnly={setCondition === undefined}
			name={`snippet-condition-date-${objectIndex}`}
			value={value}
			required
			onChange={event => {
				setValue(event.target.value)

				setCondition?.(previous =>
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
		<span className="sep">{_x('to', 'date separator', 'code-snippets')}</span>
		<DateSelect {...ruleProps} objectIndex={1} />
	</div>

export interface ConditionObjectEditorProps<S extends ConditionSubject> extends ConditionRuleEditorProps {
	objectOptions: SelectGroups<ConditionSubjects[S]>
	currentOperator: ConditionOperator | undefined
	operatorOptions: SelectOptions<ConditionOperator>
	objectOptionsLoaded: boolean
	loadMoreOptions: VoidFunction
	setSearchQuery: (query: string) => void
	searchingOptions: boolean
}

export const ConditionObjectEditor = <S extends ConditionSubject>({
	objectOptions,
	currentOperator,
	operatorOptions,
	loadMoreOptions,
	objectOptionsLoaded,
	setSearchQuery,
	searchingOptions,
	...ruleProps
}: ConditionObjectEditorProps<S>) => {
	const operatorSelectProps: OperatorSelectProps = { ...ruleProps, currentOperator, options: operatorOptions }

	const isMissingOperator = undefined === currentOperator
	const isSingleOperator = 'is' === currentOperator || 'not' === currentOperator
	const isMultiOperator = 'in' === currentOperator || 'not in' === currentOperator
	const isBooleanOperator = 'true' === currentOperator || 'false' === currentOperator
	const isDateOperator = 'before' === currentOperator || 'after' === currentOperator || 'between' === currentOperator

	return (
		<>
			{isSingleOperator || isMultiOperator || isDateOperator || isMissingOperator
				? <OperatorSelect {...operatorSelectProps} />
				: null}

			{isSingleOperator || isMultiOperator || isBooleanOperator || isMissingOperator
				? <ObjectSelect
					{...ruleProps}
					options={objectOptions}
					optionsLoaded={objectOptionsLoaded}
					isMulti={isMultiOperator}
					onLoadMore={loadMoreOptions}
					onSearch={setSearchQuery}
					searchingOptions={searchingOptions}
				/>
				: null}

			{isDateOperator
				? <div className="snippet-condition-field snippet-condition-object">
					{'between' === currentOperator
						? <DateRangeSelect {...ruleProps} />
						: <DateSelect {...ruleProps} />}
				</div> : null}

			{isBooleanOperator
				? <OperatorSelect {...operatorSelectProps} /> : null}
		</>
	)
}
