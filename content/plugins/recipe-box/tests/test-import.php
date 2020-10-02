<?php
/**
 * Recipe Box Import Tests.
 *
 * @since   0.2
 * @package Recipe_Box
 */
class RB_Import_Test extends WP_UnitTestCase {

	/**
	 * Test if our class exists.
	 *
	 * @since  0.2
	 */
	function test_class_exists() {
		$this->assertTrue( class_exists( 'RB_Import' ) );
	}

	/**
	 * Test that we can access our class through our helper function.
	 *
	 * @since  0.2
	 */
	function test_class_access() {
		$this->assertInstanceOf( 'RB_Import', rb()->import );
	}

	/**
	 * Replace this with some actual testing code.
	 *
	 * @since  0.2
	 */
	function test_sample() {
		$this->assertTrue( true );
	}
}
