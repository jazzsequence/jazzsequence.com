<?php
/**
 * Shortcode setup
 *
 * @version 0.1.0
 */
class StoryFTW_Shortcodes {

	protected $shortcodes = array();
	protected $buttons    = array();
	protected $fields     = array();
	protected $count      = 0;
	protected static $video_shortcodes = array();

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct( $frontend ) {
		$this->frontend        = $frontend;
		$this->cpts            = $this->frontend->cpts;
		$this->cpt             = $this->frontend->cpt;
		$this->ctasc           = new StoryFTW_IconSelect();
		$this->fields['video'] = $this->ctasc->fields;

		unset( $this->fields['video']['title'] );
		unset( $this->fields['video']['external'] );

		$this->fields['video']['url'] = array(
			'name' => __( 'Video URL', 'storyftw' ),
			'desc' => sprintf( __( 'For best results, enter a youtube, vimeo, or other oembed video URLs. Supports services listed at %s', 'storyftw' ), '<a href="http://codex.wordpress.org/Embeds">http://codex.wordpress.org/Embeds</a>' ),
			'id'   => 'url',
			'type' => 'oembed',
		);

		$this->fields['share'] = array(
			array(
				'name'    => __( 'Include Facebook Share Button?', 'storyftw' ),
				'id'      => 'share_fb',
				'type'    => 'checkbox',
			),
			array(
				'before_row'  => '<div class="toggle-hidden">',
				'name'    => __( 'Button Text', 'storyftw' ),
				'default' => __( 'Share', 'storyftw' ),
				'id'      => 'fb_btn_text',
				'type'    => 'text',
			),
			array(
				'name'    => __( 'Text Color', 'storyftw' ),
				'id'      => 'fb_btn_text_color',
				'type'    => 'colorpicker',
			),
			array(
				'name'    => __( 'Button Color', 'storyftw' ),
				'id'      => 'fb_btn_color',
				'type'    => 'colorpicker',
				'after_row'   => '</div>',
			),
			array(
				'name'    => __( 'Include Twitter Share Button?', 'storyftw' ),
				'id'      => 'share_tw',
				'type'    => 'checkbox',
			),
			array(
				'before_row'  => '<div class="toggle-hidden">',
				'name'    => __( 'Button Text', 'storyftw' ),
				'default' => __( 'Tweet', 'storyftw' ),
				'id'      => 'tw_btn_text',
				'type'    => 'text',
			),
			array(
				'name'    => __( 'Text Color', 'storyftw' ),
				'id'      => 'tw_btn_text_color',
				'type'    => 'colorpicker',
			),
			array(
				'name'    => __( 'Button Color', 'storyftw' ),
				'id'      => 'tw_btn_color',
				'type'    => 'colorpicker',
				'after_row'   => array( $this, 'share_script_style' ),
			),
			array(
				'name'    => __( 'Twitter Share Text (optional)', 'storyftw' ),
				'desc'    => __( 'Will default to the Tweet Text setting for this Story, or the Story\'s title, if there is no Tweet Text saved', 'storyftw' ),
				'id'      => 'share_tw_text',
				'type'    => 'textarea_small',
				'attributes' => array(
					'rows' => 2,
				),
			),
			array(
				'name'     => __( 'Include a share link for copying?', 'storyftw' ),
				'id'       => 'share_link',
				'default'  => true,
				'_default' => false,
				'type'     => 'checkbox',
			),
		);

		$this->fields['cta'] = $this->ctasc->fields;

		$this->shortcodes = array(
			'storyftw_video' => array(
				'icon'             => 'dashicons-video-alt3',
				'method'           => array( $this, 'video' ),
				'button_text'      => 'story|ftw '. __( 'video', 'storyftw' ),
				'button_tooltip'   => 'story|ftw '. __( 'video', 'storyftw' ),
				'metabox_config'   => array( $this, 'cmb_config' ),
				'metabox_fields'   => $this->fields['video'],
			),
			'storyftw_share' => array(
				// 'icon'        => StoryFTW::url( 'assets/images/storyftw.svg' ),
				'icon'             => 'dashicons-share-alt2',
				'method'           => array( $this, 'share' ),
				'button_text'      => 'story|ftw '. __( 'share', 'storyftw' ),
				'button_tooltip'   => 'story|ftw '. __( 'share', 'storyftw' ),
				'metabox_config'   => array( $this, 'cmb_config' ),
				'metabox_fields'   => $this->fields['share'],
			),
		);

		$this->shortcodes['storyftw_cta'] = $this->ctasc->button_config;
		$this->shortcodes['storyftw_cta']['metabox_config'] = array( $this, 'cmb_config' );

		StoryFTW::include_file( 'libraries/shortcode-button/shortcode-button' );

		foreach ( array_reverse( $this->shortcodes ) as $shortcode => $shortcode_button ) {
			$this->init_button( $shortcode, $shortcode_button );
		}

	}

