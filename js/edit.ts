import { handleFormSubmitValidation } from './edit/validate'
import { loadSnippetTagEditor } from './edit/tags'
import { handleSnippetTypeTabs } from './edit/tabs'
import { handleClipboardCopy } from './edit/clipboard'
import { handleContentShortcodeOptions } from './edit/shortcode'
import { loadSnippetCodeEditor } from './edit/editor'

loadSnippetCodeEditor()
loadSnippetTagEditor()

handleSnippetTypeTabs()
handleContentShortcodeOptions()
handleFormSubmitValidation()
handleClipboardCopy()
