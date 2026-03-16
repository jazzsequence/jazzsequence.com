<?php
/**
 * Tests for ability registration.
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

/**
 * Test ability registration.
 */
class Test_Ability_Registration extends WP_UnitTestCase {
	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Reset abilities registry before each test.
		if ( function_exists( '_reset_abilities_registry' ) ) {
			_reset_abilities_registry();
		}

		// Trigger bootstrap.
		if ( function_exists( 'JazzSequence\MCP_Abilities\bootstrap' ) ) {
			JazzSequence\MCP_Abilities\bootstrap();
		}
	}

	/**
	 * Test that ability category is registered.
	 *
	 * Assumption: register_ability_category() registers the jazzsequence-mcp category.
	 */
	public function test_ability_category_registered() {
		global $_wp_ability_categories_registry;

		$this->assertArrayHasKey(
			'jazzsequence-mcp',
			$_wp_ability_categories_registry,
			'jazzsequence-mcp ability category should be registered'
		);

		$category = $_wp_ability_categories_registry['jazzsequence-mcp'];
		$this->assertArrayHasKey( 'label', $category, 'Category should have label' );
		$this->assertArrayHasKey( 'description', $category, 'Category should have description' );
	}

	/**
	 * Test that discovery abilities are registered.
	 *
	 * Assumption: register_discovery_abilities() registers all 13 discovery abilities.
	 */
	public function test_discovery_abilities_registered() {
		$abilities = wp_get_abilities();

		$expected_abilities = [
			'jazzsequence-mcp/discover-post-types',
			'jazzsequence-mcp/discover-taxonomies',
			'jazzsequence-mcp/discover-plugins',
			'jazzsequence-mcp/discover-theme-structure',
			'jazzsequence-mcp/discover-custom-fields',
			'jazzsequence-mcp/discover-menus',
			'jazzsequence-mcp/discover-shortcodes',
			'jazzsequence-mcp/discover-blocks',
			'jazzsequence-mcp/discover-hooks',
			'jazzsequence-mcp/discover-options',
			'jazzsequence-mcp/discover-rewrite-rules',
			'jazzsequence-mcp/discover-capabilities',
			'jazzsequence-mcp/discover-cron-jobs',
		];

		foreach ( $expected_abilities as $ability_name ) {
			$this->assertArrayHasKey(
				$ability_name,
				$abilities,
				"Ability {$ability_name} should be registered"
			);
		}
	}

	/**
	 * Test that abilities have MCP metadata.
	 *
	 * Assumption: All abilities have meta.mcp.public = true.
	 * This is the CRITICAL test - this is what makes abilities visible to MCP.
	 */
	public function test_abilities_have_mcp_metadata() {
		$abilities = wp_get_abilities();

		$jazzsequence_abilities = array_filter(
			$abilities,
			function ( $ability ) {
				return strpos( $ability->name, 'jazzsequence-mcp/' ) === 0;
			}
		);

		$this->assertGreaterThan(
			0,
			count( $jazzsequence_abilities ),
			'Should have registered at least one jazzsequence-mcp ability'
		);

		foreach ( $jazzsequence_abilities as $ability_name => $ability ) {
			$meta = $ability->get_meta();

			$this->assertArrayHasKey(
				'mcp',
				$meta,
				"Ability {$ability_name} should have 'mcp' metadata"
			);

			$this->assertArrayHasKey(
				'public',
				$meta['mcp'],
				"Ability {$ability_name} should have 'mcp.public' metadata"
			);

			$this->assertTrue(
				$meta['mcp']['public'],
				"Ability {$ability_name} should have mcp.public = true"
			);

			$this->assertArrayHasKey(
				'type',
				$meta['mcp'],
				"Ability {$ability_name} should have 'mcp.type' metadata"
			);

			$this->assertEquals(
				'tool',
				$meta['mcp']['type'],
				"Ability {$ability_name} should have mcp.type = 'tool'"
			);
		}
	}

	/**
	 * Test that all registered abilities have show_in_rest metadata.
	 *
	 * Assumption: All abilities should be accessible via REST API.
	 */
	public function test_abilities_have_show_in_rest() {
		$abilities = wp_get_abilities();

		$jazzsequence_abilities = array_filter(
			$abilities,
			function ( $ability ) {
				return strpos( $ability->name, 'jazzsequence-mcp/' ) === 0;
			}
		);

		foreach ( $jazzsequence_abilities as $ability_name => $ability ) {
			$meta = $ability->get_meta();

			$this->assertArrayHasKey(
				'show_in_rest',
				$meta,
				"Ability {$ability_name} should have 'show_in_rest' metadata"
			);

			$this->assertTrue(
				$meta['show_in_rest'],
				"Ability {$ability_name} should have show_in_rest = true"
			);
		}
	}

	/**
	 * Test expected total count of abilities.
	 *
	 * Assumption: Plugin should register exactly 34 abilities total.
	 */
	public function test_correct_number_of_abilities_registered() {
		$abilities = wp_get_abilities();

		$jazzsequence_abilities = array_filter(
			$abilities,
			function ( $ability ) {
				return strpos( $ability->name, 'jazzsequence-mcp/' ) === 0;
			}
		);

		$this->assertEquals(
			34,
			count( $jazzsequence_abilities ),
			'Should register exactly 34 jazzsequence-mcp abilities'
		);
	}
}
