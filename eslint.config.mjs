// @ts-check

import globals from 'globals'
import eslintJs from '@eslint/js'
import eslintTs from 'typescript-eslint'
import stylistic from '@stylistic/eslint-plugin'
import reactHooks from 'eslint-plugin-react-hooks'
import importPlugin from 'eslint-plugin-import'
import jsxA11yPlugin from 'eslint-plugin-jsx-a11y'
import reactPlugin from 'eslint-plugin-react'
import svgPlugin from 'eslint-plugin-svg-jsx'
import { FlatCompat } from '@eslint/eslintrc'

const compat = new FlatCompat({
	baseDirectory: import.meta.dirname,
	recommendedConfig: eslintJs.configs.recommended
})

export default eslintTs.config(
	eslintJs.configs.recommended,
	...eslintTs.configs.strictTypeChecked,
	...eslintTs.configs.stylisticTypeChecked,
	...compat.extends('plugin:react-hooks/recommended'),
	reactPlugin.configs.flat.recommended,
	importPlugin.flatConfigs.recommended,
	jsxA11yPlugin.flatConfigs.recommended,
	{
		plugins: { 'react-hooks': reactHooks },
		rules: reactHooks.configs.recommended.rules,
	},
	{
		ignores: [
			'bundle/*', 'src/dist/*', 'src/vendor/*', 'svn/*',
			'*.config.mjs', '*.config.js',
			'.*/*', 'tmp/*', 'playwright-report/*'
		]
	},
	{
		languageOptions: {
			ecmaVersion: 2022,
			globals: { ...globals.browser },
			parserOptions: {
				ecmaVersion: 2022,
				ecmaFeatures: { jsx: true },
				tsconfigRootDir: import.meta.dirname,
				projectService: { allowDefaultProject: ['eslint.config.mjs'] }
			}
		},
		plugins: {
			'@stylistic': stylistic,
			'react': reactPlugin,
			'svg-jsx': svgPlugin
		},
		rules: {
			'@stylistic/array-bracket-newline': ['error', 'consistent'],
			'@stylistic/arrow-parens': ['error', 'as-needed'],
			'@stylistic/comma-dangle': ['error', 'only-multiline'],
			'@stylistic/dot-location': ['error', 'property'],
			'@stylistic/function-call-argument-newline': ['error', 'consistent'],
			'@stylistic/indent': ['error', 'tab', { SwitchCase: 1 }],
			'@stylistic/jsx-quotes': ['error', 'prefer-double'],
			'@stylistic/linebreak-style': ['error', 'unix'],
			'@stylistic/max-len': ['error', 140, { ignorePattern: 'd="(.*?)"|_[_xn]\\(|import .+ from .+' }],
			'@stylistic/multiline-ternary': 'off',
			'@stylistic/no-extra-parens': ['error', 'all', { ignoreJSX: 'all', returnAssign: true }],
			'@stylistic/no-mixed-spaces-and-tabs': ['error', 'smart-tabs'],
			'@stylistic/no-tabs': ['error', { allowIndentationTabs: true }],
			'@stylistic/object-property-newline': ['error', { allowAllPropertiesOnSameLine: true }],
			'@stylistic/operator-linebreak': ['error', 'after', { 'overrides': { '?': 'before', ':': 'before' } }],
			'@stylistic/padded-blocks': ['error', 'never'],
			'@stylistic/quote-props': ['error', 'consistent-as-needed'],
			'@stylistic/quotes': ['error', 'single', { avoidEscape: true }],
			'@stylistic/semi': ['error', 'never'],
			'@typescript-eslint/await-thenable': 'error',
			'@typescript-eslint/ban-ts-comment': 'error',
			'@typescript-eslint/consistent-type-assertions': ['error', {
				assertionStyle: 'angle-bracket',
				objectLiteralTypeAssertions: 'never'
			}],
			'@typescript-eslint/consistent-type-imports': 'error',
			'@typescript-eslint/no-confusing-void-expression': ['error', { ignoreArrowShorthand: true }],
			'@typescript-eslint/no-for-in-array': 'error',
			'@typescript-eslint/no-import-type-side-effects': 'error',
			'@typescript-eslint/no-inferrable-types': ['error', { ignoreProperties: true, ignoreParameters: false }],
			'@typescript-eslint/no-magic-numbers': ['error', { ignore: [-1, 0, 1], ignoreEnums: true }],
			'@typescript-eslint/no-unused-vars': ['error', {
				argsIgnorePattern: '^_',
				varsIgnorePattern: '^_',
				caughtErrorsIgnorePattern: '^_',
				ignoreRestSiblings: true
			}],
			'@typescript-eslint/prefer-includes': 'error',
			'@typescript-eslint/prefer-string-starts-ends-with': 'error',
			'@typescript-eslint/restrict-template-expressions': ['error', { allowNumber: true }],
			'capitalized-comments': ['warn', 'always', {
				ignorePattern: 'translators:',
				ignoreInlineComments: true,
				ignoreConsecutiveComments: true
			}],
			'curly': 'error',
			'dot-notation': 'error',
			'eqeqeq': ['error', 'always'],
			'func-style': ['error', 'expression'],
			'import/export': 'error',
			'import/named': 'error',
			'import/no-duplicates': 'warn',
			'import/no-namespace': 'error',
			'import/no-unresolved': 'error',
			'import/no-useless-path-segments': 'warn',
			'import/order': ['error', {
				'groups': ['builtin', 'external', 'internal', 'parent', 'sibling', 'index', 'object', 'type'],
				'newlines-between': 'never',
				'alphabetize': { orderImportKind: 'asc' }
			}],
			'max-lines-per-function': ['warn', { skipBlankLines: true, skipComments: true }],
			'no-invalid-this': 'error',
			'no-plusplus': ['error', { allowForLoopAfterthoughts: true }],
			'no-ternary': 'off',
			'one-var': ['error', 'never'],
			'prefer-named-capture-group': 'error',
			'prefer-template': 'error',
			'sort-imports': ['error', { ignoreDeclarationSort: true }],
			'yoda': ['error', 'always'],
			// Accessibility rules.
			'jsx-a11y/alt-text': 'error',
			'jsx-a11y/anchor-has-content': 'error',
			'jsx-a11y/anchor-is-valid': 'error',
			'jsx-a11y/aria-props': 'error',
			'jsx-a11y/aria-proptypes': 'error',
			'jsx-a11y/aria-role': 'error',
			'jsx-a11y/aria-unsupported-elements': 'error',
			'jsx-a11y/click-events-have-key-events': 'error',
			'jsx-a11y/control-has-associated-label': ['warn', { ignoreElements: ['th', 'td'] }],
			'jsx-a11y/heading-has-content': 'error',
			'jsx-a11y/iframe-has-title': 'error',
			'jsx-a11y/img-redundant-alt': 'error',
			'jsx-a11y/interactive-supports-focus': 'error',
			'jsx-a11y/label-has-associated-control': 'error',
			'jsx-a11y/no-autofocus': 'error',
			'jsx-a11y/no-noninteractive-element-interactions': 'error',
			'jsx-a11y/no-noninteractive-tabindex': 'error',
			'jsx-a11y/no-redundant-roles': 'error',
			'jsx-a11y/no-static-element-interactions': 'error',
			'jsx-a11y/role-has-required-aria-props': 'error',
			'jsx-a11y/role-supports-aria-props': 'error',
			'jsx-a11y/tabindex-no-positive': 'error',
			'svg-jsx/camel-case-dash': 'error',
			'svg-jsx/camel-case-colon': 'error',
			'svg-jsx/no-style-string': 'error',
		},
		settings: {
			'react': {
				version: 'detect'
			},
			'import/resolver': {
				typescript: {
					alwaysTryTypes: true,
					project: './tsconfig.json',
				}
			}
		}
	},
	{
		files: ['**/*.tsx'],
		rules: {
			'@typescript-eslint/consistent-type-assertions': ['error', {
				assertionStyle: 'as',
				objectLiteralTypeAssertions: 'never'
			}],
		}
	},
	{
		files: ['tests/**', '**/*.test.*', '**/*.spec.*'],
		rules: {
			'@typescript-eslint/no-magic-numbers': 'off',
			'max-lines-per-function': 'off'
		}
	}
)
