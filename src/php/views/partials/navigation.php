<?php
/**
 * Partial for the main top navigation menu present across all pages.
 *
 * @package    Code_Snippets
 * @subpackage Views
 *
 * @var $this Admin_Menu
 */

namespace Code_Snippets;

$top_links = [
	'docs'    => [
		'url'      => 'https://help.codesnippets.pro/',
		'label'    => __( 'Docs', 'code-snippets' ),
		'external' => true,
	],
	'cloud'   => [
		'url'      => 'https://codesnippets.cloud/',
		'label'    => __( 'Cloud Dashboard', 'code-snippets' ),
		'external' => true,
	],
	'welcome' => [
		'url'   => code_snippets()->get_menu_url( 'welcome' ),
		'label' => __( "What's New", 'code-snippets' ),
	],
];

$lower_nav_links = [
	'snippets'  => [
		'url'    => code_snippets()->get_menu_url(),
		'label'  => __( 'Snippets', 'code-snippets' ),
		'active' => 'manage' === $this->name,
	],
	'library'   => [
		'url'   => false,
		'label' => __( 'My Library', 'code-snippets' ),
		'pro'   => true,
	],
	'community' => [
		'url'   => code_snippets()->get_menu_url( 'cloud' ),
		'label' => __( 'Community Cloud', 'code-snippets' ),
	],
	'teams'     => [
		'url'   => 'https://codesnippets.pro/pricing',
		'label' => __( 'My Teams', 'code-snippets' ),
		'pro'   => true,
	],
	'settings'  => [
		'url'    => code_snippets()->get_menu_url( 'settings' ),
		'label'  => __( 'Settings', 'code-snippets' ),
		'active' => 'settings' === $this->name,
	],
];


?>

<div class="code-snippets-navigation-top">
	<div class="logo">
		<img
			src="<?php echo esc_attr( plugins_url( 'assets/icon.svg', code_snippets()->file ) ); ?>"
			alt="<?php esc_attr_e( 'Code Snippets Logo', 'code-snippets' ); ?>"
		/>

		<h1><?php esc_html_e( 'Code Snippets', 'code-snippets' ); ?></h1>
	</div>

	<nav>
		<ul>
			<?php foreach ( $top_links as $key => $nav_link ) { ?>
				<li>
					<a
						class="<?php echo esc_attr( $key ); ?>-link"
						href="<?php echo esc_url( $nav_link['url'] ); ?>"
						<?php if ( ! empty( $nav_link['external'] ) ) { ?>
							target="_blank" rel="noopener noreferrer"
						<?php } ?>
					>
						<?php echo esc_html( $nav_link['label'] ); ?>
					</a>
				</li>
			<?php } ?>
			<li>
				<a
					class="button button-large button-secondary"
					href="https://codesnippets.pro/pricing/"
					target="_blank" rel="noopener noreferrer"
				>
					<?php esc_html_e( 'Upgrade to Pro', 'code-snippets' ); ?>
				</a>
			</li>
		</ul>
	</nav>
</div>
<div class="code-snippets-navigation-lower">
	<?php foreach ( $lower_nav_links as $key => $nav_link ) { ?>
		<a
			class="<?php echo esc_attr( $key ); ?>-link
			<?php echo esc_attr( ! empty( $nav_link['active'] ) ? 'active-link' : '' ); ?>"
			href="<?php echo esc_url( $nav_link['url'] ); ?>"

		>
			<?php include __DIR__ . "/icons/{$key}.svg"; ?>
			<span><?php echo esc_html( $nav_link['label'] ); ?></span>

			<?php if ( ! empty( $nav_link['pro'] ) ) { ?>
				<span class="pro-chip"><?php esc_html_e( 'Pro', 'code-snippets' ); ?></span>
			<?php } ?>
		</a>
	<?php } ?>
</div>
