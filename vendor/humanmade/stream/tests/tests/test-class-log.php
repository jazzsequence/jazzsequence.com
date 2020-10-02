<?php
namespace WP_Stream;

class Test_Log extends WP_StreamTestCase {

	public function setUp() {
		parent::setUp();

		$admin_role = get_role( 'administrator' );

		/**
		 * Add user roles for testing.
		 */
		$role = 'this_longish_user_role_slug_to_be_logged_in_stream';
		$long = get_role( $role );
		if ( ! $long ) {
			$long = add_role(
				$role,
				'This longish user_role slug to be logged in stream',
				(array) $admin_role->capabilities
			);
		}

		$role = 'this_is_a_really_long_user_role_slug_that_will_not_be_logged_in_stream';
		$extra_long = get_role( $role );
		if ( ! $extra_long ) {
			$extra_long = add_role(
				$role,
				'This is a really long user_role slug that will not be logged in stream',
				(array) $admin_role->capabilities
			);
		}

		/**
		 * Add users for testing and assign roles.
		 */
		$user_id = wp_create_user( 'test1', 'password', 'test1@example.com' );
		wp_update_user( array( 'ID' => $user_id, 'role' => $long->name ) );

		$user_id = wp_create_user( 'test2', 'password', 'test2@example.com' );
		wp_update_user( array( 'ID' => $user_id, 'role' => $extra_long->name ) );
	}

	public function test_field_lengths() {

		$user1 = get_user_by( 'slug', 'test1' );
		$user2 = get_user_by( 'slug', 'test2' );

		// Test user_role length (<=50)
		$result = $this->plugin->log->log( 'test_connector', 'Test user_role 1', array(), 0, 'settings', 'test', $user1->ID );
		$this->assertNotEmpty( $result );
		$record = $this->plugin->db->query( array( 'search' => $result, 'search_field' => 'ID' ) );
		$this->assertEquals( $result, $record[0]->ID );

		// Test user_role length (>50)
		$result = $this->plugin->log->log( 'test_connector', 'Test user_role 2', array(), 0, 'settings', 'test', $user2->ID );
		$this->assertEmpty( $result );

		// Test connector length

		// Test context length

		// Test action length

		// Test IP length
		
	}
}
