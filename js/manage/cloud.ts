import * as Prism from 'prismjs'

//Handle clicks on snippet preview button 
export const handleShowCloudPreview = () => {
	let prisGrammar = null
	let prisLang = null
	const previewButtons = document.querySelectorAll('.cloud-snippet-preview')
	//Add click event listener to buttons
	previewButtons.forEach( button => {
		button.addEventListener('click', event => {
			event.preventDefault()
			//Get the snippet ID from the button's data attribute
			const snippetId = button.getAttribute('data-snippet')
			//Get the snippet name from input with ID cloud-snippet-name-<snippetId>
			const snippetNameInput = document.getElementById(`cloud-snippet-name-${snippetId}`) as HTMLInputElement
			const snippetName = snippetNameInput.value
			//Get the snippet language type 
			const snippetLanguageInput = document.getElementById(`snippet-type-${snippetId}`) as HTMLAnchorElement
			const snippetLanguage = snippetLanguageInput.getAttribute('data-type')
			switch (snippetLanguage) {
				case 'php':
					prisGrammar = Prism.languages.php
					prisLang = 'php'
					break
				case 'js':
					prisGrammar = Prism.languages.javascript
					prisLang = 'javascript'
					break
				case 'css':
					prisGrammar = Prism.languages.css
					prisLang = 'css'
					break
				case 'html':
					prisGrammar = Prism.languages.html
					prisLang = 'html'
					break
				default:
					prisGrammar = Prism.languages.php
					break
			}
			//Get the snippet code from input with ID cloud-snippet-code-<snippetId>
			const snippetCodeInput = document.getElementById(`cloud-snippet-code-${snippetId}`) as HTMLInputElement
			const snippetCode = snippetCodeInput.value
			//Set the Snippet Name and Snippet Code in the Thickbox modal
			const snippetNameModalTag = document.getElementById('snippet-name-thickbox')
			const snippetCodeModalTag = document.getElementById('snippet-code-thickbox')
			snippetNameModalTag.textContent = snippetName
			const html = Prism.highlight(snippetCode, prisGrammar, prisLang)
			snippetCodeModalTag.innerHTML = html
		})
	})
}