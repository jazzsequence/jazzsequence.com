<?php

class StoryFTW_IconSelect {

	protected $do_cta_js = false;

	public function __construct() {
		$this->fields = array(
			'title' => array(
				'name'    => __( 'Enter the destination URL', 'storyftw' ),
				'id'      => '',
				'type'    => 'title',
			),
			'url' => array(
				'name'    => __( 'Button URL', 'storyftw' ),
				'default' => 'http://',
				'id'      => 'url',
				'type'    => 'text_url',
			),
			'text' => array(
				'name'    => __( 'Button Text', 'storyftw' ),
				'default' => __( 'Click me!', 'storyftw' ),
				'id'      => 'text',
				'type'    => 'text',
			),
			'text_color' => array(
				'name'    => __( 'Text Color', 'storyftw' ),
				'id'      => 'text_color',
				'type'    => 'colorpicker',
			),
			'color' => array(
				'name'    => __( 'Button Color (optional)', 'storyftw' ),
				'id'      => 'color',
				'type'    => 'colorpicker',
			),
			'icon' => array(
				'name'     => __( 'Select an icon (optional)', 'storyftw' ),
				'desc'     => '<a href="http://melchoyce.github.io/dashicons/" target="_blank">'. __( 'Dashicons', 'storyftw' ) .'</a>',
				'id'       => 'icon',
				'type'     => 'select',
				'storyftw' => true,
				'options'  => array(),
			),
			'external' => array(
				'name'    => __( 'Open link in a new window/tab', 'storyftw' ),
				'id'      => 'external',
				'type'    => 'checkbox',
			),
		);

		$this->button_config = array(
			'icon'             => 'dashicons-lightbulb',
			'method'           => array( $this, 'cta' ),
			'button_text'      => 'story|ftw '. __( 'button', 'storyftw' ),
			'button_tooltip'   => 'story|ftw '. __( 'call to action button', 'storyftw' ),
			'l10ninsert'       => __( 'Insert Call to Action Link', 'storyftw' ),
			'metabox_fields'   => $this->fields,
		);
		// wp_die( '<xmp>: '. print_r( $this->get_dashicon_classes(), true ) .'</xmp>' );
	}

	public function hooks() {
		add_filter( 'cmb2_select_attributes', array( $this, 'get_icons' ), 10, 4 );
		add_action( 'admin_footer', array( $this, 'cta_js' ), 999 );
	}

	public function get_dashicon_classes() {
		global $wp_version;

		$classes = get_transient( 'storyftw_dashicon_classes_'. $wp_version );
		if ( $classes && ! isset( $_GET['delete-trans'] ) ) {
			return $classes;
		}
		if ( ! class_exists( 'CSSparse' ) ) {
			StoryFTW::include_file( 'libraries/parsecss/parseCSS' );
		}
		$CSS = new CSSparse();
		$CSS->parseFile( ABSPATH . '/wp-includes/css/dashicons.css' );

		$classes = array();

		if ( is_array( $CSS->css ) ) {
			foreach ( $CSS->css as $class => $css ) {
				if ( false === stripos( $class, '.dashicons-' ) ) {
					continue;
				}

				if ( false !== stripos( $class, '.dashicons,.dashicons-before:before' ) || '.dashicons-before:before' == $class ) {
					continue;
				}

				$class = str_ireplace( array( '.', ':before' ), '', $class );
				$label = str_ireplace( array( '.dashicons-', ':before', '-' ), array( '', '', ' ' ), $class );

				$classes[ $class ] = ucwords( $label );
			}
		}

		if ( ! empty( $classes ) ) {
			set_transient( 'storyftw_dashicon_classes_'. $wp_version, $classes, DAY_IN_SECONDS * 30 );
		}

		return $classes;
	}

