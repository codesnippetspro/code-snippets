<?php

namespace Code_Snippets\Admin;

use WP_Screen;
use function Code_Snippets\code_snippets;

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

			case 'cloud-library':
				$this->load_cloud_library_help();
				break;

			case 'blueprints':
				$this->load_blueprints_help();
				break;

			case 'ai-agent':
				$this->load_ai_agent_help();
				break;

			case 'insights':
				$this->load_insights_help();
				break;

			case 'import':
				$this->load_import_help();
				break;

			case 'settings':
				$this->load_settings_help();
				break;

			case 'welcome':
				$this->load_welcome_help();
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
			'https://codesnippets.pro/docs/faq/'                 => __( 'FAQ', 'code-snippets' ),
			'https://wordpress.org/support/plugin/code-snippets' => __( 'Support Forum', 'code-snippets' ),
			'https://codesnippets.pro'                           => __( 'Plugin Website', 'code-snippets' ),
		];

		$allowed_html = [
			'p'      => [],
			'strong' => [],
			'a'      => [ 'href' => [] ],
		];

		$contents = sprintf( "<p><strong>%s</strong></p>\n", esc_html__( 'For more information:', 'code-snippets' ) );

		foreach ( $sidebar_links as $url => $label ) {
			$contents .= "\n" . sprintf( '<p><a href="%s">%s</a></p>', esc_url( $url ), esc_html( $label ) );
		}

		$this->screen->set_help_sidebar( wp_kses( $contents, $allowed_html ) );
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
			[
				$this->get_intro_text(),
				__( 'Here you can manage your existing snippets and perform tasks on them such as activating, deactivating, deleting and exporting.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'safe-mode',
			__( 'Safe Mode', 'code-snippets' ),
			[
				__( 'Be sure to check your snippets for errors before you activate them, as a faulty snippet could bring your whole blog down. If your site starts doing strange things, deactivate all your snippets and activate them one at a time.', 'code-snippets' ),
				__( "If something goes wrong with a snippet, and you can't use WordPress, you can cause all snippets to stop executing by turning on <strong>safe mode</strong>.", 'code-snippets' ),
				/* translators: %s: URL to Code Snippets Pro Docs */
				sprintf( __( 'You can find out how to enable safe mode in the <a href="%s">Code Snippets Pro Docs</a>.', 'code-snippets' ), 'https://codesnippets.pro/doc/safe-mode/' ),
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
				/* translators: %s: URL to Code Snippets Pro Docs */
				sprintf( __( "If you're not sure about the types of snippets you can add, take a look at the <a href=\"%s\">Code Snippets Pro Docs</a> for inspiration.", 'code-snippets' ), 'https://codesnippets.pro/docs/adding-snippets/' ),
			]
		);

		$this->add_help_tab(
			'adding',
			__( 'Adding Snippets', 'code-snippets' ),
			[
				__( 'You need to fill out the name and code fields for your snippet to be added. While the description field will add more information about how your snippet works, what is does and where you found it, it is completely optional.', 'code-snippets' ),
				__( 'Please be sure to check that your snippet is valid PHP code and will not produce errors before adding it through this page. While doing so will not become active straight away, it will help to minimize the chance of a faulty snippet becoming active on your site.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the Cloud Library demo page.
	 */
	private function load_cloud_library_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text(),
				__( 'Cloud Library lets you reuse snippets across the sites connected to your Code Snippets Cloud.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the Blueprints demo page.
	 */
	private function load_blueprints_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text(),
				__( 'Blueprints help you create snippets from templates for supported WordPress features.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'using-blueprints',
			__( 'Using Blueprints', 'code-snippets' ),
			[
				__( 'Blueprints provide guided forms for creating snippets from supported WordPress features.', 'code-snippets' ),
				__( 'Choose a blueprint, complete its fields and review the generated snippet before saving it.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'generating-snippets',
			__( 'Generating Snippets', 'code-snippets' ),
			[
				__( 'Code Snippets Pro combines your choices with the required boilerplate to generate a complete snippet.', 'code-snippets' ),
				__( 'Review the generated snippet before saving or activating it on your site.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the AI Agent demo page.
	 */
	private function load_ai_agent_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text(),
				__( 'The AI Agent can plan, build and refine snippets from prompts.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'writing-prompts',
			__( 'Writing Prompts', 'code-snippets' ),
			[
				__( 'Describe what you want your site to do in plain language, including any relevant details.', 'code-snippets' ),
				__( 'The AI Agent uses your prompt to prepare a plan before creating snippets.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'reviewing-plans',
			__( 'Reviewing Plans', 'code-snippets' ),
			[
				__( 'Review the plan to see what the AI Agent intends to build before it creates any snippets.', 'code-snippets' ),
				__( 'Accept the plan to continue, or ask for changes when it does not match your requirements.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'reviewing-snippets',
			__( 'Reviewing Snippets', 'code-snippets' ),
			[
				__( 'New snippets are added inactive, so you can review the code before it runs on your site.', 'code-snippets' ),
				__( 'Activate each snippet only after confirming that it does what you expect.', 'code-snippets' ),
			]
		);
	}

	/**
	 * Register and handle the help tabs for the Insights admin page.
	 */
	private function load_insights_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text() .
				__( 'Here you can view snippets statistics, and get insights how the snippets are being used on this website.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'insights',
			__( 'Insights', 'code-snippets' ),
			__( 'Get detailed information about your snippets usage, including which snippets types are being most used, their activation status, where they are located, and more.', 'code-snippets' )
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
			[
				$this->get_intro_text() .
				__( 'Here you can load snippets from a code snippets export file into the database alongside existing snippets.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'import',
			__( 'Importing', 'code-snippets' ),
			[
				__( 'You can load your snippets from a code snippets export file using this page.', 'code-snippets' ) .
				/* translators: %s: URL to Snippets admin menu */
				sprintf( __( 'Imported snippets will be added to the database along with your existing snippets. Regardless of whether the snippets were active on the previous site, imported snippets are always inactive until activated using the <a href="%s">Manage Snippets</a> page.', 'code-snippets' ), $manage_url ),
			]
		);

		$this->add_help_tab(
			'export',
			__( 'Exporting', 'code-snippets' ),
			/* translators: %s: URL to Manage Snippets admin menu */
			sprintf( __( 'You can save your snippets to a code snippets export file using the <a href="%s">Manage Snippets</a> page.', 'code-snippets' ), $manage_url )
		);

		$this->add_help_tab(
			'migrating',
			__( 'Migrating', 'code-snippets' ),
			__( 'If you are using another snippets plugin, you can import those existing snippets to your Code Snippets library.', 'code-snippets' )
		);
	}

	/**
	 * Register and handle the help tabs for the settings admin page.
	 */
	private function load_settings_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text() .
				__( 'Here you can configure how Code Snippets works on your site, including snippet editing, execution and display preferences.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'editing',
			__( 'Editing', 'code-snippets' ),
			__( 'Configure snippet fields and the code editor, including its theme, indentation and display preferences.', 'code-snippets' ),
		);

		$this->add_help_tab(
			'running',
			__( 'Running', 'code-snippets' ),
			__( 'Choose how snippets run and whether new snippets are activated when you save them.', 'code-snippets' ),
		);

		$this->add_help_tab(
			'library',
			__( 'Library', 'code-snippets' ),
			__( 'Manage snippet revisions, deleted snippets and cloud library connections.', 'code-snippets' ),
		);

		$this->add_help_tab(
			'interface',
			__( 'Interface', 'code-snippets' ),
			__( 'Configure the snippets list, code highlighting, the admin bar menu and upgrade notices.', 'code-snippets' ),
		);

		$this->add_help_tab(
			'advanced',
			__( 'Advanced', 'code-snippets' ),
			__( 'Control access, maintenance actions, version changes and complete uninstallation.', 'code-snippets' ),
		);
	}

	/**
	 * Register and handle the help tabs for the welcome admin page.
	 */
	private function load_welcome_help() {
		$this->add_help_tab(
			'overview',
			__( 'Overview', 'code-snippets' ),
			[
				$this->get_intro_text() .
				__( 'Here you can find the latest Code Snippets news and release updates, plus helpful articles and partner offers.', 'code-snippets' ),
			]
		);

		$this->add_help_tab(
			'whats-new',
			__( "What's New", 'code-snippets' ),
			[
				__( 'The Latest Changes section summarizes new features, improvements, bug fixes, security updates and other changes in recent releases.', 'code-snippets' ),
				__( 'Select View Changelog to read the complete release notes.', 'code-snippets' ),
			]
		);
	}
}
