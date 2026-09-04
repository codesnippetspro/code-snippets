import { __, sprintf } from '@wordpress/i18n'
import type { DemoPlan, DemoSnippet } from './types'

/**
 * The starter prompts the real agent offers, by their chip labels. The demo
 * never sends one — they are here so the page reads as the agent's own.
 */
export const DEMO_EXAMPLES: readonly string[] = [
	__('Replace a plugin with snippets', 'code-snippets'),
	__('Speed up my site', 'code-snippets'),
	__('Add a feature', 'code-snippets'),
	__('Fix a problem', 'code-snippets'),
	__('Customize appearance', 'code-snippets')
]

export const DEMO_PROMPT = __('Add a dismissible welcome banner to the top of my site.', 'code-snippets')

export const DEMO_PLAN_REPLY = __('Here is what I would build. Two snippets keep the markup and the styling separate, so you can restyle the banner later without touching the PHP.', 'code-snippets')

export const DEMO_PLAN: DemoPlan = {
	title: __('Dismissible welcome banner', 'code-snippets'),
	summary: DEMO_PLAN_REPLY,
	parts: [
		{
			language: 'php',
			name: __('Welcome banner', 'code-snippets'),
			description: __('Prints the banner at the top of every front-end page and remembers when a visitor dismisses it.', 'code-snippets')
		},
		{
			language: 'css',
			name: __('Welcome banner styles', 'code-snippets'),
			description: __('Styles the banner bar, its message, and the dismiss button.', 'code-snippets')
		}
	]
}

/**
 * Fallback used when the site has no name set, so the refinement still reads as
 * a sentence rather than trailing off into an empty quotation.
 */
const FALLBACK_SITE_NAME = __('our site', 'code-snippets')

export const getSiteName = (): string => {
	const siteName = window.CODE_SNIPPETS_MANAGE?.aiDemo?.siteName.trim()
	return undefined === siteName || '' === siteName ? FALLBACK_SITE_NAME : siteName
}

export const getRefinementPrompt = (siteName: string): string =>
	sprintf(
		/* translators: %s: name of the current site. */
		__('Make the banner say “Welcome to %s” and give it our brand colours.', 'code-snippets'),
		siteName
	)

export const DEMO_REFINEMENT_REPLY = __('Done — the banner now greets visitors by name and uses a warmer gradient.', 'code-snippets')

/**
 * Escape a value for embedding inside a single-quoted PHP string literal.
 */
const escapePhpString = (value: string): string =>
	value.replace(/\\/g, '\\\\').replace(/'/g, "\\'")

const bannerPhp = (greeting: string) => `add_action( 'wp_body_open', function () {
	if ( is_admin() || isset( $_COOKIE['cs_welcome_banner_dismissed'] ) ) {
		return;
	}

	printf(
		'<div class="cs-welcome-banner" role="status">
			<p class="cs-welcome-banner__message">%s</p>
			<button type="button" class="cs-welcome-banner__dismiss" aria-label="%s">&times;</button>
		</div>',
		esc_html( '${escapePhpString(greeting)}' ),
		esc_attr__( 'Dismiss this message', 'default' )
	);
} );

add_action( 'wp_footer', function () {
	?>
	<script>
		document.querySelector( '.cs-welcome-banner__dismiss' )?.addEventListener( 'click', function () {
			document.cookie = 'cs_welcome_banner_dismissed=1; path=/; max-age=2592000';
			this.closest( '.cs-welcome-banner' ).remove();
		} );
	</script>
	<?php
} );`

const BANNER_CSS_PLAIN = `.cs-welcome-banner {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 1rem;
	padding-block: 0.75rem;
	padding-inline: 1.5rem;
	background: #2271b1;
	color: #fff;
	font-size: 0.95rem;
}

.cs-welcome-banner__message {
	margin: 0;
}

.cs-welcome-banner__dismiss {
	background: none;
	border: 0;
	color: inherit;
	cursor: pointer;
	font-size: 1.25rem;
	line-height: 1;
	padding: 0;
}`

const BANNER_CSS_REFINED = `.cs-welcome-banner {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 1rem;
	padding-block: 0.9rem;
	padding-inline: 1.5rem;
	background: linear-gradient(90deg, #1d3c78 0%, #2271b1 55%, #3fa8d4 100%);
	color: #fff;
	font-size: 1rem;
	letter-spacing: 0.01em;
	text-align: center;
}

.cs-welcome-banner__message {
	margin: 0;
	font-weight: 600;
}

.cs-welcome-banner__dismiss {
	background: none;
	border: 0;
	color: inherit;
	cursor: pointer;
	font-size: 1.4rem;
	line-height: 1;
	padding: 0;
	opacity: 0.8;
	transition: opacity 0.15s ease;
}

.cs-welcome-banner__dismiss:hover {
	opacity: 1;
}`

const snippetDesc = (summary: string): string =>
	sprintf(
		/* translators: %s: description of what the snippet does. */
		__('%s Created by the Code Snippets AI Agent demo, and left inactive so you can review it before switching it on.', 'code-snippets'),
		summary
	)

export const getDraftSnippets = (): DemoSnippet[] => [
	{
		key: 'banner-php',
		name: __('Welcome banner', 'code-snippets'),
		desc: snippetDesc(__('Shows a dismissible welcome banner at the top of the site.', 'code-snippets')),
		language: 'php',
		code: bannerPhp(__('Welcome!', 'code-snippets'))
	},
	{
		key: 'banner-css',
		name: __('Welcome banner styles', 'code-snippets'),
		desc: snippetDesc(__('Styles the welcome banner.', 'code-snippets')),
		language: 'css',
		code: BANNER_CSS_PLAIN
	}
]

export const getRefinedSnippets = (siteName: string): DemoSnippet[] => {
	const greeting = sprintf(
		/* translators: %s: name of the current site. */
		__('Welcome to %s', 'code-snippets'),
		siteName
	)

	return [
		{
			...getDraftSnippets()[0],
			code: bannerPhp(greeting)
		},
		{
			...getDraftSnippets()[1],
			code: BANNER_CSS_REFINED
		}
	]
}
