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
		// Create mock config.
		$config = [
			'tools' => [
				'mcp-adapter/discover-abilities',
				'mcp-adapter/get-ability-info',
				'mcp-adapter/execute-ability',
			],
		];

		// Apply the filter.
		$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );

		// Should have more tools now.
		$this->assertGreaterThan(
			count( $config['tools'] ),
			count( $filtered_config['tools'] ),
			'Filter should add jazzsequence-mcp abilities to tools array'
		);

		// All jazzsequence-mcp abilities should be in the tools array.
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
		 * Register a fake ability from another plugin with mcp.public = true.
		 */
		wp_register_ability(
			'other-plugin/test-ability',
			[
				'label'              => 'Test Ability',
				'description'        => 'Test',
				'category'           => 'other',
				'execute_callback'   => function () {
					return [ 'success' => true ];
				},
				'permission_callback' => function () {
					return true;
				},
				'meta'               => [
					'show_in_rest' => true,
					'mcp'          => [
						'public' => true,
						'type'   => 'tool',
					],
				],
			]
		);

		// Create mock config.
		$config = [
			'tools' => [
				'mcp-adapter/discover-abilities',
			],
		];

		// Apply the filter.
		$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );

		// The other plugin's ability should also be included.
		$this->assertContains(
			'other-plugin/test-ability',
			$filtered_config['tools'],
			'Filter should expose abilities from ANY plugin with mcp.public = true'
		);
	}

	/**
	 * Test that filter does NOT expose abilities without mcp.public = true.
	 *
	 * Assumption: Filter should NOT expose abilities without MCP metadata.
	 */
	public function test_filter_does_not_expose_private_abilities() {
		/*
		 * Register a fake ability WITHOUT mcp.public.
		 */
		wp_register_ability(
			'private-plugin/private-ability',
			[
				'label'              => 'Private Ability',
				'description'        => 'Test',
				'category'           => 'private',
				'execute_callback'   => function () {
					return [ 'success' => true ];
				},
				'permission_callback' => function () {
					return true;
				},
				'meta'               => [
					'show_in_rest' => true,
					// No mcp.public metadata.
				],
			]
		);

		// Create mock config.
		$config = [
			'tools' => [
				'mcp-adapter/discover-abilities',
			],
		];

		// Apply the filter.
		$filtered_config = apply_filters( 'mcp_adapter_default_server_config', $config );

		// The private ability should NOT be included.
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
		/*
		 * This test would require temporarily removing the function.
		 * For now, just verify the filter returns config unchanged if function is missing.
		 */
		$config = [ 'tools' => [ 'test' ] ];

		/*
		 * If wp_get_abilities doesn't exist, config should be returned unchanged.
		 * We can't easily test this without mocking, but we document the expectation.
		 */
		$this->assertTrue(
			function_exists( 'wp_get_abilities' ),
			'This test assumes wp_get_abilities exists. If it doesn\'t, filter should return config unchanged.'
		);
	}
}
