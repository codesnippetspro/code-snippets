import { handleFormSubmitValidation } from './edit/validate'
import { handleSnippetTypeTabs } from './edit/tabs'
import { handleClipboardCopy } from './edit/clipboard'
import { handleContentShortcodeOptions } from './edit/shortcode'
import { loadSnippetCodeEditor } from './edit/editor'

loadSnippetCodeEditor()
handleSnippetTypeTabs()
handleContentShortcodeOptions()
handleFormSubmitValidation()
handleClipboardCopy()
