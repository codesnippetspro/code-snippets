import Prism from 'prismjs'
import 'prismjs/components/prism-css'
import 'prismjs/components/prism-php'
import 'prismjs/components/prism-markup'

//Handle clicks on snippet preview button 
export const handleShowCloudPreview = () => {
	const previewButtons = document.querySelectorAll('.cloud-snippet-preview')
	//Add click event listener to buttons
	previewButtons.forEach( button => {
		button.addEventListener('click', () => {
			//Get the snippet ID from the button's data attribute
			const snippetId = button.getAttribute('data-snippet')
			//Get the snippet language type 
			const snippetLanguage = button.getAttribute('data-lang') as string
			//Get the snippet name from input with ID cloud-snippet-name-<snippetId>
			const snippetNameInput = document.getElementById(`cloud-snippet-name-${snippetId}`) as HTMLInputElement
			const snippetName = snippetNameInput.value
			//Get the snippet code from input with ID cloud-snippet-code-<snippetId>
			const snippetCodeInput = document.getElementById(`cloud-snippet-code-${snippetId}`) as HTMLInputElement
			const snippetCode = snippetCodeInput.value
			//Set the Snippet Name and Snippet Code in the Thickbox modal
			const snippetNameModalTag = document.getElementById('snippet-name-thickbox') as HTMLElement
			const snippetCodeModalTag = document.getElementById('snippet-code-thickbox') as HTMLElement
			snippetNameModalTag.textContent = snippetName
			snippetCodeModalTag.classList.remove(...snippetCodeModalTag.classList)
			snippetCodeModalTag.classList.add(`language-${snippetLanguage}`)
			snippetCodeModalTag.innerHTML = snippetCode
			Prism.highlightElement(snippetCodeModalTag)
		})
	})

}