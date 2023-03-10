const COPY_ICON = 'dashicon-clipboard'
const SUCCESS_ICON = 'dashicon-yes'
const TIMEOUT = 3000

export const handleClipboardCopy = () => {
	for (const button of document.querySelectorAll<HTMLElement>('.code-snippets-copy-text')) {
		if (navigator.clipboard) {
			button.addEventListener('click', event => {
				event.preventDefault()
				const text = button.getAttribute('data-text')
				if (text) {
					navigator.clipboard.writeText(text)
						.then(() => {
							button.classList.replace(COPY_ICON, SUCCESS_ICON)
							setTimeout(() => button.classList.replace(SUCCESS_ICON, COPY_ICON), TIMEOUT)
						})
						.catch(error => console.error(error))
				}
			})
		} else {
			button.style.display = 'none'
		}
	}
}
