<?php

namespace Code_Snippets\Integration\Promotions\Other;

use Code_Snippets\UnitTestCase;

/**
 * Tests for the Elementor editor promotion.
 *
 * @group promotions
 */
class Elementor_Editor_Test extends UnitTestCase {

	/**
	 * Elementor hooks the promotion registers its controls on.
	 */
	private const PROMOTION_HOOKS = [
		'elementor/element/common/section_custom_css/after_section_start',
		'elementor/element/common/section_custom_css_pro/after_section_start',
	];

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		foreach ( self::PROMOTION_HOOKS as $hook ) {
			remove_all_actions( $hook );
		}

		parent::tear_down();
	}

	/**
	 * The promotion is left unregistered when the notice control type is absent.
	 *
	 * Elementor gained that control type in 3.19. Registering against an older
	 * version reads a constant that does not exist, and the resulting Error is
	 * raised while the editor builds its controls, leaving the editor unusable.
	 *
	 * Elementor is not installed for these tests, so the constant is genuinely
	 * missing here, which is the same condition an older Elementor presents.
	 *
	 * @return void
	 */
	public function test_promotion_is_skipped_when_the_notice_control_is_missing(): void {
		$promotion = new Elementor_Editor();

		$promotion->promotion_in_custom_css_section();

		foreach ( self::PROMOTION_HOOKS as $hook ) {
			$this->assertFalse(
				has_action( $hook ),
				"the promotion should not be registered on $hook without the notice control type"
			);
		}
	}

	/**
	 * The promotion is still registered on a version that offers the control type.
	 *
	 * @return void
	 */
	public function test_promotion_is_registered_when_the_notice_control_exists(): void {
		$promotion = new class() extends Elementor_Editor {
			/**
			 * Stand in for an Elementor new enough to offer the control type.
			 *
			 * @return bool
			 */
			protected function supports_notice_control(): bool {
				return true;
			}
		};

		$promotion->promotion_in_custom_css_section();

		foreach ( self::PROMOTION_HOOKS as $hook ) {
			$this->assertNotFalse(
				has_action( $hook ),
				"the promotion should still be registered on $hook when the control type exists"
			);
		}
	}
}
