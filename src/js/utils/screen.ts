export const isNetworkAdmin = (): boolean =>
	window.pagenow.endsWith('-network')

export const isMacOS = (): boolean =>
	null !== /mac/i.exec(window.navigator.userAgent)

export const isLicensed = (): boolean =>
	!!window.CODE_SNIPPETS?.isLicensed
