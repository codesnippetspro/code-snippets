import jQuery from 'jquery'

const { elementorFrontend } = window

jQuery(window).on('elementor/frontend/init', () => {

	elementorFrontend.hooks.addAction('frontend/element_ready/code-snippets-source.default', () => {
		if (window.Prism) {
			window.Prism.highlightAll()
		}
	})
})
