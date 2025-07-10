import React from 'react'
import { createRoot } from 'react-dom/client'
import { SnippetForm } from './components/SnippetForm'

const container = document.getElementById('edit-snippet-form-container')

if (container) {
	const root = createRoot(container)
	root.render(<SnippetForm />)
} else {
	console.error('Could not find snippet edit form container.')
}
