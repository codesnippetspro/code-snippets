import React, { useEffect } from 'react'
import { SnippetForm } from './SnippetForm'

const EVENT_NAME = 'code_snippets_focus_editor'

interface EditMenuLinkBinding {
	menuLink: HTMLAnchorElement
	originalHref: string | undefined
	originalRole: string | undefined
	originalTabIndex: string | undefined
	handleClick: (event: MouseEvent) => void
	handleKeyDown: (event: KeyboardEvent) => void
}

const focusCodeEditor = () => {
	window.dispatchEvent(new CustomEvent(EVENT_NAME))
}

const restoreAttribute = (menuLink: HTMLAnchorElement, name: string, value: string | undefined) => {
	if (undefined !== value) {
		menuLink.setAttribute(name, value)
		return
	}

	menuLink.removeAttribute(name)
}

const getEditPage = (): string | undefined => {
	const editUrl = window.CODE_SNIPPETS?.urls.edit

	return editUrl ? new URL(editUrl, window.location.origin).searchParams.get('page') ?? undefined : undefined
}

const bindEditMenuLink = (menuLink: HTMLAnchorElement, page: string): EditMenuLinkBinding | undefined => {
	const menuUrl = new URL(menuLink.href, window.location.origin)

	if (page !== menuUrl.searchParams.get('page')) {
		return undefined
	}

	const handleClick = (event: MouseEvent) => {
		event.preventDefault()
		focusCodeEditor()
	}

	const handleKeyDown = (event: KeyboardEvent) => {
		if ('Enter' !== event.key && ' ' !== event.key) {
			return
		}

		event.preventDefault()
		focusCodeEditor()
	}

	const binding = {
		menuLink,
		originalHref: menuLink.getAttribute('href') ?? undefined,
		originalRole: menuLink.getAttribute('role') ?? undefined,
		originalTabIndex: menuLink.getAttribute('tabindex') ?? undefined,
		handleClick,
		handleKeyDown
	}

	menuLink.dataset.codeSnippetsDisabled = 'true'
	menuLink.setAttribute('role', 'button')
	menuLink.setAttribute('tabindex', '0')
	menuLink.classList.add('code-snippets-edit-menu-link')
	menuLink.removeAttribute('href')
	menuLink.addEventListener('click', handleClick)
	menuLink.addEventListener('keydown', handleKeyDown)

	return binding
}

const unbindEditMenuLink = ({
	menuLink,
	originalHref,
	originalRole,
	originalTabIndex,
	handleClick,
	handleKeyDown
}: EditMenuLinkBinding) => {
	menuLink.removeEventListener('click', handleClick)
	menuLink.removeEventListener('keydown', handleKeyDown)
	menuLink.classList.remove('code-snippets-edit-menu-link')
	delete menuLink.dataset.codeSnippetsDisabled

	restoreAttribute(menuLink, 'href', originalHref)
	restoreAttribute(menuLink, 'role', originalRole)
	restoreAttribute(menuLink, 'tabindex', originalTabIndex)
}

const useEditMenuLinkFocus = () => {
	useEffect(() => {
		const page = getEditPage()

		if (!page) {
			return
		}

		const editMenuLinks = Array.from(document.querySelectorAll<HTMLAnchorElement>('#adminmenu a[href]'))
			.flatMap(menuLink => {
				const binding = bindEditMenuLink(menuLink, page)

				return binding ? [binding] : []
			})

		return () => {
			editMenuLinks.forEach(unbindEditMenuLink)
		}
	}, [])
}

export const EditMenu = () => {
	useEditMenuLinkFocus()

	return <SnippetForm />
}
