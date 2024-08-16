import Prism from 'prismjs'

window.addEventListener('elementor/frontend/init', () => {
	const { elementorFrontend } = window

	elementorFrontend.hooks.addAction('frontend/element_ready/code-snippets-source.default', () => {
		if (undefined !== <typeof Prism | undefined> window.Prism) {
			Prism.highlightAll()
		} else if (undefined !== window.CODE_SNIPPETS_PRISM) {
			window.CODE_SNIPPETS_PRISM.highlightAll()
		} else {
			console.error('Could not find instance of Prism for code-snippets-source block')
		}
	})
})
