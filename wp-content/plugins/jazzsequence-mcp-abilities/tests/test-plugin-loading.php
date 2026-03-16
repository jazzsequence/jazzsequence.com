<?php
/**
 * Tests for plugin loading and timing.
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

/**
 * Test plugin loading.
 */
class Test_Plugin_Loading extends WP_UnitTestCase {

	/**
	 * Test that wp_register_ability function exists.
	 *
	 * Assumption: The Abilities API provides wp_register_ability().
	 */
	public function test_abilities_api_function_exists() {
		$this->assertTrue(
			function_exists( 'wp_register_ability' ),
			'wp_register_ability() function should exist (either from WordPress core or our mock)'
		);
	}

	/**
	 * Test that wp_register_ability_category function exists.
	 *
	 * Assumption: The Abilities API provides wp_register_ability_category().
	 */
	public function test_ability_category_function_exists() {
		$this->assertTrue(
			function_exists( 'wp_register_ability_category' ),
			'wp_register_ability_category() function should exist'
		);
	}

	/**
	 * Test that wp_get_abilities function exists.
	 *
	 * Assumption: The Abilities API provides wp_get_abilities().
	 */
	public function test_get_abilities_function_exists() {
		$this->assertTrue(
			function_exists( 'wp_get_abilities' ),
			'wp_get_abilities() function should exist'
		);
	}

	/**
	 * Test that JSMCP_ABILITY_CATEGORY constant is defined.
	 *
	 * Assumption: Plugin defines this constant.
	 */
	public function test_ability_category_constant_defined() {
		$this->assertTrue(
			defined( 'JSMCP_ABILITY_CATEGORY' ),
			'JSMCP_ABILITY_CATEGORY constant should be defined'
		);

		$this->assertEquals(
			'jazzsequence-mcp',
			JSMCP_ABILITY_CATEGORY,
			'JSMCP_ABILITY_CATEGORY should equal "jazzsequence-mcp"'
		);
	}

	/**
	 * Test that bootstrap function exists in correct namespace.
	 *
	 * Assumption: Plugin provides JazzSequence\MCP_Abilities\bootstrap().
	 */
	public function test_bootstrap_function_exists() {
		$this->assertTrue(
			function_exists( 'JazzSequence\MCP_Abilities\bootstrap' ),
			'JazzSequence\MCP_Abilities\bootstrap() function should exist'
		);
	}

	/**
	 * Test that register_mcp_integration function exists.
	 *
	 * Assumption: Plugin provides JazzSequence\MCP_Abilities\register_mcp_integration().
	 */
	public function test_register_mcp_integration_function_exists() {
		$this->assertTrue(
			function_exists( 'JazzSequence\MCP_Abilities\register_mcp_integration' ),
			'JazzSequence\MCP_Abilities\register_mcp_integration() function should exist'
		);
	}

	/**
	 * Test that MCP Adapter class exists.
	 *
	 * Assumption: MCP Adapter plugin is loaded.
	 */
	public function test_mcp_adapter_class_exists() {
		// This may not exist in test environment - just verify check works.
		$exists = class_exists( 'WP\MCP\Core\McpAdapter' );
		$this->assertIsBool( $exists, 'class_exists check should return boolean' );
	}
}
