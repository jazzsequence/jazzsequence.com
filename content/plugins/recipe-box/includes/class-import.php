<?php
/**
 * Recipe Box Import.
 *
 * Class for syndicating/importing recipes from a remote Recipe Box site.
 *
 * @since   0.3
 * @package Recipe_Box
 */

// We use CMB2 for the forms but we're not actually submitting anything.
require_once dirname( __FILE__ ) . '/../vendor/cmb2/init.php';

/**
 * Recipe Box Import.
 *
 * @since 0.3
 */
class RB_Import {
	/**
	 * Parent plugin class.
	 *
	 * @since 0.3
	 *
	 * @var   Recipe_Box
	 */
	protected $plugin = null;

	/**
	 * CMB2 key.
	 *
	 * @var    string
	 * @since  0.3
	 */
	protected $key = 'recipe_box_import';

	/**
	 * CMB2 metabox ID.
	 *
	 * @var    string
	 * @since  0.3
	 */
	protected $metabox_id = 'recipe_box_import_metabox';

	/**
	 * Constructor.
	 *
	 * @since  0.3
	 *
	 * @param  Recipe_Box $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.3
	 */
	public function hooks() {
		add_action( 'admin_menu',            [ $this, 'add_import_page' ] );
		add_action( 'cmb2_admin_init',       [ $this, 'add_import_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_js' ] );

		add_filter( 'json_prepare_post',     [ $this, 'trim_data' ], 12, 3 );
	}

	/**
	 * Enqueue admin javascript for importing recipes.
	 *
	 * @since 0.3
	 * @param string $hook The current screen.
	 */
	public function enqueue_admin_js( $hook ) {
		// Only load these scripts in the admin.
		if ( ! is_admin() || 'rb_recipe_page_recipe_box_import' !== $hook ) {
			return;
		}

		$min = '.min';

		// A better way to figure out the .min thing...
		// First we build an array of scripts. The first parameter is the script name and the second is the path to the script, excluding the .min.js or whatever.
		$scripts = [
			'name' => 'import',
			'js'   => rb()->url . 'assets/js/recipe-import',
			'css'  => rb()->url . 'assets/css/recipe-import',
		];

		// Check if debug is turned on. If it is, we set $min to an empty string. We won't minify if debugging is active.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$min = '';
		}

		// Set a default path that excludes $min entirely.
		$js_src  = $scripts['js'] . '.js';
		$css_src = $scripts['css'] . '.css';

		// Check if a minified version exists and set the $src to that if it does. If that file doesn't exist, we just use the default (unminified) version regardless of WP_DEBUG status.
		if ( file_exists( $scripts['js'] . $min . '.js' ) ) {
			$js_src = $scripts['js'] . $min . '.js';
		}

		if ( file_exists( $scripts['css'] . $min . '.css' ) ) {
			$css_src = $scripts['css'] . $min . '.css';
		}

		// If debug is on, bust the cache by appending a timestamp to the end of the version.
		$version = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? rb()->version . '-' . rand() : rb()->version;

		// Now enqueue the scripts.
		wp_enqueue_script( $scripts['name'], $js_src, [ 'jquery' ], $version, true );
		wp_enqueue_style( $scripts['name'], $css_src, [], $version );

