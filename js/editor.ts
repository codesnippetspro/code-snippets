import { defineMode, getMode, EditorConfiguration, ModeSpec } from 'codemirror'
import './php-lint'
import 'codemirror-colorpicker/dist/codemirror-colorpicker.js'

type ModeSpecOptions = {
	startOpen: boolean
}

/** Define a new mode which starts the phpmixed mode in php mode instead of html mode */
defineMode('php-snippet', (config: EditorConfiguration) =>
	getMode(config, <ModeSpec<ModeSpecOptions>> {
		name: 'application/x-httpd-php',
		startOpen: true
	})
)
