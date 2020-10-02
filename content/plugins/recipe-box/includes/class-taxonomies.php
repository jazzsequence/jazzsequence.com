<?php
/**
 * Recipe Taxonomies
 *
 * @since 0.1
 * @package Recipe Box
 */

require_once dirname( __FILE__ ) . '/../vendor/taxonomy-core/Taxonomy_Core.php';

/**
 * Taxonomies class.
 *
 * @see https://github.com/WebDevStudios/Taxonomy_Core
 * @since 0.1
 */
class RB_Taxonomies {
	/**
	 * Parent plugin class
	 *
	 * @var class
	 * @since  0.1
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since 0.1
	 * @param  object $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since 0.1
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_taxonomies' ), 4 );
	}

	/**
	 * Register Taxonomy. See documentation in Taxonomy_Core, and in wp-includes/taxonomy.php
	 *
	 * @since 0.1
	 */
	public function register_taxonomies() {
		// Recipe Category.
		register_via_taxonomy_core( array(
				__( 'Recipe Category', 'recipe-box' ),   // Singular.
				__( 'Recipe Categories', 'recipe-box' ),  // Plural.
				'rb_recipe_category',                    // Registered name.
			),
			array(),             // Array of taxonomy arguments.
			array( 'rb_recipe' ) // Array of post types.
		);

		// Meal Types.
		register_via_taxonomy_core( array(
				__( 'Meal Type', 'recipe-box' ),  // Singular.
				__( 'Meal Types', 'recipe-box' ), // Plural.
				'rb_meal_type',                   // Registered name.
			),
			array(),             // Array of taxonomy arguments.
			array( 'rb_recipe' ) // Array of post types.
		);

		// Recipe Cuisines.
		register_via_taxonomy_core( array(
				__( 'Cuisine', 'recipe-box' ),  // Singular.
				__( 'Cuisines', 'recipe-box' ), // Plural.
				'rb_recipe_cuisine',            // Registered name.
			),
			array(),             // Array of taxonomy arguments.
			array( 'rb_recipe' ) // Array of post types.
		);
	}

	/**
	 * Return the term objects for the recipe taxonomy passed.
	 *
	 * @since  0.2
	 * @param  mixed  $post   If passed, can take an int or a WP_Post object. Otherwise attempts to find the post ID using get_the_ID.
	 * @param  string $tax    The recipe taxonomy. Defaults to Recipe Category.
	 * @param  bool   $simple Optional. If true, will return a simplified array of each term.
	 * @return array          An array of WP_Term objects.
	 */
	public function get_the_recipe_terms( $post = false, $tax = 'rb_recipe_category', $simple = false ) {
		// Check for an error.
		if ( is_wp_error( $post ) ) {
			return ( $post instanceof WP_Error );
		}

		// Get the post ID.
		if ( $post && is_int( $post ) ) {
			$post_id = absint( $post );
		} elseif ( $post && is_object( $post ) ) {
			$post_id = $post->ID;
		} else {
			$post_id = get_the_ID();
		}

		$terms = get_the_terms( $post, $tax );

		// Bail if there are no terms.
		if ( ! $terms ) {
			return [];
		}

		// If we aren't returning the simplified output, return the full term data.
		if ( ! $simple ) {
			return $terms;
		}

		// Handle the simplified term output used by the API.
		$the_terms = [];
		foreach ( $terms as $term ) {
			$the_terms[] = [
				'name' => $term->name,
				'slug' => $term->slug,
				'desc' => $term->description,
			];
		}

		return $the_terms;
	}

	/**
	 * Returns the recipe terms HTML markup for the taxonomy given.
	 *
	 * @since  0.2
	 * @param  mixed  $post      If passed, can take an int or a WP_Post object. Otherwise attempts to find the post ID using get_the_ID.
	 * @param  string $tax       The recipe taxonomy. Defaults to Recipe Category.
	 * @param  string $separator The separator between terms. Defaults to ", ".
	 * @return string             The HTML markup for the list of recipe terms of the given taxonomy.
	 */
	public function recipe_terms( $post = false, $tax = 'rb_recipe_category', $separator = ', ' ) {
		// Bail if no post was passed.
		if ( ! $post ) {
			return;
		}

		$terms = $this->get_the_recipe_terms( $post, $tax );
		$taxonomy = get_taxonomy( $tax );

		$i = 1;

		$output = '';

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$separator = ( count( $terms ) > $i ) ? $separator : '';

				$output .= sprintf( '<a class="recipe-%3$s %4$s" href="%1$s">%2$s</a>',
					get_term_link( $term, $tax ),
					esc_html( $term->name ),
					sanitize_title( strtolower( $taxonomy->labels->singular_name ) ),
					$term->slug
				);
				$output .= $separator;

				$i++;
			}

			$output = sprintf( '%1$s: %2$s', $taxonomy->labels->name, $output );
		}

		return apply_filters( 'rb_filter_recipe_' . $tax . '_terms', $output, $terms );
	}
}
