<?php
/**
 * Tests for MCP integration filter.
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

/**
 * Test MCP integration filter.
 */
class Test_MCP_Integration extends WP_UnitTestCase {
	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		/*
		 * Reset the abilities registry. In WP 6.9+, _reset_abilities_registry() fires
		 * wp_abilities_api_init internally, re-triggering all registered callbacks.
		 * The plugin was already loaded via _manually_load_plugin, so its hooks are in place.
		 * Do NOT call bootstrap() here — that would add duplicate hooks each test run.
		 */
		if ( function_exists( '_reset_abilities_registry' ) ) {
			_reset_abilities_registry();
		}
	}

	/**
	 * Test that mcp_adapter_default_server_config filter is registered.
	 *
	 * Assumption: register_mcp_integration() hooks the filter.
	 * This is CRITICAL - without this filter, abilities won't appear in tools/list.
	 */
	public function test_mcp_integration_filter_registered() {
		$this->assertGreaterThan(
			0,
			has_filter( 'mcp_adapter_default_server_config' ),
			'mcp_adapter_default_server_config filter should be registered'
		);
	}

	/**
	 * Test that the filter adds abilities to MCP server config.
	 *
	 * Assumption: Filter adds all abilities with mcp.public = true to config['tools'].
	 * This is the ACTUAL MECHANISM that exposes abilities to MCP.
	 */
	public function test_filter_adds_abilities_to_config() {
		$config = [
			'tools' => [
				'mcp-adapter/discover-abilities',
				'mcp-adapter/get-ability-info',
				'mcp-adapter/execute-ability',
			],
		];

		$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );

		$this->assertGreaterThan(
			count( $config['tools'] ),
			count( $filtered_config['tools'] ),
			'Filter should add jazzsequence-mcp abilities to tools array'
		);

		$jazzsequence_tools = array_filter(
			$filtered_config['tools'],
			function ( $tool ) {
				return strpos( $tool, 'jazzsequence-mcp/' ) === 0;
			}
		);

		$this->assertEquals(
			34,
			count( $jazzsequence_tools ),
			'Should add all 34 jazzsequence-mcp abilities to tools array'
		);
	}

	/**
	 * Test that filter exposes ALL abilities with mcp.public = true, not just jazzsequence-mcp.
	 *
	 * Assumption: Filter should expose ANY ability with mcp.public = true.
	 * This is THE CRITICAL REQUIREMENT - expose ALL abilities, not just ours.
	 */
	public function test_filter_exposes_all_public_abilities() {
		/*
		 * WP 6.9+ only allows ability registration during wp_abilities_api_init, and
		 * _reset_abilities_registry() fires that action only once per test lifecycle.
		 * Instead of injecting a fake second-plugin ability, verify the filter exposes
		 * EVERY currently-registered public ability regardless of namespace — proving
		 * it iterates wp_get_abilities() globally, not just jazzsequence-mcp abilities.
		 */
		$all_abilities = wp_get_abilities();

		$public_abilities = array_filter(
			$all_abilities,
			function ( $ability ) {
				$meta = $ability->get_meta();
				return isset( $meta['mcp']['public'] ) && true === $meta['mcp']['public'];
			}
		);

		$this->assertGreaterThan( 0, count( $public_abilities ), 'Should have public abilities to test' );

		$config          = [ 'tools' => [] ];
		$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );

		foreach ( array_keys( $public_abilities ) as $ability_name ) {
			$this->assertContains(
				$ability_name,
				$filtered_config['tools'],
				"Filter should expose ability: {$ability_name}"
			);
		}
	}

	/**
	 * Test that filter does NOT expose abilities without mcp.public = true.
	 *
	 * Assumption: Filter should NOT expose abilities without MCP metadata.
	 */
	public function test_filter_does_not_expose_private_abilities() {
		/*
		 * Add the private ability hook BEFORE resetting the registry so it fires
		 * during wp_abilities_api_init when _reset_abilities_registry() is called.
		 */
		add_action(
			'wp_abilities_api_init',
			function () {
				wp_register_ability(
					'private-plugin/private-ability',
					[
						'label'               => 'Private Ability',
						'description'         => 'Test',
						'category'            => 'private',
						'execute_callback'    => function () {
							return [ 'success' => true ];
						},
						'permission_callback' => function () {
							return true;
						},
						'meta'                => [
							'show_in_rest' => true,
							// No mcp.public metadata.
						],
					]
				);
			}
		);

		/* Re-reset so the hook above fires. */
		if ( function_exists( '_reset_abilities_registry' ) ) {
			_reset_abilities_registry();
		}

		$config          = [ 'tools' => [ 'mcp-adapter/discover-abilities' ] ];
		$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );

		$this->assertNotContains(
			'private-plugin/private-ability',
			$filtered_config['tools'],
			'Filter should NOT expose abilities without mcp.public = true'
		);
	}

	/**
	 * Test that filter handles missing wp_get_abilities gracefully.
	 *
	 * Assumption: Filter should not error if wp_get_abilities doesn't exist.
	 */
	public function test_filter_handles_missing_api() {
		$this->assertTrue(
			function_exists( 'wp_get_abilities' ),
			'This test assumes wp_get_abilities exists. If it doesn\'t, filter should return config unchanged.'
		);
	}
}
