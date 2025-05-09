/** @type {import('stylelint').Config} */
export default {
	extends: ['stylelint-config-standard-scss'],
	plugins: [
		'stylelint-scss',
		'stylelint-use-logical'
	],
	customSyntax: 'postcss-scss',
	rules: {
		'at-rule-no-unknown': null,
		'csstools/use-logical': 'always',
		'font-family-no-missing-generic-family-keyword': [true, { ignoreFontFamilies: ['dashicons'] }],
		'selector-id-pattern': null,
		'selector-class-pattern': null,
		'scss/at-rule-no-unknown': true,
	}
}
