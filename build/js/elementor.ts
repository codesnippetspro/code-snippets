window.addEventListener('elementor/frontend/init', () => {
	const { elementorFrontend } = window

	elementorFrontend.hooks.addAction('frontend/element_ready/code-snippets-source.default', () =>
		window.Prism ?
			Prism.highlightAll() :
			window.code_snippets_prism ?
				window.code_snippets_prism.highlightAll() :
				console.error('Could not find instance of Prism for code-snippets-source block'))
})
