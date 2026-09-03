import { test as teardown } from '@playwright/test'
import { wpCli } from './helpers/wpCli'

// Put the test user back on the site's own locale, whatever the RTL specs did.
teardown('restore the test user locale', async () => {
	try {
		await wpCli(['user', 'update', 'admin', '--locale='])
		await wpCli(['user', 'meta', 'delete', 'admin', 'locale'])
	} catch {
		// The meta may already be gone; nothing else to restore.
	}
})
