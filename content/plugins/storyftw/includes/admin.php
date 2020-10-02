<?php
/**
 * Plugin Options Page
 *
 * @version 0.1.0
 */
class StoryFTW_Admin {

 	/**
 	 * Option key, and option page slug
 	 * @var string
 	 */
	private $key = 'storyftw_options';

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	protected $option_metabox = array();

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct( $cpt ) {
		$this->cpt = $cpt;
		// Set our title
		$this->title = __( 'story|ftw settings', 'storyftw' );
 	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
	}

	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 * @since 0.1.0
	 */
	public function add_options_page() {
		$this->options_page = add_submenu_page( 'edit.php?post_type='. $this->cpt->name, $this->title, $this->title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );
	}

	/**
	 * Admin page markup. Mostly handled by CMB
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2_options_page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->option_metabox(), $this->key, array( 'save_button' => __( 'Save Settings', 'storyftw' ) ) ); ?>
		</div>
		<?php
	}

	/**
	 * Defines the option metabox field configuration
	 * @since  0.1.0
	 * @return array
	 */
	public function option_fields() {
		return array(
			// array(
			// 	'name' => __( "Include <code>wp_head();</code><br>in Story's head?", 'storyftw' ),
			// 	'desc' => __( 'This will likely effect the display of the stories as wp_head loads theme and plugin stylesheet and script files. You may need this if you are looking for plugin functionality on story pages.', 'storyftw' ),
			// 	'id'   => 'wp_head',
			// 	'type' => 'checkbox',
			// ),
			array(
				'name' => __( "Remove <code>wp_footer();</code><br>from Story's footer?", 'storyftw' ),
				'desc' => __( 'Enabling this will remove the admin bar (if enabled) as well as other plugin functionality. You may need to enable this option if other plugins or your theme is conflicting with story pages.', 'storyftw' ),
				'id'   => 'wp_footer',
				'type' => 'checkbox',
			),
		);
	}

	/**
	 * Defines the option metabox configuration
	 * @since  0.1.0
	 * @return array
	 */
	public function option_metabox() {
		return array(
			'id'         => 'option_metabox',
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'show_names' => true,
			'fields'     => $this->option_fields(),
		);
	}


	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'fields', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}
		if ( 'option_metabox' === $field ) {
			return $this->option_metabox();
		}

		if ( 'option_fields' === $field ) {
			return $this->option_fields();
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function storyftw_get_option( $key = '' ) {
	global $StoryFTW_Admin;
	return cmb2_get_option( $StoryFTW_Admin->key, $key );
}
