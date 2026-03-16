<?php
/**
 * Tests for ability input_schema property definitions.
 *
 * Validates that all abilities expose correct, non-empty input schemas so that
 * MCP clients (e.g. Claude Code) can construct valid tool calls without workarounds.
 *
 * Regression: abilities previously had 'properties' => [] (empty PHP indexed array)
 * which JSON-encodes as "[]" instead of "{}". Claude Code silently rejects tools
 * whose inputSchema properties is an array rather than an object, resulting in
 * 0 capabilities being registered.
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

/**
 * Test ability input_schema definitions.
 */
class Test_Input_Schemas extends WP_UnitTestCase {

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( function_exists( '_reset_abilities_registry' ) ) {
			_reset_abilities_registry();
		}

		if ( function_exists( 'JazzSequence\MCP_Abilities\bootstrap' ) ) {
			JazzSequence\MCP_Abilities\bootstrap();
		}
	}

	/**
	 * Helper: get the input_schema for a registered ability.
	 *
	 * @param string $ability_name Full ability name.
	 * @return array|null
	 */
	private function get_input_schema( string $ability_name ): ?array {
		$abilities = wp_get_abilities();

		if ( ! isset( $abilities[ $ability_name ] ) ) {
			return null;
		}

		$ability = $abilities[ $ability_name ];

		// Support both direct property access and getter methods.
		if ( isset( $ability->input_schema ) ) {
			return (array) $ability->input_schema;
		}

		if ( method_exists( $ability, 'get_input_schema' ) ) {
			return (array) $ability->get_input_schema();
		}

		return null;
	}

	/**
	 * Assert that an ability's properties JSON-encodes as an object, not an array.
	 *
	 * This is the core regression check. PHP's json_encode( [] ) produces "[]"
	 * but json_encode( ['key' => 'val'] ) produces "{"key":"val"}".
	 * An empty indexed array [] also produces "[]" which fails MCP schema validation.
	 *
	 * @param array  $schema       The input_schema array.
	 * @param string $ability_name For assertion messages.
	 */
	private function assertPropertiesIsObject( array $schema, string $ability_name ): void {
		$this->assertArrayHasKey(
			'properties',
			$schema,
			"{$ability_name}: input_schema must have a 'properties' key"
		);

		$encoded = json_encode( $schema['properties'] );

		$this->assertStringStartsWith(
			'{',
			$encoded,
			"{$ability_name}: properties must JSON-encode as an object '{}', got '{$encoded}'. " .
			"An empty PHP array [] always encodes as '[]' which Claude Code rejects."
		);
	}

	/**
	 * Assert that specific properties exist in a schema.
	 *
	 * @param array  $schema       The input_schema array.
	 * @param array  $expected     Expected property names.
	 * @param string $ability_name For assertion messages.
	 */
	private function assertHasProperties( array $schema, array $expected, string $ability_name ): void {
		foreach ( $expected as $prop ) {
			$this->assertArrayHasKey(
				$prop,
				$schema['properties'],
				"{$ability_name}: input_schema must define property '{$prop}'"
			);
		}
	}

	/**
	 * Assert that specific properties are listed as required.
	 *
	 * @param array  $schema       The input_schema array.
	 * @param array  $expected     Expected required property names.
	 * @param string $ability_name For assertion messages.
	 */
	private function assertRequiredProperties( array $schema, array $expected, string $ability_name ): void {
		$this->assertArrayHasKey(
			'required',
			$schema,
			"{$ability_name}: input_schema must have a 'required' array"
		);

		foreach ( $expected as $prop ) {
			$this->assertContains(
				$prop,
				$schema['required'],
				"{$ability_name}: '{$prop}' must be listed as required"
			);
		}
	}

	// -------------------------------------------------------------------------
	// Content abilities
	// -------------------------------------------------------------------------

	/**
	 * Test create-post input schema.
	 */
	public function test_create_post_schema() {
		$ability = 'jazzsequence-mcp/create-post';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'post_title', 'post_content', 'post_status', 'post_type', 'meta_input', 'tax_input' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'post_title' ], $ability );
	}

	/**
	 * Test update-post input schema.
	 */
	public function test_update_post_schema() {
		$ability = 'jazzsequence-mcp/update-post';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'ID', 'post_title', 'post_content', 'post_status', 'meta_input', 'tax_input' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'ID' ], $ability );
	}

	/**
	 * Test delete-post input schema.
	 */
	public function test_delete_post_schema() {
		$ability = 'jazzsequence-mcp/delete-post';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'ID', 'force_delete' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'ID' ], $ability );
	}

	/**
	 * Test get-post input schema.
	 */
	public function test_get_post_schema() {
		$ability = 'jazzsequence-mcp/get-post';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'ID' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'ID' ], $ability );
	}

	/**
	 * Test query-posts input schema.
	 */
	public function test_query_posts_schema() {
		$ability = 'jazzsequence-mcp/query-posts';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'post_type', 'posts_per_page', 'paged', 'post_status', 'orderby', 'order', 's' ], $ability );
	}

	// -------------------------------------------------------------------------
	// Media abilities
	// -------------------------------------------------------------------------

	/**
	 * Test upload-media-url input schema.
	 */
	public function test_upload_media_url_schema() {
		$ability = 'jazzsequence-mcp/upload-media-url';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'url', 'title', 'alt', 'description', 'filename' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'url' ], $ability );
	}

	/**
	 * Test upload-media-base64 input schema.
	 */
	public function test_upload_media_base64_schema() {
		$ability = 'jazzsequence-mcp/upload-media-base64';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'data', 'filename', 'title', 'alt' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'data', 'filename' ], $ability );
	}

	/**
	 * Test update-media input schema.
	 */
	public function test_update_media_schema() {
		$ability = 'jazzsequence-mcp/update-media';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'ID', 'title', 'caption', 'description', 'alt' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'ID' ], $ability );
	}

	/**
	 * Test delete-media input schema.
	 */
	public function test_delete_media_schema() {
		$ability = 'jazzsequence-mcp/delete-media';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'ID', 'force_delete' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'ID' ], $ability );
	}

	/**
	 * Test get-media input schema.
	 */
	public function test_get_media_schema() {
		$ability = 'jazzsequence-mcp/get-media';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'ID' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'ID' ], $ability );
	}

	// -------------------------------------------------------------------------
	// Taxonomy abilities
	// -------------------------------------------------------------------------

	/**
	 * Test create-term input schema.
	 */
	public function test_create_term_schema() {
		$ability = 'jazzsequence-mcp/create-term';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'name', 'taxonomy', 'description', 'slug', 'parent' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'name', 'taxonomy' ], $ability );
	}

	/**
	 * Test update-term input schema.
	 */
	public function test_update_term_schema() {
		$ability = 'jazzsequence-mcp/update-term';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'term_id', 'taxonomy', 'name', 'slug', 'description', 'parent' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'term_id', 'taxonomy' ], $ability );
	}

	/**
	 * Test delete-term input schema.
	 */
	public function test_delete_term_schema() {
		$ability = 'jazzsequence-mcp/delete-term';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'term_id', 'taxonomy' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'term_id', 'taxonomy' ], $ability );
	}

	/**
	 * Test get-term input schema.
	 */
	public function test_get_term_schema() {
		$ability = 'jazzsequence-mcp/get-term';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'term_id', 'taxonomy' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'term_id', 'taxonomy' ], $ability );
	}

	// -------------------------------------------------------------------------
	// System abilities
	// -------------------------------------------------------------------------

	/**
	 * Test clear-cache input schema.
	 */
	public function test_clear_cache_schema() {
		$ability = 'jazzsequence-mcp/clear-cache';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'types' ], $ability );
	}

	/**
	 * Test run-cron input schema.
	 */
	public function test_run_cron_schema() {
		$ability = 'jazzsequence-mcp/run-cron';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'hook' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'hook' ], $ability );
	}

	/**
	 * Test schedule-cron input schema.
	 */
	public function test_schedule_cron_schema() {
		$ability = 'jazzsequence-mcp/schedule-cron';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'hook', 'timestamp', 'recurrence', 'args' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'hook' ], $ability );
	}

	/**
	 * Test unschedule-cron input schema.
	 */
	public function test_unschedule_cron_schema() {
		$ability = 'jazzsequence-mcp/unschedule-cron';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'hook', 'timestamp', 'args' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'hook' ], $ability );
	}

	// -------------------------------------------------------------------------
	// Config abilities
	// -------------------------------------------------------------------------

	/**
	 * Test get-option input schema.
	 */
	public function test_get_option_schema() {
		$ability = 'jazzsequence-mcp/get-option';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'option_name' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'option_name' ], $ability );
	}

	/**
	 * Test update-option input schema.
	 */
	public function test_update_option_schema() {
		$ability = 'jazzsequence-mcp/update-option';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'option_name', 'value' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'option_name', 'value' ], $ability );
	}

	/**
	 * Test delete-option input schema.
	 */
	public function test_delete_option_schema() {
		$ability = 'jazzsequence-mcp/delete-option';
		$schema  = $this->get_input_schema( $ability );

		$this->assertNotNull( $schema, "{$ability} must be registered" );
		$this->assertPropertiesIsObject( $schema, $ability );
		$this->assertHasProperties( $schema, [ 'option_name' ], $ability );
		$this->assertRequiredProperties( $schema, [ 'option_name' ], $ability );
	}
}
