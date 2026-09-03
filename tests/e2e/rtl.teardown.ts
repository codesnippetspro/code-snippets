import { existsSync, readFileSync, unlinkSync } from 'fs'
import { test as teardown } from '@playwright/test'
import { RTL_USER, rtlCreatedMarker } from './helpers/rtlUser'
import { wpCli } from './helpers/wpCli'

// Remove the right-to-left user only if this run created it; an account that
// already existed on the site is left alone. A failed delete fails the teardown.
teardown('remove the right-to-left user', async () => {
	if (!existsSync(rtlCreatedMarker)) {
		return
	}

	const created = 'created' === readFileSync(rtlCreatedMarker, 'utf8').trim()
	unlinkSync(rtlCreatedMarker)

	if (created) {
		await wpCli(['user', 'delete', RTL_USER, '--yes'])
	}
})
