// `pagenow` is only defined inside wp-admin. This module is also reached from
// the admin bar bundle, which loads on the front end, so read it defensively.
export const isNetworkAdmin = (): boolean =>
	true === window.pagenow?.endsWith('-network')

export const isMacOS = (): boolean =>
	null !== /mac/i.exec(window.navigator.userAgent)

export const isLicensed = (): boolean =>
	!!window.CODE_SNIPPETS?.isLicensed

export const shouldShowUpsell = () =>
	!isLicensed() && !window.CODE_SNIPPETS?.hideUpsell
