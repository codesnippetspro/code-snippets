/** @type {import('stylelint').Config} */
export default {
	extends: ['stylelint-config-standard-scss'],
	plugins: ['stylelint-scss'],
	customSyntax: 'postcss-scss',
	rules: {
		'at-rule-no-unknown': null,
		'font-family-no-missing-generic-family-keyword': [true, { ignoreFontFamilies: ['dashicons'] }],
		'selector-id-pattern': null,
		'selector-class-pattern': null,
		'scss/at-rule-no-unknown': true,
	}
}
