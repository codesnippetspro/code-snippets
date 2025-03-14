<?php

namespace Code_Snippets;

use WP_Screen;

/**
 * This file holds all the content for the contextual help screens.
 *
 * @package Code_Snippets
 */
class Contextual_Help {

	/**
	 * Current screen object
	 *
	 * @see get_current_screen()
	 *
	 * @var WP_Screen
	 */
	public WP_Screen $screen;

	/**
	 * Name of current screen
	 *
	 * @see get_current_screen()
	 *
	 * @var string
	 */
	public string $screen_name;

	/**
	 * Class constructor
	 *
	 * @param string $screen_name Name of current screen.
	 */
	public function __construct( string $screen_name ) {
		$this->screen_name = $screen_name;
	}

	/**
	 * Load the contextual help
	 */
	public function load() {
		$this->screen = get_current_screen();

		switch ( $this->screen_name ) {
			case 'manage':
				$this->load_manage_help();
				break;

			case 'edit':
				$this->load_edit_help();
				break;

			case 'import':
				$this->load_import_help();
				break;
		}

		$this->load_help_sidebar();
	}

	/**
	 * Load the help sidebar
	 */
	private function load_help_sidebar() {
		$sidebar_links = [
			'https://wordpress.org/plugins/code-snippets'        => __( 'About Plugin', 'code-snippets' ),
			'https://help.codesnippets.pro/collection/3-faq'     => __( 'FAQ', 'code-snippets' ),
			'https://wordpress.org/support/plugin/code-snippets' => __( 'Support Forum', 'code-snippets' ),
			'https://codesnippets.pro'                           => __( 'Plugin Website', 'code-snippets' ),
		];

		$contents = '<p><strong>' . __( 'For more information:', 'code-snippets' ) . "</strong></p>\n";

		foreach ( $sidebar_links as $url => $label ) {
			$contents .= "\n" . sprintf( '<p><a href="%s">%s</a></p>', esc_url( $url ), esc_html( $label ) );
		}

		$this->screen->set_help_sidebar( wp_kses_post( $contents ) );
	}

	/**
	 * Add a help tab to the current screen.
	 *
	 * @param string               $id         Screen ID.
	 * @param string               $title      Screen title.
	 * @param string|array<string> $paragraphs List of paragraphs to display as content.
	 *
	 * @return void
	 */
	private function add_help_tab( string $id, string $title, $paragraphs ) {
		$this->screen->add_help_tab(
			array(
				'title'   => $title,
				'id'      => $id,
				'content' => wp_kses_post(
					implode(
						"\n",
						array_map(
							function ( $content ) {
								return '<p>' . $content . '</p>';
							},
							is_array( $paragraphs ) ? $paragraphs : [ $paragraphs ]
						)
					)
				),
			)
		);
	}

	/**
	 * Reusable introduction text
	 *
	 * @return string
	 */
	private function get_intro_text(): string {
		return __( 'Snippets are similar to plugins - they both extend and expand the functionality of WordPress. Snippets are more light-weight, just a few lines of code, and do not put as much load on your server. ', 'code-snippets' );
	}

	/**
	 * Register and handle the help tabs for the manage snippets admin page
	 */
	private function load_manage_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			$this->get_intro_text() .
			__( 'Here you can manage your existing snippets and perform tasks on them such as activating, deactivating, deleting and exporting.', 'code-snippets' )
		);

		$this->add_help_tab(
			'safe-mode',
			__( 'Safe Mode', 'code-snippets' ),
			[
				__( 'Be sure to check your snippets for errors before you activate them, as a faulty snippet could bring your whole blog down. If your site starts doing strange things, deactivate all your snippets and activate them one at a time.', 'code-snippets' ),
				__( "If something goes wrong with a snippet, and you can't use WordPress, you can cause all snippets to stop executing by turning on <strong>safe mode</strong>.", 'code-snippets' ),
				__( 'You can find out how to enable safe mode in the <a href="https://help.codesnippets.pro/article/12-safe-mode">Code Snippets Pro Docs</a>.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the single snippet admin page
	 */
	private function load_edit_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text() .
				__( 'Here you can add a new snippet, or edit an existing one.', 'code-snippets' ),
				__( "If you're not sure about the types of snippets you can add, take a look at the <a href=\"https://help.codesnippets.pro/collection/2-adding-snippets\">Code Snippets Pro Docs</a> for inspiration.", 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'adding',
			__( 'Adding Snippets', 'code-snippets' ),
			[
				__( 'You need to fill out the name and code fields for your snippet to be added. While the description field will add more information about how your snippet works, what is does and where you found it, it is completely optional.', 'code-snippets' ),
				__( 'Please be sure to check that your snippet is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straight away, it will help to minimise the chance of a faulty snippet becoming active on your site.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the import snippets admin page
	 */
	private function load_import_help() {
		$manage_url = code_snippets()->get_menu_url( 'manage' );

		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			$this->get_intro_text() .
			__( 'Here you can load snippets from a code snippets export file into the database alongside existing snippets.', 'code-snippets' )
		);

		$this->add_help_tab(
			'import',
			__( 'Importing', 'code-snippets' ),
			__( 'You can load your snippets from a code snippets export file using this page.', 'code-snippets' ) .
			/* translators: %s: URL to Snippets admin menu */
			sprintf( __( 'Imported snippets will be added to the database along with your existing snippets. Regardless of whether the snippets were active on the previous site, imported snippets are always inactive until activated using the <a href="%s">Manage Snippets</a> page.', 'code-snippets' ), $manage_url )
		);

		$this->add_help_tab(
			'export',
			__( 'Exporting', 'code-snippets' ),
			/* translators: %s: URL to Manage Snippets admin menu */
			sprintf( __( 'You can save your snippets to a code snippets export file using the <a href="%s">Manage Snippets</a> page.', 'code-snippets' ), $manage_url )
		);
	}
}
