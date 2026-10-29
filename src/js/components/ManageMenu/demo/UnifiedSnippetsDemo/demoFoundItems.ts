import { __ } from '@wordpress/i18n'
import type { DemoFoundItem, DemoSourceType } from './types'

/** The view-only item the walkthrough points at once the imports are done. */
export const FEATURED_VIEW_ONLY_KEY = 'htaccess-redirects'

const getBaseItem = (): DemoFoundItem => ({
	key: '',
	name: '',
	code: '',
	type: 'php',
	sourceType: 'theme',
	sourceName: '',
	sourcePath: '',
	isRunning: true,
	viewOnlyReason: ''
})

export const sourceLabel = (type: DemoSourceType): string => {
	switch (type) {
		case 'child-theme':
			return __('Child theme', 'code-snippets')
		case 'theme':
			return __('Theme', 'code-snippets')
		case 'plugin':
			return __('Plugins', 'code-snippets')
		case 'builder':
			return __('Page builders', 'code-snippets')
		case 'database':
			return __('Page content', 'code-snippets')
		case 'mu-plugin':
			return __('Must-use plugins', 'code-snippets')
		case 'customizer':
			return __('Customizer', 'code-snippets')
		case 'server':
			return __('Server configuration', 'code-snippets')
	}
}

const getDisableXmlRpcItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'disable-xmlrpc',
	name: __('Disable XML-RPC', 'code-snippets'),
	code: "add_filter( 'xmlrpc_enabled', '__return_false' );",
	type: 'php',
	sourceType: 'child-theme',
	sourceName: __('Storefront Child', 'code-snippets'),
	sourcePath: 'wp-content/themes/storefront-child/functions.php',
	importedId: 41
})

const getExcerptLengthItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'excerpt-length',
	name: __('Shorter post excerpts', 'code-snippets'),
	code: [
		"add_filter( 'excerpt_length', function () {",
		'\treturn 24;',
		'}, 999 );'
	].join('\n'),
	type: 'php',
	sourceType: 'theme',
	sourceName: __('Storefront', 'code-snippets'),
	sourcePath: 'wp-content/themes/storefront/functions.php',
	importedId: 42
})

const getHeaderAnalyticsItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'header-analytics',
	name: __('Analytics tracking tag', 'code-snippets'),
	code: [
		'window.dataLayer = window.dataLayer || [];',
		'function gtag() { dataLayer.push( arguments ); }',
		"gtag( 'js', new Date() );",
		"gtag( 'config', 'G-XXXXXXX' );"
	].join('\n'),
	type: 'js',
	sourceType: 'plugin',
	sourceName: __('Header and footer scripts plugin', 'code-snippets'),
	sourcePath: __('Plugin settings › Header scripts', 'code-snippets'),
	importedId: 43
})

const getNewsletterFormItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'newsletter-form',
	name: __('Newsletter sign-up form', 'code-snippets'),
	code: [
		'<form class="newsletter" action="/subscribe" method="post">',
		'\t<label for="newsletter-email">Get our weekly tips</label>',
		'\t<input id="newsletter-email" type="email" name="email" required>',
		'\t<button type="submit">Subscribe</button>',
		'</form>'
	].join('\n'),
	type: 'html',
	sourceType: 'builder',
	sourceName: __('Page builder HTML widget', 'code-snippets'),
	sourcePath: __('Page: Home › HTML widget', 'code-snippets'),
	importedId: 44
})

const getPromoBannerItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'promo-banner',
	name: __('Holiday promo banner', 'code-snippets'),
	code: '<div class="promo-banner">Free shipping on orders over $50 this week only.</div>',
	type: 'html',
	sourceType: 'database',
	sourceName: __('Custom HTML block', 'code-snippets'),
	sourcePath: __('Page: Shop › Custom HTML block', 'code-snippets'),
	isRunning: false,
	importedId: 45
})

const getAdditionalCssItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'additional-css',
	name: __('Additional CSS', 'code-snippets'),
	code: [
		'.site-header {',
		'\tborder-block-end: 3px solid #2271b1;',
		'}',
		'',
		'.woocommerce-store-notice {',
		'\tbackground: #1d2327;',
		'}'
	].join('\n'),
	type: 'css',
	sourceType: 'customizer',
	sourceName: __('Customizer', 'code-snippets'),
	sourcePath: __('Appearance › Customize › Additional CSS', 'code-snippets'),
	importedId: 46
})

const getHostCacheHelperItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: 'host-cache-helper',
	name: __('Hosting cache helper', 'code-snippets'),
	code: [
		'<?php',
		"add_action( 'send_headers', function () {",
		"\theader( 'X-Cache-Enabled: true' );",
		'} );',
		'?>',
		'<!-- cache: hit -->'
	].join('\n'),
	type: 'mixed',
	sourceType: 'mu-plugin',
	sourceName: __('Host cache', 'code-snippets'),
	sourcePath: 'wp-content/mu-plugins/host-cache.php',
	viewOnlyReason: __('Mixes PHP with page output, so it cannot run as a single snippet.', 'code-snippets')
})

const getRedirectRulesItem = (): DemoFoundItem => ({
	...getBaseItem(),
	key: FEATURED_VIEW_ONLY_KEY,
	name: __('Redirect rules', 'code-snippets'),
	code: [
		'RewriteEngine On',
		'RewriteCond %{HTTPS} off',
		'RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [L,R=301]'
	].join('\n'),
	type: 'config',
	sourceType: 'server',
	sourceName: __('Server configuration', 'code-snippets'),
	sourcePath: '.htaccess',
	viewOnlyReason: __('Read by the web server before WordPress loads, so a snippet cannot stand in for it.', 'code-snippets')
})

/**
 * Example finds, one or two from each place the real scan looks, listed in the
 * order a site owner would look: theme first, server configuration last.
 */
export const getDemoFoundItems = (): DemoFoundItem[] => [
	getDisableXmlRpcItem(),
	getExcerptLengthItem(),
	getHeaderAnalyticsItem(),
	getNewsletterFormItem(),
	getPromoBannerItem(),
	getAdditionalCssItem(),
	getHostCacheHelperItem(),
	getRedirectRulesItem()
]