	function get_icons( $args, $defaults, $field_object, $field_types_object = '' ) {

		if ( empty( $field_types_object ) ) {
			return $args;
		}

		if ( 'icon' != $field_object->_id() || ! $field_object->args( 'storyftw' ) ) {
			return $args;
		}

		$this->do_cta_js = true;

		$options_string = $field_types_object->option( __( '-- Select --', 'storyftw' ), '', true );

		foreach ( (array) $this->get_dashicon_classes() as $class => $label ) {
			$options_string .= sprintf( "\t".'<option value="%s"> %s</option>', $class, $label )."\n";
		}

		$defaults['options'] = $options_string;

		return $defaults;
	}

	public function cta_js() {
		if ( ! $this->do_cta_js ) {
			return;
		}

		wp_enqueue_script( 'select2', '//cdn.jsdelivr.net/select2/3.4.8/select2.min.js', array( '_shortcode_buttons_' ), StoryFTW::VERSION );
		wp_enqueue_style( 'select2', '//cdn.jsdelivr.net/select2/3.4.8/select2.css', null, StoryFTW::VERSION );

		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){

			var $iconSelect = $( '.cmb2_select#icon' );
			window.setTimeout( function(){
				if ( ! window.wp_sc_buttons || ! window.wp_sc_buttons.qt.storyftw_cta.buildShortCode ) {
					return;
				}

				function override( object, methodName, callback ) {
				  object[ methodName ] = callback( object[ methodName ] );
				}

				function before( extraBehavior ) {
					return function( original ) {
						return function() {
							if ( extraBehavior.apply( this, arguments ) ) {
								console.log( "you're good to go" );
								return original.apply( this, arguments );
							}
							return false;
						}
					}
				}

				override( window.wp_sc_buttons.qt.storyftw_cta, 'buildShortCode', before( function( params ) {
					if ( 'http://' === params.url || ! params.url ) {
						alert( '<?php _e( 'URL is required', 'storyftw' ); ?>' );
						return false;
					}

					if ( ! params.text || ! params.text.trim() ) {
						alert( '<?php _e( 'Button text is required', 'storyftw' ); ?>' );
						return false;
					}

					return true;
				}));

				window.wp_sc_buttons.qt.storyftw_cta.$.modal.on( 'dialogclose', function() {
					$iconSelect.select2( 'close' );
				});

			}, 1000 );

			var select2Img = function( option ) {
				var icon = $(option.element).val();
				if ( icon ) {
					return '<span class="dashicons-before '+ icon +'"></span> ' + option.text;
				}
				return option.text;
			};

			$iconSelect.select2({
				formatResult: select2Img,
				formatSelection: select2Img,
				escapeMarkup: function(m) { return m; }
			});

		});
		</script>
		<style type="text/css" media="screen">
		.select2-container.cmb2_select {
			display: block;
			width: 70%;
		}
		#select2-drop {
			z-index: 999999;
		}
		</style>
		<?php
	}

	public function cta( $atts = array() ) {

		$atts = $this->shortcode_atts( $atts  );

		$url = $atts['url'] ? $atts['url'] : '';

		if ( ! $atts['url'] || 'http://' === trim( $atts['url'] ) ) {
			return;
		}

		if ( ! $atts['text'] ) {
			return;
		}

		// $color = params.color ? ' style="background:'. $atts['color'] .';"' : '';
		$external = $atts['external'] ? ' target="_blank"' : '';
		$icon = $atts['icon'] ? 'dashicons-before '. $atts['icon'] : '';

		$style = ' style="';
		$style .= $atts['color'] ? 'background:'. $atts['color'] .';' : '';
		$style .= $atts['text_color'] ? 'color:'. $atts['text_color'] .';' : '';
		$style .= '"';


		$button = '<a class="btn light '. $icon .'" href="'. esc_url( $atts['url'] ) .'"' . $external . $style .'>'. $atts['text'] .'</a>';

		return $button;
	}

	public function shortcode_atts( $atts ) {
		$defaults = array();

		foreach ( $this->fields as $field ) {
			$defaults[ $field['id'] ] = isset( $field['default'] ) ? $field['default'] : '';
		}

		return shortcode_atts( $defaults, $atts, 'storyftw_cta'  );
	}

}
