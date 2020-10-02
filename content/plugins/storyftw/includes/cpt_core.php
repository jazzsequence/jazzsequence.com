<?php
/**
 * Plugin class for generating Custom Post Types.
 * @version 0.2.0
 * @author  Justin Sternberg
 */
class storyftw_cpt_core {

	/**
	 * Singlur CPT label
	 * @var string
	 */
	private $singular;

	/**
	 * Plural CPT label
	 * @var string
	 */
	private $plural;

	/**
	 * Registered CPT name/slug
	 * @var string
	 */
	private $post_type;

	/**
	 * Optional argument overrides passed in from the constructor.
	 * @var array
	 */
	private $arg_overrides = array();

	/**
	 * All CPT registration arguments
	 * @var array
	 */
	private $cpt_args = array();

	/**
	 * An array of each storyftw_cpt_core object registered with this class
	 * @var array
	 */
	private static $custom_post_types = array();

	/**
	 * Constructor. Builds our CPT.
	 * @since 0.1.0
	 * @param mixed  $cpt           Array with Singular, Plural, and Registered (slug)
	 * @param array  $arg_overrides CPT registration override arguments
	 */
	public function __construct( array $cpt, $arg_overrides = array() ) {

		if ( ! is_array( $cpt ) ) {
			wp_die( __( 'It is required to pass a single, plural and slug string to storyftw_cpt_core', 'storyftw' ) );
		}

		if ( ! isset( $cpt[0], $cpt[1], $cpt[2] ) ) {
			wp_die( __( 'It is required to pass a single, plural and slug string to storyftw_cpt_core', 'storyftw' ) );
		}

		if ( ! is_string( $cpt[0] ) || ! is_string( $cpt[1] ) || ! is_string( $cpt[2] ) ) {
			wp_die( __( 'It is required to pass a single, plural and slug string to storyftw_cpt_core', 'storyftw' ) );
		}

		$this->singular  = $cpt[0];
		$this->plural    = !isset( $cpt[1] ) || !is_string( $cpt[1] ) ? $cpt[0] .'s' : $cpt[1];
		$this->post_type = !isset( $cpt[2] ) || !is_string( $cpt[2] ) ? sanitize_title( $this->plural ) : $cpt[2];

		$this->arg_overrides = (array) $arg_overrides;

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );
		add_filter( 'manage_edit-'. $this->post_type .'_columns', array( $this, 'columns' ) );

		// Different column registration for pages/posts
		$h = isset( $arg_overrides['hierarchical'] ) && $arg_overrides['hierarchical'] ? 'pages' : 'posts';
		add_action( "manage_{$h}_custom_column", array( $this, 'columns_display' ) );

