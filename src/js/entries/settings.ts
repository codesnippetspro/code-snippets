import { Toolbar } from '../components/common/Toolbar'
import { handleEditorPreviewUpdates, handleSettingsTabs } from '../services/settings'
import { loadComponent } from '../utils/bootstrap'

loadComponent('code-snippets-toolbar-container', Toolbar)

handleSettingsTabs()
handleEditorPreviewUpdates()
