import { join } from 'path'

// The right-to-left specs sign in as an account of their own, so the rest of
// the suite never sees the site mirrored. These names are shared by the setup
// that creates the session and the teardown that removes the account.
export const RTL_LOCALE = 'he_IL'
export const RTL_USER = 'cs-e2e-rtl'

export const rtlAuthFile = join(__dirname, '..', '.auth', 'rtl-user.json')

// Records whether a run created the user, so the teardown only removes an
// account it made and never one that already existed on the site.
export const rtlCreatedMarker = join(__dirname, '..', '.auth', 'rtl-user-created')