		add_filter( 'enter_title_here', array( $this, 'title' ) );
	}

	/**
	 * Gets the passed in arguments combined with our defaults.
	 * @since  0.2.0
	 * @return array  CPT arguments array
	 */
	public function get_args() {
		if ( ! empty( $this->cpt_args ) )
			return $this->cpt_args;

		// Generate CPT labels
		$labels = array(
			'name'               => $this->plural,
			'singular_name'      => $this->singular,
			'add_new'            => sprintf( __( 'Add New %s', 'storyftw' ), $this->singular ),
			'add_new_item'       => sprintf( __( 'Add New %s', 'storyftw' ), $this->singular ),
			'edit_item'          => sprintf( __( 'Edit %s', 'storyftw' ), $this->singular ),
			'new_item'           => sprintf( __( 'New %s', 'storyftw' ), $this->singular ),
			'all_items'          => sprintf( __( 'All %s', 'storyftw' ), $this->plural ),
			'view_item'          => sprintf( __( 'View %s', 'storyftw' ), $this->singular ),
			'search_items'       => sprintf( __( 'Search %s', 'storyftw' ), $this->plural ),
			'not_found'          => sprintf( __( 'No %s', 'storyftw' ), $this->plural ),
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'storyftw' ), $this->plural ),
			'parent_item_colon'  => isset( $this->arg_overrides['hierarchical'] ) && $this->arg_overrides['hierarchical'] ? sprintf( __( 'Parent %s:', 'storyftw' ), $this->singular ) : null,
			'menu_name'          => $this->plural,
		);

		// Set default CPT parameters
		$defaults = array(
			'labels'             => array(),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'has_archive'        => true,
			'supports'           => array( 'title', 'editor', 'excerpt' ),
		);

		$this->cpt_args = wp_parse_args( $this->arg_overrides, $defaults );
		$this->cpt_args['labels'] = wp_parse_args( $this->cpt_args['labels'], $labels );

	return $this->cpt_args;
	}

	public function get_arg( $arg ) {
		$args = $this->get_args();
		if ( is_array( $args ) ) {
			return array_key_exists( $arg, $args ) ? $args[ $arg ] : false;
		}
		if ( is_object( $args ) ) {
			return isset( $args->{$arg} ) ? $args->{$arg} : false;
		}
	}

	/**
	 * Actually registers our CPT with the merged arguments
	 * @since  0.1.0
	 */
	public function register_post_type() {
		// Register our CPT
		$args = register_post_type( $this->post_type, $this->get_args() );
		// If error, yell about it.
		if ( is_wp_error( $args ) ) {
			wp_die( $args->get_error_message() );
		}

		// Success. Set args to what WP returns
		$this->cpt_args = $args;

		// Add this post type to our custom_post_types array
		self::$custom_post_types[ $this->post_type ] = $this;
	}

	/**
	 * Modies CPT based messages to include our CPT labels
	 * @since  0.1.0
	 * @param  array  $messages Array of messages
	 * @return array            Modied messages array
	 */
	public function messages( $messages ) {
		global $post, $post_ID;

		$messages[$this->post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( '%1$s updated. <a href="%2$s">View %1$s</a>', 'storyftw' ), $this->singular, esc_url( get_permalink( $post_ID ) ) ),
			2 => __( 'Custom field updated.', 'storyftw' ),
			3 => __( 'Custom field deleted.', 'storyftw' ),
			4 => sprintf( __( '%1$s updated.', 'storyftw' ), $this->singular ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( __( '%1$s restored to revision from %2$s', 'storyftw' ), $this->singular , wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( '%1$s published. <a href="%2$s">View %1$s</a>', 'storyftw' ), $this->singular, esc_url( get_permalink( $post_ID ) ) ),
			7 => sprintf( __( '%1$s saved.', 'storyftw' ), $this->singular ),
			8 => sprintf( __( '%1$s submitted. <a target="_blank" href="%2$s">Preview %1$s</a>', 'storyftw' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9 => sprintf( __( '%1$s scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$s</a>', 'storyftw' ), $this->singular,
					// translators: Publish box date format, see http://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'storyftw' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>', 'storyftw' ), $this->singular, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * Registers admin columns to display. To be overridden by an extended class.
	 * @since  0.1.0
	 * @param  array  $columns Array of registered column names/labels
	 * @return array           Modified array
	 */
	public function columns( $columns ) {
		// placeholder
		return $columns;
	}

	/**
	 * Handles admin column display. To be overridden by an extended class.
	 * @since  0.1.0
	 * @param  array  $column Array of registered column names
	 */
	public function columns_display( $column ) {
		// placeholder
	}

	/**
	 * Filter CPT title entry placeholder text
	 * @since  0.1.0
	 * @param  string $title Original placeholder text
	 * @return string        Modifed placeholder text
	 */
	public function title( $title ){
		$screen = get_current_screen();

		if ( isset( $screen->post_type ) && $screen->post_type == $this->post_type ) {
			return sprintf( __( '%s Title', 'storyftw' ), $this->singular );
		}

		return $title;
	}

	/**
	 * Provides access to all storyftw_cpt_core taxonomy objects registered via this class.
	 * @since  0.1.0
	 * @param  string $post_type Specific storyftw_cpt_core object to return, or 'true' to specify only names.
	 * @return mixed             Specific storyftw_cpt_core object or array of all
	 */
	public function custom_post_types( $post_type = '' ) {
		if ( $post_type === true && ! empty( self::$custom_post_types ) ) {
			return array_keys( self::$custom_post_types );
		}
		return isset( self::$custom_post_types[ $post_type ] ) ? self::$custom_post_types[ $post_type ] : self::$custom_post_types;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param string $field
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'singular':
			case 'plural':
			case 'post_type':
				return $this->{$field};
			case 'labels':
				return $this->get_arg( $field );
			case 'name':
				return $this->post_type;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

	/**
	 * Magic method that returns the CPT registered name when treated like a string
	 * @since  0.2.0
	 * @return string CPT registered name
	 */
	public function __toString() {
		return $this->post_type;
	}

}
