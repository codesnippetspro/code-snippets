import jQuery from 'jquery'

jQuery(window).on('elementor/frontend/init', () => {
	const { elementorFrontend, code_snippets_prism: Prism } = window

	elementorFrontend.hooks.addAction('frontend/element_ready/code-snippets-source.default', () =>
		Prism?.highlightAll())
})
