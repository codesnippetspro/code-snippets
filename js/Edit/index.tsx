import React from 'react'
import ReactDOM from 'react-dom'
import { EditForm } from './EditForm'

const container = document.getElementById('edit-snippet-form-container')

ReactDOM.render(
	<EditForm snippetId={Number(container?.getAttribute('data-snippet-id') ?? '0')} />,
	container
)
