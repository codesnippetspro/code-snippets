import { Toolbar } from '../components/common/Toolbar'
import { TokenSettings } from '../components/Settings/TokenSettings'
import { handleEditorPreviewUpdates, handleSettingsTabs, initVersionSwitch } from '../services/settings'
import { loadComponent } from '../utils/bootstrap'

loadComponent('code-snippets-toolbar-container', Toolbar)
loadComponent('code-snippets-token-settings', TokenSettings)

handleSettingsTabs()
handleEditorPreviewUpdates()
initVersionSwitch()
