/** @type {import('stylelint').Config} */
export default {
	extends: ['stylelint-config-standard-scss'],
	plugins: [
		'stylelint-scss',
		'stylelint-use-logical',
		'@stylistic/stylelint-plugin'
	],
	customSyntax: 'postcss-scss',
	rules: {
		'@stylistic/color-hex-case': 'lower',
		'@stylistic/indentation': 'tab',
		'at-rule-no-unknown': null,
		'csstools/use-logical': 'always',
		'color-function-notation': 'modern',
		'font-family-no-missing-generic-family-keyword': [true, { ignoreFontFamilies: ['dashicons'] }],
		'selector-id-pattern': null,
		'selector-class-pattern': null,
		'scss/at-rule-no-unknown': true,
	},
	fix: true
}
