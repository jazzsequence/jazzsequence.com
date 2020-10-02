<?php
/**
 * Recipe CPT
 *
 * @since 0.1
 * @package Recipe Box
 */

require_once dirname( __FILE__ ) . '/../vendor/cpt-core/CPT_Core.php';
require_once dirname( __FILE__ ) . '/../vendor/cmb2/init.php';

/**
 * Recipe Box RB_Recipe post type class.
 *
 * @see https://github.com/WebDevStudios/CPT_Core
 * @since 0.1
 */
class RB_Recipe {
	/**
	 * Parent plugin class
	 *
	 * @var class
	 * @since  0.1
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 * Register Custom Post Types. See documentation in CPT_Core, and in wp-includes/post.php
	 *
	 * @since  0.1
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Register the CPTs, yo.
	 *
	 * @since 0.1
	 */
	public function register_cpts() {
		// Register Recipes.
		register_via_cpt_core(
			array(
				__( 'Recipe', 'recipe-box' ),  // Singular.
				__( 'Recipes', 'recipe-box' ), // Plural.
				'rb_recipe',                   // Post type name.
			),
			array(
				'supports'     => [ 'title', 'editor', 'thumbnail' ],
				'menu_icon'    => 'dashicons-carrot',
				'rewrite'      => [ 'slug' => 'recipe' ],
				'show_in_rest' => true,
				'rest_base'    => 'recipes',
			)
		);

		// Register ingredients CPT.
		register_via_cpt_core(
			array(
				__( 'Ingredient', 'recipe-box' ),  // Singular.
				__( 'Ingredients', 'recipe-box' ), // Plural.
				'rb_ingredient',                      // Post type name.
			),
			array(
				'supports'     => array( 'title' ),
				'public'       => false,
				'show_in_menu' => false,
				'show_in_rest' => true,
				'rest_base'    => 'ingredients',
			)
		);
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.1
	 */
	public function hooks() {
		add_action( 'cmb2_init',              [ $this, 'fields' ] );
		add_action( 'init',                   [ $this, 'register_cpts' ], 9 );
		add_action( 'save_post',              [ $this, 'save_ingredient' ], 10, 3 );
		add_action( 'admin_enqueue_scripts',  [ $this, 'admin_enqueue_scripts' ], 9999 );
		add_filter( 'rest_prepare_rb_recipe', [ $this, 'filter_recipes_json' ], 10, 2 );

		// Allow the Slack Integration plugin to include the recipe post type.
		add_filter( 'slack_event_transition_post_status_post_types', function( $post_types ) {
			return array_merge( $post_types, [ 'rb_recipe' ] );
		} );
	}


	/**
	 * Enqueue admin javascript. Mostly for autocompletion.
	 *
	 * @since  0.1
	 * @param  string $hook The current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $post;

		// Bail if we aren't editing a post.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// Bail if we aren't editing a recipe.
		if ( 'rb_recipe' !== $post->post_type ) {
			return;
		}

		$min = '.min';

		// Don't use minified js/css if DEBUG is on.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$min = '';
		}

		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'recipes', rb()->url . 'assets/js/recipes' . $min . '.js', array( 'jquery' ), rb()->version, true );
		wp_enqueue_style( 'recipes', rb()->url . 'assets/css/recipes' . $min . '.css', array(), rb()->version, 'screen' );
		wp_localize_script( 'recipes', 'recipes', array(
			'autosuggest' => $this->autosuggest_terms(),
			'wp_debug'    => ( defined( 'WP_DEBUG' ) ) ? WP_DEBUG : false,
		) );
	}

	/**
	 * Get an array of unique ingredient names using the WP-API.
	 *
	 * @since  0.1
	 * @todo         This whole thing needs to be edited to use post meta instead of an ingredient cpt.
	 * @return array An array of ingredient names (post titles).
	 */
	public function autosuggest_terms() {
		// Get the ingredients from the WP-API.
		$request = wp_remote_get( home_url( '/wp-json/wp/v2/ingredients?filter[posts_per_page]=1000' ) );

		if ( $request && ! is_wp_error( $request ) ) {
			// Decode the json.
			$results = json_decode( $request['body'] );

			// Build an array of ingredient names.
			$ingredient_list = [];
			foreach ( $results as $ingredient ) {
				$ingredient_list[] = $ingredient->title->rendered;
			}

			// Strip out any duplicate ingredients and return.
			return array_unique( $ingredient_list );
		}

		return new WP_Error( 'rb_ingredients_remote_get_fail', __( 'WordPress remote get operation failed.', 'recipe-box' ), $request );
	}

	/**
	 * Register the CMB2 fields and metaboxes.
	 *
	 * @since 0.1
	 */
	public function fields() {
		$prefix = '_rb_';

		$this->recipe_meta( $prefix );
		$this->ingredients( $prefix . 'ingredients_' );
		$this->instructions( $prefix . 'instructions_' );
	}


	/**
	 * Handles the Recipe Information CMB2 box.
	 *
	 * @since  0.1
	 * @param  string $prefix The meta prefix.
	 */
	private function recipe_meta( $prefix ) {

		$post_id = isset( $_GET['post'] ) && ! is_array( $_GET['post'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) : false;

		$cmb = new_cmb2_box( array(
			'id'           => $prefix . 'info_metabox',
			'title'        => __( 'Recipe Information', 'recipe-box' ),
			'object_types' => array( 'rb_recipe' ),
			'classes'      => 'recipe-meta',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Servings', 'recipe-box' ),
			'id'         => $prefix . 'servings',
			'type'       => 'text_small',
			'default'    => '2',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Preparation Time', 'recipe-box' ),
			'id'         => $prefix . 'prep_time',
			'type'       => 'text_small',
			'desc'       => __( 'minutes', 'recipe-box' ) .
				// 1: opening div tag, 2: closing div tag.
				sprintf( __( '%1$sTime to prepare the recipe.%2$s', 'recipe-box' ),
				'<div class="extended-description">',
				'</div>'
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Cook Time', 'recipe-box' ),
			'id'         => $prefix . 'cook_time',
			'type'       => 'text_small',
			'desc'       => __( 'minutes', 'recipe-box' ) .
				// 1: opening div tag, 2: closing div tag.
				sprintf( __( '%1$sTime to cook the recipe.%2$s', 'recipe-box' ),
				'<div class="extended-description">',
				'</div>'
			)
		) );

		$cmb->add_field( array(
			'name'       => __( 'Total Time (optional)', 'recipe-box' ),
			'id'         => $prefix . 'total_time',
			'type'       => 'text_small',
			'desc'       => __( 'minutes', 'recipe-box' ) .
				sprintf( __( '%1$sThe total time to prepare the recipe. (Defaults to Prep Time + Cook Time. Change if that is not accurate.)%2$s', 'recipe-box' ),
				// 1: opening div tag, 2: closing div tag.
				'<div class="extended-description">',
				'</div>'
			),
			'default'    => ( $post_id ) ? $this->get_total_time( $post_id ) : '',
		) );

		$group_field_id = $cmb->add_field( array(
			'id'          => $prefix . 'preheat_group',
			'type'        => 'group',
			'repeatable'  => false,
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Preheat Temperature', 'recipe-box' ),
			'id'          => $prefix . 'preheat_temp',
			'type'        => 'text_small',
			'attributes'  => [
				'type' => 'number',
				'step' => '5',
			]
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Unit', 'recipe-box' ),
			'id'          => $prefix . 'preheat_unit',
			'type'        => 'select',
			'options'     => [
				'farenheit' => __( 'Farenheit', 'recipe-box' ),
				'celcius'   => __( 'Celcius' ),
			],
		) );
	}


	/**
	 * Handles the recipe instructions metabox.
	 *
	 * @since  0.1
	 * @param  string $prefix The post meta key prefix.
	 */
	private function instructions( $prefix ) {
		$cmb = new_cmb2_box( array(
			'id'           => $prefix . 'metabox',
			'title'        => __( 'Preparation', 'recipe-box' ),
			'object_types' => array( 'rb_recipe' ),
			'classes'      => 'preparation',
		) );

		$group_field_id = $cmb->add_field( array(
			'id'          => $prefix . 'group',
			'type'        => 'group',
			'description' => __( 'Add the instructions for the recipe. Instructions can be divided up into multiple groups of steps (e.g. Batter Instructions, Filling Instructions).', 'recipe-box' ),
			'options'     => array(
				'group_title'   => __( 'Preparation Group {#}', 'recipe-box' ),
				'add_button'    => __( 'Add another group', 'recipe-box' ),
				'remove_button' => __( 'Remove group', 'recipe-box' ),
				'sortable'      => true,
			),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'       => __( 'Title', 'recipe-box' ),
			'id'         => $prefix . 'title',
			'type'       => 'text',
			'desc'       => __( 'The title you want to appear above your preparation instructions in this group.', 'recipe-box' ),
			'default'    => __( 'Instructions', 'recipe-box' ),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Steps', 'recipe-box' ),
			'id'          => 'content',
			'desc'        => __( 'Click "Add another step" to add more steps to this instruction group.', 'recipe-box' ),
			'type'        => 'textarea_small',
			'options'     => array(
				'add_row_text' => __( 'Add another step', 'recipe-box' ),
			),
			'repeatable'  => true,
		) );
	}


	/**
	 * The ingredients list metabox.
	 *
	 * @since  0.1
	 * @param  string $prefix The post metakey prefix.
	 */
	private function ingredients( $prefix ) {
		$cmb = new_cmb2_box( array(
			'id'           => $prefix . 'metabox',
			'title'        => __( 'Ingredients', 'recipe-box' ),
			'object_types' => array( 'rb_recipe' ),
			'show_names'   => true,
			'classes'      => array( 'ingredients' ),
		) );

		$group_field_id = $cmb->add_field( array(
			'id'          => $prefix . 'group',
			'type'        => 'group',
			'description' => __( 'Add the ingredients for this recipe. For each ingredient, you can enter a custom ingredient or if you start typing in the Ingredient box, your ingredient will be automatically matched to an existing ingredient from a previous recipe.', 'recipe-box' ),
			'options'     => array(
				'group_title'   => __( 'Ingredient {#}', 'recipe-box' ),
				'add_button'    => __( 'Add another ingredient', 'recipe-box' ),
				'remove_button' => __( 'Remove ingredient', 'recipe-box' ),
				'sortable'      => true,
			),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Ingredient', 'recipe-box' ),
			'id'          => $prefix . 'product',
			'type'        => 'text',
			'desc'        => __( 'Enter the ingredient name.', 'recipe-box' ),
			'attributes'  => array( 'class' => 'ingredient' ),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Quantity', 'recipe-box' ),
			'description' => __( 'How many units of this ingredient?', 'recipe-box' ),
			'id'          => $prefix . 'quantity',
			'type'        => 'text_small',
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Unit of measurement', 'recipe-box' ),
			'description' => __( '(2 Tbsp, 3 cups, 1 handful, etc.)' ),
			'id'          => $prefix . 'unit',
			'type'        => 'select',
			'options'     => $this->get_units(),
			'attributes'  => array(
				'data-placeholder' => __( 'Select unit of measurement.', 'recipe-box' ),
			),
		) );

		$cmb->add_group_field( $group_field_id, array(
			'name'        => __( 'Notes', 'recipe-box' ),
			'id'          => $prefix . 'notes',
			'type'        => 'text',
			'desc'        => __( 'Any notes about the ingredient (alternate ingredients, substitutions, "to taste", optional instructions etc.).', 'recipe-box' ),
			'attributes'  => array( 'class' => 'notes' ),
		) );
	}


	/**
	 * Return various units of measurement.
	 *
	 * @since  0.1
	 * @return array Different units of measurement that could be used in a recipe.
	 */
	private function get_units() {
		return array(
			'none'    => '&mdash;',
			'cup'     => __( 'cup(s)', 'recipe-box' ),
			'tbsp'    => __( 'tablespoon(s)', 'recipe-box' ),
			'tsp'     => __( 'teaspoon(s)', 'recipe-box' ),
			'oz'      => __( 'ounce(s)', 'recipe-box' ),
			'gram'    => __( 'gram(s)', 'recipe-box' ),
			'piece'   => __( 'piece(s)', 'recipe-box' ),
			'quart'   => __( 'quart(s)', 'recipe-box' ),
			'gallon'  => __( 'gallon(s)', 'recipe-box' ),
			'half'    => __( 'halves', 'recipe-box' ),
			'can'     => __( 'can(s)', 'recipe-box' ),
			'package' => __( 'package(s)', 'recipe-box' ),
			'sprig'   => __( 'sprig(s)', 'recipe-box' ),
			'dash'    => __( 'dash(es)', 'recipe-box' ),
			'drop'    => __( 'drop(s)', 'recipe-box' ),
			'bunch'   => __( 'bunch(es)', 'recipe-box' ),
			'hand'    => __( 'handful(s)', 'recipe-box' ),
			'splash'  => __( 'splash(es)', 'recipe-box' ),
			'pinch'   => __( 'pinch(es)', 'recipe-box' ),
			'clove'   => __( 'clove(s)', 'recipe-box' ),
			'whole'   => __( 'whole', 'recipe-box' ),
			'some'    => __( 'some', 'recipe-box' ),
		);
	}

	/**
	 * Helper function to calculate the total time based on prep time and cook time.
	 *
	 * @param  int $post_id The post ID.
	 * @return int          The total time calculation.
	 */
	public function get_total_time( $post_id ) {
		$total_time = get_post_meta( $post_id, '_rb_total_time', true );
		if ( '' == $total_time || ! $total_time ) {
			$total_time = absint( get_post_meta( $post_id, '_rb_prep_time', true ) ) + absint( get_post_meta( $post_id, '_rb_cook_time', true ) );
		}

		return $total_time;
	}

	/**
	 * Function to calculate time in HH:MM from time stored only in minutes.
	 *
	 * @since  0.1
	 * @param  integer $time_in_minutes Time in minutes.
	 * @param  string  $format          The desired format of the calculated time.
	 *         Accepted possibilities are:
	 *         'hh:mm' or 'HH:MM'        Time in hours and minutes, e.g. 4:30.
	 *         'array'                   Returns an array of hours and minutes.
	 *         'string'                  Returns the time in plain english.
	 *         'duration'                Returns the time in ISO 8601 duration format.
	 * @return mixed                     Time in HH:MM (default) or whatever format was passed.
	 */
	public function calculate_hours_minutes( $time_in_minutes, $format = 'hh:mm' ) {

		// If no time is saved, bail.
		if ( ! $time_in_minutes ) {
			return;
		}

		$hours   = intval( $time_in_minutes / 60 );
		$minutes = $time_in_minutes - ( $hours * 60 );

		// Store hours and minutes in an array.
		$time = array(
			'hours'   => $hours,
			'minutes' => $minutes,
		);

		// Check the format. If we want the time in HH:MM format, return that.
		if ( in_array( $format, array( 'hh:mm', 'HH:MM' ) ) ) {
			return ( $time['hours'] >= 1 ) ? sprintf( '%d:%d', $time['hours'], $time['minutes'] ) : $time['minutes'];
		}

		// ...but maybe we want to do something like "4 hours and 20 minutes", or manipulate the format manually. In that case we can just return the array of hours/minutes.
		if ( 'array' === $format ) {
			return $time;
		}

		// If we need a ISO 8601 time format (e.g. for schema.org).
		if ( 'duration' === $format ) {
			$duration = 'PT';

			if ( $time['hours'] >= 1 ) {
				$duration .= sprintf( 'H%d', absint( $time['hours'] ) );
			}

			$duration .= absint( $time['minutes'] );

			return $duration;
		}

		// We can also use this array to return the time in plain text.
		if ( 'string' === $format ) {
			return ( $time['hours'] >= 1 ) ? sprintf( __( '%d hours and %d minutes', 'recipe-box' ), $time['hours'], $time['minutes'] ) : sprintf( __( '%d minutes', 'recipe-box' ), $time['minutes'] );
		}

		// If we got here and we haven't returned anything, just return what we got in the beginning.
		return $time_in_minutes;
	}

	/**
	 * Save ingredients as ingredient CPT posts when a recipe is saved.
	 *
	 * @since  0.1
	 * @param  int    $post_id The post ID of the recipe being saved.
	 * @param  object $post    The post object of the recipe.
	 * @param  bool   $update  Whether or not the post was updated.
	 */
	public function save_ingredient( $post_id, $post, $update ) {
		// If we aren't saving a recipe post, bail.
		if ( 'rb_recipe' !== $post->post_type ) {
			return;
		}

		$ingredients = get_post_meta( $post_id, '_rb_ingredients_group', true );

		$item_slug = '';
		$item_name = '';

		if ( $ingredients ) {
			foreach ( $ingredients as $ingredient ) {
				// Get the name of the ingredient.
				if ( isset( $ingredient['_rb_ingredients_product'] ) ) {
					$item_slug = sanitize_title( $ingredient['_rb_ingredients_product'] );
					$item_name = esc_html( $ingredient['_rb_ingredients_product'] );
				}

				// See if there's an existing ingredient CPT with this slug.
				$match = new WP_Query( array(
					'name'        => $item_slug,
					'post_type'   => 'rb_ingredient',
					'post_status' => 'publish',
					'numberposts' => 1,
					'fields'      => 'ids',
				) );

				// If nothing matches, we're going to add a new ingredient with this name.
				if ( empty( $match->posts ) ) {
					wp_insert_post( array(
						'post_title'  => $item_name,
						'post_name'   => $item_slug,
						'post_type'   => 'rb_ingredient',
						'post_status' => 'publish',
					) );
				}
			}
		}
	}

	/**
	 * Add recipe postmeta to the recipe API objects.
	 *
	 * @since  0.3
	 * @param  object $data The REST API object.
	 * @param  object $post The WP_Post object.
	 * @return object       Updated REST API object with recipe meta included.
	 */
	public function filter_recipes_json( $data, $post ) {
		$fields = [
			'preheat_temp'      => rb()->public->get_preheat_temp( $post->ID ),
			'ingredients'       => rb()->public->get_ingredients( $post->ID ),
			'servings'          => rb()->public->get_servings( $post->ID ),
			'steps'             => rb()->public->get_steps( $post->ID ),
			'cook_times'        => rb()->public->get_cook_time( $post->ID ),
			'recipe_categories' => rb()->taxonomy->get_the_recipe_terms( $post->ID, 'rb_recipe_category', true ),
			'meal_type'         => rb()->taxonomy->get_the_recipe_terms( $post->ID, 'rb_meal_type', true ),
			'cuisine'           => rb()->taxonomy->get_the_recipe_terms( $post->ID, 'rb_recipe_cuisine', true ),
		];

		foreach ( $fields as $key => $value ) {
			$data->data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * Registers admin columns to display. Hooked in via CPT_Core.
	 *
	 * @since  0.1
	 * @param  array $columns Array of registered column names/labels.
	 * @return array          Modified array
	 */
	public function columns( $columns ) {
		$new_column = array();
		return array_merge( $new_column, $columns );
	}

	/**
	 * Handles admin column display. Hooked in via CPT_Core.
	 *
	 * @since  0.1
	 * @param array $column  Column currently being rendered.
	 * @param int   $post_id ID of post to display column for.
	 */
	public function columns_display( $column, $post_id ) {
		switch ( $column ) {
		}
	}
}
