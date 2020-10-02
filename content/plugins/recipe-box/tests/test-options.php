<?php
/**
 * Recipe Box Options Tests.
 *
 * @since   0.1
 * @package Recipe_Box
 */
class RB_Options_Test extends WP_UnitTestCase {

	/**
	 * Test if our class exists.
	 *
	 * @since  0.1
	 */
	function test_class_exists() {
		$this->assertTrue( class_exists( 'RB_Options') );
	}

	/**
	 * Test that we can access our class through our helper function.
	 *
	 * @since  0.1
	 */
	function test_class_access() {
		$this->assertInstanceOf( 'RB_Options', rb()->options );
	}

	/**
	 * Replace this with some actual testing code.
	 *
	 * @since  0.1
	 */
	function test_sample() {
		$this->assertTrue( true );
	}
}
