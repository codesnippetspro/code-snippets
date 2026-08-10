import { Toolbar } from '../components/common/Toolbar'
import { handleEditorPreviewUpdates, handleSettingsTabs, initVersionSwitch, relocateScreenMeta } from '../services/settings'
import { loadComponent } from '../utils/bootstrap'

loadComponent('code-snippets-toolbar-container', Toolbar)

handleSettingsTabs()
relocateScreenMeta()
handleEditorPreviewUpdates()
initVersionSwitch()
