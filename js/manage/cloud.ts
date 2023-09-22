import Prism from 'prismjs'
import 'prismjs/components/prism-clike'
import 'prismjs/components/prism-javascript'
import 'prismjs/components/prism-css'
import 'prismjs/components/prism-php'
import 'prismjs/components/prism-markup'
import 'prismjs/plugins/keep-markup/prism-keep-markup'

/**
 * Handle clicks on snippet preview button.
 */
export const handleShowCloudPreview = () => {
	const previewButtons = document.querySelectorAll('.cloud-snippet-preview')

	previewButtons.forEach(button => {
		button.addEventListener('click', () => {
			const snippetId = button.getAttribute('data-snippet')
			const snippetLanguage = button.getAttribute('data-lang')

			const snippetCodeInput = <HTMLInputElement> document.getElementById(`cloud-snippet-code-${snippetId}`)
			const snippetCode = snippetCodeInput?.value

			const snippetCodeModalTag = <HTMLElement> document.getElementById('snippet-code-thickbox')
			snippetCodeModalTag.classList.remove(...snippetCodeModalTag.classList)
			snippetCodeModalTag.classList.add(`language-${snippetLanguage}`)
			snippetCodeModalTag.textContent = snippetCode

			if ('markup' === snippetLanguage) {
				snippetCodeModalTag.innerHTML = `<xmp>${snippetCode}</xmp>`
			}

			if ('php' === snippetLanguage) {
				// Check if there is an opening php tag if not add it.
				if (!snippetCode.startsWith('<?php')) {
					snippetCodeModalTag.textContent = `<?php\n${snippetCode}`
				}
			}

			Prism.highlightElement(snippetCodeModalTag)
		})
	})
}

/**
 * Handle cloud bundle text and select inputs.
 *
 * On change of bundle share input or select, clear the other.
 */
export const handleCloudBundleInputsChange = () => {
	const bundleInput = <HTMLInputElement> document.getElementById('bundle_share_name')
	const bundleSelect = <HTMLSelectElement> document.getElementById('cloud-bundles')

	bundleInput?.addEventListener('change', () => {
		bundleSelect.value = '0'
	})

	bundleSelect?.addEventListener('change', () => {
		bundleInput.value = ''
	})
}