	public function share_script_style() {
		?>
		</div>
		<style type="text/css" media="screen">
		#cmb2-metabox-shortcode_storyftw_share .toggle-hidden {
			display: none;
			background: #efefef;
			padding: 0 .6em 1.2em;
			border-top: 1px solid #aaa;
		}
		.ui-dialog .toggle-hidden .cmb-th {
			padding: .5em 0 0 1px;
		}
		#cmb2-metabox-shortcode_storyftw_share .toggle-hidden:after {
			content: '';
			display: block;
			width: 100%;
			clear: both;
		}
		.toggle-hidden .cmb-type-colorpicker {
			float: left;
			margin-right: 1em;
		}
		</style>
		<script type="text/javascript">
		jQuery(document).ready(function($){

			$share_dialog = $( document.getElementById( 'cmb2-metabox-shortcode_storyftw_share' ) );
			$toggle = $share_dialog.find( '.toggle-hidden' );
			$clear = $share_dialog.find( '.wp-picker-clear' );

			$( '#share_fb, #share_tw' ).on( 'change', function( evt ) {
				$this = $( this );
				$hidden = $this.parents( '.cmb-row' ).next();
				if ( $this.is( ':checked' ) ) {
					$hidden.show();
				} else {
					$hidden.hide();
				}
			});

			window.wp_sc_buttons.qt.storyftw_share.$.modal.on( 'dialogclose', function() {
				$toggle.hide().find( 'input.regular-text' ).val( '' );
				$clear.trigger( 'click' );
			});

		});
		</script>
		<?php
	}

	public function init_button( $shortcode, $shortcode_button ) {
		$button_args = array(
			'icon'                 => $shortcode_button['icon'],
			'qt_button_text'       => $shortcode_button['button_text'],
			'button_tooltip'       => $shortcode_button['button_tooltip'],
			'author'               => 'story|ftw',
			'authorurl'            => 'http://storyftw.com',
			'infourl'              => 'http://storyftw.com',
			'version'              => StoryFTW::VERSION,
		);
		if ( isset( $shortcode_button['l10ninsert'] ) ) {
			$button_args['l10ninsert'] = $shortcode_button['l10ninsert'];
		}
		$additional_args = array(
			'cmb_metabox_config'   => $shortcode_button['metabox_config'],
			'conditional_callback' => array( $this->cpt, 'is_story_page_single' ),
		);

		$this->buttons[ $shortcode ] = new _Shortcode_Button_( $shortcode, $button_args, $additional_args );

		return $this->buttons[ $shortcode ];
	}

	public function cmb_config( $button ) {

		return array(
			'id'      => 'shortcode_'. $button['slug'],
			'fields'  => $this->shortcodes[ $button['slug'] ]['metabox_fields'],
			'show_on' => array( 'key' => 'options-page', 'value' => $button['slug'] ),
		);
	}

	public function hooks() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_filter( 'storyftw_share_shortcode_fields', array( $this, 'parse_share_fields' ) );
		$this->ctasc->hooks();
	}

	public function register_shortcodes() {
		if ( is_admin() && ! $this->cpt->is_story_page_single() ) {
			return;
		}

		foreach ( $this->shortcodes as $shortcode => $info ) {
			// if ( 'cta' != $shortcode ) {
				add_shortcode( $shortcode, $info['method'] );
			// }
		}

	}

	public function parse_share_fields( $fields ) {

		if ( isset( $fields['share_tw_text'] ) && ! empty( $fields['share_tw_text'] ) ) {
			$fields['share_tw'] = $fields['share_tw_text'];
		}

		unset( $fields['share_tw_text'] );

		if ( ! isset( $fields['share_fb'] ) ) {
			unset( $fields['fb_btn_text'] );
			unset( $fields['fb_btn_text_color'] );
			unset( $fields['fb_btn_color'] );
		}
		if ( ! isset( $fields['share_tw'] ) ) {
			unset( $fields['tw_btn_text'] );
			unset( $fields['tw_btn_text_color'] );
			unset( $fields['tw_btn_color'] );
		}

		return $fields;
	}

	public function is_true( $value ) {
		return $value && 'false' !== $value && 'no' !== $value;
	}

	public function shortcode_atts( $type, $atts ) {
		$defaults = array();

		foreach ( $this->fields[ $type ] as $field ) {
			$defaults[ $field['id'] ] = '';
			if ( isset( $field['_default'] ) ) {
				$defaults[ $field['id'] ] = $field['_default'];
			} elseif ( isset( $field['default'] ) ) {
				$defaults[ $field['id'] ] = $field['default'];
			}
		}

		return shortcode_atts( $defaults, $atts, 'storyftw_' . $type );
	}


	/**
	 * Video Shortcode
	 */

	public function video( $atts = array() ) {

		$atts = $this->shortcode_atts( __FUNCTION__, $atts  );

		$embed_url = $atts['url'] ? $atts['url'] : '';

		if ( ! $embed_url ) {
			return;
		}

		$id = $this->count++;
		self::$video_shortcodes[ $id ] = $embed_url;

		add_action( 'storyftw_footer', array( $this, 'video_modal' ) );

		$classes = $atts['icon'] ? 'dashicons-before '. $atts['icon'] : '';
		$classes .= $atts['color'] ? '' : ' bg-dynamic-a';

		$style = ' style="';
		$style .= $atts['color'] ? 'background:'. esc_attr( $atts['color'] ) .';' : 'background:'. $this->frontend->get_first_background_color( false, false ) .';';
		$style .= $atts['text_color'] ? 'color:'. esc_attr( $atts['text_color'] ) .';' : '';
		$style .= '"';

		return sprintf( '<a data-vidid="storyftw-modal-%d" class="js-show-modal btn light %s" %s>%s</a>', $id, $classes, $style, esc_html( $atts['text'] ) );
	}

	public function video_modal() {
		static $done;

		if ( $done || empty( self::$video_shortcodes ) ) {
			return;
		}

		foreach ( self::$video_shortcodes as $id => $url ) {

			$is_oembed    = $embed = wp_oembed_get( $url );
			$video_source = $is_oembed ? '' : 'data-src="'. esc_url( $url ) .'"';
			?>
			<div id="storyftw-modal-<?php echo $id; ?>" class="js-modal fade-in absolute-fill hide bg-black" <?php echo $video_source; ?>>
				<a class="js-hide-modal absolute t0 r0 z2 inline-block p1 clickable light"><div class="dashicons dashicons-no"></div></a>
				<div class="table y100">
					<div class="js-embed-wrap table-cell px2"></div>
					<?php if ( $is_oembed ) : ?>
					<script type="text/template" class="tmpl-videoModal">
						<?php echo $embed; ?>
					</script>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		$done = true;
	}


	/**
	 * Share Shortcode
	 */

	public function share( $atts = array() ) {

		$url = isset( $atts['share_url'] ) ? esc_url( $atts['share_url'] ) : false;
		$atts = $this->shortcode_atts( __FUNCTION__, $atts  );

		$fb = $tw = $html = '';

		if ( $this->is_true( $atts['share_fb'] ) ) {

			$args    = array( 'echo' => false );
			$style   = '';
			$classes = array(
				'btn'                   => 'btn',
				'bg-dynamic'            => 'bg-dynamic',
				'white'                 => 'white',
				'facebook-share-button' => 'facebook-share-button',
			);

			if ( trim( $atts['fb_btn_text'] ) ) {
				$args['button_text'] = trim( $atts['fb_btn_text'] );
			}

			if ( trim( $atts['fb_btn_text_color'] ) ) {
				$style .= 'color:'. trim( $atts['fb_btn_text_color'] ) .';';
				unset( $classes['white'] );
			}

			if ( trim( $atts['fb_btn_color'] ) ) {
				$style .= 'background:'. trim( $atts['fb_btn_color'] ) .';';
				unset( $classes['bg-dynamic'] );
			}

			if ( trim( $atts['fb_btn_text'] ) ) {
				$args['button_text'] = trim( $atts['fb_btn_text'] );
			}

			if ( $style ) {
				$args['button_color'] = 'style="'. $style .'"';
			}

			if ( $url ) {
				$args['share_url'] = esc_url( $url );
			}

			$args['classes'] = implode( ' ', $classes );
			$fb = $this->frontend->facebook_button( $args );
		}

		if ( $this->is_true( $atts['share_tw'] ) ) {

			$args    = array( 'echo' => false );
			$style   = '';
			$classes = array(
				'btn'          => 'btn',
				'bg-dynamic'   => 'bg-dynamic',
				'white'        => 'white',
				'tweet-button' => 'tweet-button',
			);

			if ( is_string( $atts['share_tw'] ) && 'true' !== $atts['share_tw'] ) {
				$args['tweet_text'] = $atts['share_tw'];
			}

			if ( trim( $atts['tw_btn_text'] ) ) {
				$args['button_text'] = trim( $atts['tw_btn_text'] );
			}

			if ( trim( $atts['tw_btn_text_color'] ) ) {
				$style .= 'color:'. trim( $atts['tw_btn_text_color'] ) .';';
				unset( $classes['white'] );
			}

			if ( trim( $atts['tw_btn_color'] ) ) {
				$style .= 'background:'. trim( $atts['tw_btn_color'] ) .';';
				unset( $classes['bg-dynamic'] );
			}

			if ( $style ) {
				$args['button_color'] = 'style="'. $style .'"';
			}

			if ( $url ) {
				$args['share_url'] = esc_url( $url );
			}

			$args['classes'] = implode( ' ', $classes );
			$tw = $this->frontend->tweet_button( $args );
		}

		if ( $this->is_true( $atts['share_tw'] ) || $this->is_true( $atts['share_fb'] ) ) {
			$html .= sprintf( '<p class="mb2">%s%s</p>', $fb, $tw );
		}

		if ( $this->is_true( $atts['share_link'] ) ) {
			$url = $url ? $url : esc_url( get_permalink( $this->frontend->post ) );
			$html .= '
			<input class="share_url input center bg-white" onclick="this.focus();this.select();" readonly="readonly" type="text" value="'. $url .'" size="30">
			';
		}

		return $html;
	}

	public function __get( $property ) {
		switch( $property ) {
			case 'shortcodes':
			case 'buttons':
			case 'fields':
				return $this->{$property};
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $property );
		}
	}
}
