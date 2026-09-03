import { test as teardown } from '@playwright/test'
import { wpCli } from './helpers/wpCli'

// Remove the right-to-left user the setup created, so the site is left as found.
// Only a user that was never created is tolerated; a failed delete must fail here.
teardown('remove the right-to-left user', async () => {
	let exists = true

	try {
		await wpCli(['user', 'get', 'rtl-admin', '--field=ID'])
	} catch {
		exists = false
	}

	if (exists) {
		await wpCli(['user', 'delete', 'rtl-admin', '--yes'])
	}
})
