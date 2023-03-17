window.addEventListener('elementor/frontend/init', () => {
	const { elementorFrontend } = window

	elementorFrontend.hooks.addAction('frontend/element_ready/code-snippets-source.default', () =>
		window.Prism ?
			Prism.highlightAll() :
			window.CODE_SNIPPETS_PRISM ?
				window.CODE_SNIPPETS_PRISM.highlightAll() :
				console.error('Could not find instance of Prism for code-snippets-source block'))
})
