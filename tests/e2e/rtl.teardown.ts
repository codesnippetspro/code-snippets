import { test as teardown } from '@playwright/test'
import { wpCli } from './helpers/wpCli'

// Remove the right-to-left user the setup created, so the site is left as found.
teardown('remove the right-to-left user', async () => {
	try {
		await wpCli(['user', 'delete', 'rtl-admin', '--yes'])
	} catch {
		// Already gone, or never created because the setup failed early.
	}
})
