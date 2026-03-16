<?php
/**
 * Tests for ability execute_callback behavior.
 *
 * Validates that the Content_Manager methods actually work with the parameter names
 * defined in the input_schema — proving direct MCP tool calls succeed without the
 * mcp-adapter-execute-ability workaround.
 *
 * These are the runtime checks that correspond to the PR test plan:
 * - jazzsequence-mcp-get-post with ID: <int> returns post data
 * - jazzsequence-mcp-create-post with post_title, post_status creates draft
 * - jazzsequence-mcp-update-post with ID, meta_input updates post meta
 * - jazzsequence-mcp-query-posts with post_type, per_page returns posts
 *
 * @package JazzSequence\MCP_Abilities\Tests
 */

use JazzSequence\MCP_Abilities\Abilities\Content\Content_Manager;

/**
 * Test ability execute_callback behavior with correct input_schema parameters.
 */
class Test_Ability_Execution extends WP_UnitTestCase {

	/**
	 * Content manager instance.
	 *
	 * @var Content_Manager
	 */
	private $manager;

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		parent::setUp();

		require_once JSMCP_PLUGIN_DIR . 'includes/abilities/content/class-content-manager.php';

		$this->manager = new Content_Manager();
	}

	/**
	 * Test create_post() with the parameter names defined in input_schema.
	 *
	 * Covers: "jazzsequence-mcp-create-post with post_title, post_status creates draft"
	 */
	public function test_create_post_with_schema_params() {
		$result = $this->manager->create_post(
			[
				'post_title'  => 'Test MCP Post',
				'post_status' => 'draft',
				'post_type'   => 'post',
			]
		);

		$this->assertIsArray( $result, 'create_post should return an array' );
		$this->assertArrayHasKey( 'ID', $result, 'Result should include post ID' );
		$this->assertGreaterThan( 0, $result['ID'], 'Post ID should be a positive integer' );
		$this->assertEquals( 'Test MCP Post', $result['post_title'], 'Post title should match' );
		$this->assertEquals( 'draft', $result['post_status'], 'Post status should be draft' );

		// Clean up.
		wp_delete_post( $result['ID'], true );
	}

	/**
	 * Test get_post() with the parameter name defined in input_schema (ID, not post_id).
	 *
	 * Covers: "jazzsequence-mcp-get-post with ID: <int> returns post data"
	 *
	 * This was the original failure: the schema had no property definitions,
	 * so Claude Code sent an empty object {} instead of {ID: 123}.
	 */
	public function test_get_post_with_schema_params() {
		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'Test Get Post',
				'post_status' => 'publish',
			]
		);

		$result = $this->manager->get_post( [ 'ID' => $post_id ] );

		$this->assertIsArray( $result, 'get_post should return an array' );
		$this->assertEquals( $post_id, $result['ID'], 'Should return the correct post' );
		$this->assertEquals( 'Test Get Post', $result['post_title'], 'Post title should match' );
		$this->assertEquals( 'publish', $result['post_status'], 'Post status should match' );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_post() fails with the WRONG parameter name (post_id instead of ID).
	 *
	 * Proves the schema fix matters: without it, Claude Code would send {post_id: 123}
	 * and the manager would return an error because it checks $args['ID'].
	 */
	public function test_get_post_fails_without_ID_param() {
		$post_id = $this->factory->post->create( [ 'post_title' => 'Test' ] );

		$result = $this->manager->get_post( [ 'post_id' => $post_id ] );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should fail when ID param is missing' );
		$this->assertEquals( 'missing_post_id', $result->get_error_code() );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test update_post() with the parameter names defined in input_schema.
	 *
	 * Covers: "jazzsequence-mcp-update-post with ID, meta_input updates post meta"
	 */
	public function test_update_post_with_schema_params() {
		$post_id = $this->factory->post->create(
			[
				'post_title'  => 'Original Title',
				'post_status' => 'draft',
			]
		);

		$result = $this->manager->update_post(
			[
				'ID'          => $post_id,
				'post_title'  => 'Updated Title',
				'meta_input'  => [
					'autoblue_custom_message' => 'Test Bluesky message',
				],
			]
		);

		$this->assertIsArray( $result, 'update_post should return an array' );
		$this->assertEquals( 'Updated Title', $result['post_title'], 'Title should be updated' );

		$stored_meta = get_post_meta( $post_id, 'autoblue_custom_message', true );
		$this->assertEquals( 'Test Bluesky message', $stored_meta, 'Meta should be stored' );

		wp_delete_post( $post_id, true );
	}

	/**
	 * Test update_post() fails without required ID parameter.
	 *
	 * Without the schema fix, Claude Code would not know ID is required and might omit it.
	 */
	public function test_update_post_fails_without_ID() {
		$result = $this->manager->update_post( [ 'post_title' => 'New Title' ] );

		$this->assertInstanceOf( \WP_Error::class, $result, 'Should fail when ID is missing' );
		$this->assertEquals( 'missing_post_id', $result->get_error_code() );
	}

	/**
	 * Test query_posts() with the parameter names defined in input_schema.
	 *
	 * Covers: "jazzsequence-mcp-query-posts with post_type, per_page returns posts"
	 */
	public function test_query_posts_with_schema_params() {
		$post_ids = $this->factory->post->create_many(
			3,
			[
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		$result = $this->manager->query_posts(
			[
				'post_type'      => 'post',
				'posts_per_page' => 2,
				'paged'          => 1,
				'post_status'    => 'publish',
			]
		);

		$this->assertIsArray( $result, 'query_posts should return an array' );
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'total_pages', $result );
		$this->assertCount( 2, $result['posts'], 'Should respect posts_per_page' );
		$this->assertGreaterThanOrEqual( 3, $result['total'], 'Should report total count' );

		foreach ( $post_ids as $id ) {
			wp_delete_post( $id, true );
		}
	}
}
