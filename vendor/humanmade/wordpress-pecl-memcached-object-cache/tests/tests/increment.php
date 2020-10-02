<?php

class MemcachedUnitTestsIncrement extends MemcachedUnitTests {
	public function test_increment_reduces_value_by_1() {
		$key = microtime();

		$value = 99;

		// Verify set
		$this->assertTrue( $this->object_cache->set( $key, $value ) );

		// Verify value
		$this->assertSame( $value, $this->object_cache->get( $key ) );

		// Verify that value was properly incremented
		$this->assertSame( 100, $this->object_cache->increment( $key ) );
	}

	public function test_increment_reduces_value_by_x() {
		$key = microtime();

		$value = 99;
		$x = 5;

		$reduced_value = $value + $x;

		// Verify set
		$this->assertTrue( $this->object_cache->set( $key, $value ) );

		// Verify value
		$this->assertSame( $value, $this->object_cache->get( $key ) );

		// Verify that value was properly incremented
		$this->assertSame( $reduced_value, $this->object_cache->increment( $key, $x ) );
	}

	public function test_increment_reduces_value_by_1_for_no_mc_group() {
		$key = microtime();

		$value = 99;
		$group = 'counts';

		// Verify set
		$this->assertTrue( $this->object_cache->set( $key, $value, $group ) );

		// Verify value
		$this->assertSame( $value, $this->object_cache->get( $key, $group ) );

		// Verify that value was properly incremented
		$this->assertSame( 100, $this->object_cache->increment( $key, 1, $group ) );
	}

	public function test_increment_reduces_value_by_x_for_no_mc_group() {
		$key = microtime();

		$value = 99;
		$x = 5;

		$group = 'counts';

		$reduced_value = $value + $x;

		// Verify set
		$this->assertTrue( $this->object_cache->set( $key, $value, $group ) );

		// Verify value
		$this->assertSame( $value, $this->object_cache->get( $key, $group ) );

		// Verify that value was properly incremented
		$this->assertSame( $reduced_value, $this->object_cache->increment( $key, $x, $group ) );
	}
}