		wp_localize_script( $scripts['name'], 'recipe_import_messages', [
			'error_no_url'      => esc_html__( 'No URL entered.', 'recipe-box' ),
			'error_invalid_url' => esc_html__( 'Attempted to fetch recipes but the URL you entered was invalid.', 'recipe-box' ),
			'success'           => esc_html__( 'Recipes found!', 'recipe-box' ),
			'no_more_recipes'   => esc_html__( 'That\'s all the recipes!', 'recipe-box' ),
			'duplicate_recipe'  => esc_html__( 'This recipe is a duplicate of one you have already.', 'recipe-box' ),
			'similar_recipe'    => esc_html__( 'This recipe seems similar to one you have already.', 'recipe-box' ),
			'import_url'        => get_admin_url( get_current_blog_id(), 'edit.php?post_type=rb_recipe&page=recipe_box_import' ),
			'this_recipe_box'   => get_home_url( get_current_blog_id() ),
		] );
	}

	/**
	 * Add the import admin page.
	 *
	 * @since 0.3
	 */
	public function add_import_page() {
		$this->options_page = add_submenu_page(
			'edit.php?post_type=rb_recipe',
			__( 'Import Recipes from Another Recipe Box', 'recipe-box' ),
			__( 'Import Recipes', 'recipe-box' ),
			'manage_options',
			'recipe_box_import',
			[ $this, 'import_page_display' ]
		);
	}

	/**
	 * Display the Import page. Handled by CMB2 and AJAX-y goodness.
	 *
	 * @since 0.3
	 */
	public function import_page_display() {
		// If we're here because we're importing recipes, handle that stuff and don't display this form.
		if ( isset( $_GET['importIds'] ) ) {
			$this->import_recipes();
			return;
		}

		// Override the default form and submit button.
		$args = [
			'form_format' => '',
			'save_button' => '',
		];
		?>
		<div class="wrap recipe-box-import">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<div class="recipe-box-import-messages">
				<p class="rb-messages-inner"></p>
			</div>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key, $args ); ?>
			<div class="recipe-box-import-header">
				<p class="fetching-recipes-message">
					<?php // Translators: %s is an API URL based on the Recipe Box site entered. ?>
					<?php echo wp_kses_post( sprintf( __( 'Fetching recipes from %s', 'recipe-box' ), '<span id="api-url-fetched"></span>' ) ); ?>
				</p>
			</div>
			<div class="recipe-box-import-recipe-list">
				<ul class="recipe-list">
				</ul>
			</div>
			<div class="recipe-box-import-footer">
				<p class="recipe-box-more">
					<a href="#" id="recipe-api-fetch-more" data-page="1"><?php esc_html_e( 'Fetch more recipes', 'recipe-box' ); ?></a>
				</p>
				<p class="recipe-box-import-submit">
					<button class="recipe-box-fetch button button-primary"><?php esc_html_e( 'Import selected recipes', 'recipe-box' ); ?></button>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Import recipes from remote Recipe Box.
	 *
	 * @since 0.3
	 */
	private function import_recipes() {
		$recipe_ids = ( isset( $_GET['importIds'] ) ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['importIds'] ) ) ) : [];
		$api_url    = ( isset( $_GET['importUrl'] ) ) ? esc_url( sanitize_text_field( wp_unslash( $_GET['importUrl'] ) ) ) : '';

		$imported_recipes = [];

		foreach ( $recipe_ids as $recipe_id ) {
			$url = $api_url . '/wp-json/wp/v2/recipes/' . $recipe_id;
			$response = wp_remote_get( $url );
			if ( is_array( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$recipe      = json_decode( wp_remote_retrieve_body( $response ) );
				$recipe_name = $recipe->title->rendered;

				if ( ! post_exists( $recipe_name ) ) {
					$post_id = $this->import_single_recipe( $recipe );

					if ( $post_id ) {
						$imported_recipes[] = [
							'ID'   => $post_id,
							'name' => $recipe_name,
						];
					}
				}
			}
		}

		?>
		<div class="wrap recipe-box-import">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		</div>
		<?php
		if ( ! empty( $imported_recipes ) ) : ?>
			<div class="imported-recipes">
				<ul class="imported-recipes-list">
				<?php
				foreach ( $imported_recipes as $imported_recipe ) {
					echo '<li class="recipe-' . $imported_recipe['ID'] . '">' . esc_html( $imported_recipe['name'] ) . '</li>'; // WPCS: XSS ok, sanitized.
				}
				?>
				</ul>
			</div>
		<?php
		else : ?>
			<div class="recipes-not-imported">
				<p><?php esc_html_e( 'Recipes not imported. Either there was a problem or were no new recipes to import.', 'recipe-box' ); ?></p>
			</div>
		<?php
		endif;

	}

	/**
	 * Import a recipe using the WordPress API data.
	 *
	 * @since  0.3
	 * @param  object $recipe An API object of fetched recipe data.
	 * @return mixed          Either the post ID of a successfully imported recipe or false if unsuccessful.
	 */
	private function import_single_recipe( $recipe ) {
		$post_id = wp_insert_post( [
			'post_title'   => $recipe->title->rendered,
			'post_content' => $recipe->content->rendered,
			'post_type'    => 'rb_recipe',
			'post_status'  => 'publish',
		] );

		if ( ! is_wp_error( $post_id ) ) {
			add_post_meta( $post_id, 'orig_recipe_id', $recipe->id );
			add_post_meta( $post_id, '_rb_servings', $recipe->servings );
			add_post_meta( $post_id, '_rb_prep_time', $recipe->cook_times->prep_time );
			add_post_meta( $post_id, '_rb_cook_time', $recipe->cook_times->cook_time );
			add_post_meta( $post_id, '_rb_total_time', $recipe->cook_times->total_time );

			$this->import_preheat_temp( $post_id, $recipe->preheat_temp );
			$this->import_steps( $post_id, $recipe->steps );
			$this->import_ingredients( $post_id, $recipe->ingredients );
			$this->import_taxonomy_terms( $post_id, 'rb_recipe_category', $recipe->recipe_categories );
			$this->import_taxonomy_terms( $post_id, 'rb_meal_type', $recipe->meal_type );
			$this->import_taxonomy_terms( $post_id, 'rb_recipe_cuisine', $recipe->cuisine );

			return $post_id;
		}

		return false;
	}

	/**
	 * Import preheat temperature from the recipe.
	 *
	 * @since 0.3
	 * @param int   $post_id      The post ID of the imported recipe.
	 * @param mixed $preheat_temp False or array of temperature values.
	 */
	private function import_preheat_temp( $post_id, $preheat_temp = false ) {
		if ( $preheat_temp ) {
			if ( isset( $preheat_temp->_rb_preheat_temp ) ) {
				add_post_meta( $post_id, '_rb_preheat_temp', $preheat_temp->_rb_preheat_temp );
			}

			if ( isset( $preheat_temp->_rb_preheat_unit ) ) {
				add_post_meta( $post_id, '_rb_preheat_unit', $preheat_temp->_rb_preheat_unit );
			}
		}
	}

	/**
	 * Import recipe instructions.
	 *
	 * @since 0.3
	 * @param int   $post_id The post ID of the imported recipe.
	 * @param mixed $steps   False or array of recipe instructions.
	 */
	private function import_steps( $post_id, $steps = false ) {
		if ( is_array( $steps ) ) {
			$instructions = [];
			$i = 0;
			foreach ( $steps as $group ) {
				$instructions[ $i ] = [
					'_rb_instructions_title' => isset( $steps[ $i ]->_rb_instructions_title ) ? $steps[ $i ]->_rb_instructions_title : '',
					'content'                => isset( $steps[ $i ]->content ) ? $steps[ $i ]->content : '',
				];
				$i++;
			}
			add_post_meta( $post_id, '_rb_instructions_group', $instructions );
		}
	}

	/**
	 * Import recipe ingredients.
	 *
	 * @since 0.3
	 * @param int   $post_id     		The post ID of the imported recipe.
	 * @param mixed $ingredient_entries False or array of recipe ingredients.
	 */
	private function import_ingredients( $post_id, $ingredient_entries = false ) {
		if ( is_array( $ingredient_entries ) ) {
			$ingredients = [];
			$i = 0;
			foreach ( $ingredient_entries as $ingredient ) {
				$name     = isset( $ingredient->_rb_ingredients_product ) ? esc_html( $ingredient->_rb_ingredients_product ) : '';
				$quantity = isset( $ingredient->_rb_ingredients_quantity ) ? sanitize_text_field( $ingredient->_rb_ingredients_quantity ) : '';
				$unit     = isset( $ingredient->_rb_ingredients_unit ) ? sanitize_text_field( $ingredient->_rb_ingredients_unit ) : '';
				$notes    = isset( $ingredient->_rb_ingredients_notes ) ? sanitize_text_field( $ingredient->_rb_ingredients_notes ) : '';

				$ingredients[ $i ] = [
					'_rb_ingredients_product'  => $name,
					'_rb_ingredients_quantity' => $quantity,
					'_rb_ingredients_unit'     => $unit,
					'_rb_ingredients_notes'    => $notes,
				];

				// Add the ingredient so it can be autosuggested in future recipes.
				$ingredient_id = post_exists( $name );
				if ( ! $ingredient_id ) {
					$ingredient_id = wp_insert_post( [
						'post_title'  => $name,
						'post_status' => 'publish',
						'post_type'   => 'rb_ingredient',
					] );
				}
				$i++;
			}
			add_post_meta( $post_id, '_rb_ingredients_group', $ingredients );
		}
	}

	/**
	 * Import taxonomy terms from WP-API response.
	 *
	 * @since  0.3
	 * @param  int    $post_id  The post ID of the imported recipe.
	 * @param  string $taxonomy The taxonomy.
	 * @param  array  $terms    An array of terms with their slugs and descriptions.
	 */
	private function import_taxonomy_terms( $post_id, $taxonomy, $terms ) {
		$terms_to_insert = [];

		foreach ( $terms as $term ) {
			if ( ! term_exists( $term->name, $taxonomy ) ) {
				$the_term = wp_insert_term( $term->name, $taxonomy, [
					'slug'        => $term->slug,
					'description' => $term->desc,
				] );
				$term_id = $the_term['term_id'];
			} else {
				$the_term = get_term_by( 'slug', $term->slug, $taxonomy );
				$term_id  = $the_term->term_id;
			}

			$terms_to_insert[] = $term_id;
		}

		wp_set_object_terms( $post_id, $terms_to_insert, $taxonomy );
	}

	/**
	 * Add the CMB2 metabox for the API URL.
	 *
	 * @since 0.3
	 */
	public function add_import_metabox() {
		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				// These are important, don't remove.
				'key'   => 'options-page',
				'value' => array( $this->key ),
			),
		) );

		$cmb->add_field( array(
			'name'       => __( 'Recipe Box URL', 'recipe-box' ),
			'id'         => 'api_url',
			'type'       => 'text',
			'desc'       => '<a href="#" id="recipe-api-fetch" data-action="api-fetch">' . __( 'Fetch recipes', 'recipe-box' ) . '</a>',
			'attributes' => [
				'placeholder' => 'e.g. http://myrecipebox.com',
			],
		) );
	}

	/**
	 * Trim the data we're fetching to only include the stuff we actually want.
	 *
	 * @since  0.3
	 * @param  object $data    The WP-REST API data object.
	 * @param  object $post    The WP_Post object (not used).
	 * @param  string $context The context in which we are looking at this data.
	 * @return object          The updated REST API data object.
	 */
	public function trim_data( $data, $post, $context ) {
		// We only want to modify the 'view' context, for reading posts.
		if ( 'view' !== $context || is_wp_error( $data ) ) {
			return $data;
		}

		// Remove all the things we don't care about.
		$properties_to_remove = [
			'guid',
			'modified',
			'modified_gmt',
			'status',
			'type',
			'template',
			'_links',
		];

		foreach ( $properties_to_remove as $field ) {
			unset( $data[ $field ] );
		}

		return $data;
	}
}
