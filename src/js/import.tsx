import React from 'react'
import { createRoot } from 'react-dom/client'
import { ImportApp } from './components/Import/ImportApp'

const importContainer = document.getElementById('import-container')

if (importContainer) {
	const root = createRoot(importContainer)
	root.render(<ImportApp />)
} else {
	console.error('Could not find import container.')
}
