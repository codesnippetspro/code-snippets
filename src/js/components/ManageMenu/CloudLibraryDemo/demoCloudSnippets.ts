import { __ } from '@wordpress/i18n'
import { CloudStatus } from '../../../types/schema/CloudSnippetSchema'
import type { CloudSnippetSchema } from '../../../types/schema/CloudSnippetSchema'

/** The snippet the walkthrough previews, downloads, and then shows synced. */
export const FEATURED_SNIPPET_ID = 4821

const CODEVAULT = __('My Code Vault', 'code-snippets')

const FEATURED_CODE = `add_filter( 'wp_mail_from_name', function () {
	return get_bloginfo( 'name' );
} );

add_filter( 'wp_mail_from', function ( $from ) {
	$domain = wp_parse_url( home_url(), PHP_URL_HOST );

	return $domain ? 'no-reply@' . str_ireplace( 'www.', '', $domain ) : $from;
} );`

const base = (snippet: Partial<CloudSnippetSchema>): CloudSnippetSchema => ({
	id: 0,
	slug: '',
	name: '',
	description: '',
	code: '',
	tags: [],
	scope: 'global',
	codevault: CODEVAULT,
	total_votes: 0,
	vote_count: 0,
	wp_tested: '6.8',
	status: CloudStatus.Private,
	created: '2026-05-04T09:12:00Z',
	updated: '2026-07-22T14:38:00Z',
	revision: 1,
	is_owner: true,
	local_id: null,
	update_available: false,
	...snippet
})

/**
 * A small cloud library, deliberately short: the walkthrough scrolls past it to
 * the closing panel, so a full page of rows would bury the ending.
 */
// eslint-disable-next-line max-lines-per-function -- one library of example content.
export const getDemoCloudSnippets = (): CloudSnippetSchema[] => [
	base({
		id: FEATURED_SNIPPET_ID,
		slug: 'sender-name-and-address',
		name: __('Fix outgoing email sender name', 'code-snippets'),
		description: __('Sends site email from the site name and a no-reply address on your own domain, instead of “WordPress”.', 'code-snippets'),
		code: FEATURED_CODE,
		tags: ['email', 'admin'],
		scope: 'global',
		status: CloudStatus.Pro_Verified,
		updated: '2026-08-11T10:04:00Z'
	}),
	base({
		id: 4788,
		slug: 'disable-comments-everywhere',
		name: __('Disable comments everywhere', 'code-snippets'),
		description: __('Closes comments on every post type and hides the comment menus from the admin.', 'code-snippets'),
		code: "add_filter( 'comments_open', '__return_false', 20, 2 );",
		tags: ['comments', 'cleanup'],
		scope: 'global',
		status: CloudStatus.Private,
		updated: '2026-07-02T16:20:00Z'
	}),
	base({
		id: 4712,
		slug: 'checkout-field-styles',
		name: __('Checkout field styles', 'code-snippets'),
		description: __('Tidies the spacing and focus states on the checkout form fields.', 'code-snippets'),
		code: [
			'.woocommerce-checkout .form-row input:focus {',
			'\toutline: 2px solid #2271b1;',
			'}'
		].join('\n'),
		tags: ['woocommerce', 'styles'],
		scope: 'site-css',
		status: CloudStatus.Public,
		updated: '2026-06-18T08:55:00Z'
	}),
	base({
		id: 4655,
		slug: 'lazy-load-embeds',
		name: __('Lazy load video embeds', 'code-snippets'),
		description: __('Defers YouTube and Vimeo iframes until they are scrolled into view.', 'code-snippets'),
		code: [
			"add_filter( 'embed_oembed_html', function ( $html ) {",
			"\treturn str_replace( '<iframe', '<iframe loading=\"lazy\"', $html );",
			'} );'
		].join('\n'),
		tags: ['performance'],
		scope: 'front-end',
		status: CloudStatus.AI_Verified,
		updated: '2026-05-29T11:47:00Z'
	})
]
