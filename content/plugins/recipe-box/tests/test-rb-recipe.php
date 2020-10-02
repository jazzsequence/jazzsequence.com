<?php

class RB_Rb_recipe_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'RB_Rb_recipe') );
	}

	function test_class_access() {
		$this->assertTrue( rb()->rb-recipe instanceof RB_Rb_recipe );
	}

  function test_cpt_exists() {
    $this->assertTrue( post_type_exists( 'rb-rb-recipe' ) );
  }
}
