<?php
/**
 * Story Template Bypass
 *
 * @version 0.1.0
 */
class StoryFTW_Frontend {

	public $story_image = null;
	public $do_footer = false;

	public $whitelist_css = array(
		'open-sans',
		'dashicons',
		'admin-bar',
		'tt-easy-google-fonts-css',
		'storyftw-basic',
	);

	public $whitelist_js = array(
		'jquery-core',
		'jquery-migrate',
		'admin-bar',
		'flipsnap',
		'modernizr',
		'fastclick',
		'fitvids',
		'vendor-combined',
		'storyftw',
	);

	public $stylesheet_handle = null;

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	public function __construct( $cpts ) {
		$this->cpts = $cpts;
		$this->cpt  = $cpts->stories;
		$this->prefix = $this->cpt->prefix;
 	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_template_takeover' ), 9999 );
	}

	function maybe_template_takeover() {
		global $post;

		if ( ! isset( $post->ID ) ) {
			return;
		}

		if ( ! $this->story_id = $this->cpt->get_story_id_from_meta( $post->ID ) ) {
			return;
		}

		$this->cpt->story_id = $this->story_id;

		if ( ! ( $this->story = get_post( $this->story_id ) ) || is_wp_error( $this->story ) ) {
			return;
		}

		$this->post = $post;

		$this->story_hooks();


		include 'templates/index.php';
		exit;
	}

	public function story_hooks() {

		if ( $this->story_meta( 'header_scripts' ) ) {
			add_action( 'storyftw_head', array( $this, 'header_scripts' ) );
		}

		if ( $this->story_meta( 'footer_scripts' ) ) {
			add_action( 'storyftw_footer', array( $this, 'footer_scripts' ) );
		}

		if ( $this->story_meta( 'disable_wp_head' ) ) {
			// Re-attach wp_head hooks to storftw_head
			add_action( 'storyftw_head', 'wp_enqueue_scripts', 1 );
			add_action( 'storyftw_head', 'wp_print_styles', 8 );
			add_action( 'storyftw_head', 'wp_print_head_scripts', 9 );

			// Easy google fonts hook
			if ( class_exists( 'EGF_Frontend' ) ) {
				add_action( 'storyftw_head', array( EGF_Frontend::get_instance(), 'output_styles' ), 999 );
			}

			add_filter( 'style_loader_tag', array( $this, 'whitelist_css_loading' ), 10, 2 );
			add_filter( 'script_loader_src', array( $this, 'whitelist_script_loading' ), 10, 2 );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ), 55 );
		add_action( 'storyftw_before_story_page', array( $this, 'do_footer_check' ) );
		add_action( 'storyftw_toc', array( $this, 'toc' ) );
		add_action( 'storyftw_top', array( $this, 'css_output' ) );
		add_action( 'storyftw_toc_item', array( $this, 'toc_item' ) );
		add_action( 'storyftw_page_footer', array( $this, 'story_page_footer' ) );
		add_action( 'storyftw_nav_prev', array( $this, 'nav_prev' ) );
		add_action( 'storyftw_nav_next', array( $this, 'nav_next' ) );
		add_action( 'storyftw_navbar_left', array( $this, 'maybe_nav_button' ) );
		add_action( 'storyftw_navbar_right', array( $this, 'maybe_facebook_button' ) );
		add_action( 'storyftw_navbar_right', array( $this, 'maybe_twitter_button' ) );
		add_action( 'storyftw_footer_title', array( $this, 'footer_title' ) );
		add_action( 'storyftw_after_loop', array( $this, 'maybe_setup_redirect' ) );
		add_action( 'admin_bar_menu', array( $this, 'menu_item' ), 99999 );
		add_action( 'wp_after_admin_bar_render', array( $this, 'style_menu_item' ), 99999 );

		if ( ! is_admin_bar_showing() ) {
			add_action( 'storyftw_inside_wrap_after', array( $this, 'add_edit_link' ) );
		}

		if ( $this->story_meta( 'disable_wp_footer' ) ) {
			add_action( 'storyftw_footer', 'wp_admin_bar_render', 1000 );
			add_action( 'storyftw_footer', 'wp_print_footer_scripts', 20 );
		}

		if ( is_user_logged_in() ) {
			add_action( 'storyftw_head', array( $this, 'logged_in_remove_admin_bar_offset' ) );
		}
	}

	public function logged_in_remove_admin_bar_offset() {
		?>
		<style type="text/css" media="screen"> #storyftw-story { margin: 0 !important; } </style>
		<?php
	}


	public function whitelist_css() {
		return apply_filters( 'storftw_whitelist_css', $this->whitelist_css );
	}

	public function whitelist_css_loading( $tag, $handle ) {
		if ( in_array( $handle, $this->whitelist_css(), true ) || false !== stripos( $handle, 'storyftw' ) ) {
			return $tag;
		}
		return '';
	}

	public function whitelist_scripts() {
		return apply_filters( 'storftw_whitelist_scripts', $this->whitelist_js );
	}

	public function whitelist_script_loading( $tag, $handle ) {
		if ( in_array( $handle, $this->whitelist_scripts(), true ) || false !== stripos( $handle, 'storyftw' ) ) {
			return $tag;
		}
		return '';
	}

	public function maybe_setup_redirect() {
		if ( $url = $this->story_meta( 'story_redirect' ) ) {
			echo '<div id="story-page-redirect" class="story-page" data-redirect="'. esc_url( $url ) .'"></div>';
		}
	}

	public function toc() {
		global $post;
		?>
		<ul id="toc" class="list-simple center mb4">

		<?php do_action( 'storyftw_toc_start' ); ?>

		<?php foreach ( $this->get_story_pages() as $post ) : setup_postdata( $post ); ?>
			<?php do_action( 'storyftw_toc_item', $post ); ?>
		<?php endforeach; wp_reset_postdata(); ?>

		<?php do_action( 'storyftw_toc_end' ); ?>

		</ul>
		<?php
	}

	public function toc_item() {
		if ( $this->page_meta( 'exclude_nav' ) ) {
			return;
		}
		?>
		<li>
			<a href="#<?php $this->story_page_slug() ?>" class="h2 lh5 block js-story-page-btn gray"><?php echo esc_html( get_the_title() ); ?></a>
		</li>
		<?php
	}

	public function do_footer_check( $page_number ) {
		if ( 1 === $page_number ) {
			$this->do_footer = (
				$this->story_meta( 'footer_title' )
				|| $this->story_meta( 'enable_toc' )
				|| $this->social( 'facebook' )
				|| $this->social( 'twitter' )
				|| $this->page_meta( 'footer_title' )
				|| $this->page_meta( 'enable_toc' )
				|| $this->social( 'facebook', true )
				|| $this->social( 'twitter', true )
			);
		}
	}

	public function maybe_hide_footer() {
		if ( ! $this->do_footer ) {
			echo ' super-hide';
		}
	}

	public function footer_title() {
		$hidden = $this->story_meta( 'footer_title' ) ? '' : ' super-hide';
		$maybe_logo = $this->story_meta( 'footer_logo_id' );
		$footer = $maybe_logo
			? wp_get_attachment_image( $maybe_logo, 'storyftw-footer-log' )
			: get_the_title( $this->story_id );

		$footer = $this->story_meta( 'footer_url' )
			? '<a href="'. esc_url( $this->story_meta( 'footer_url' ) ) .'">'. $footer .'</a>'
			: $footer;
		?>
		<div class="h3 caps center mobile-hide footer-text-color <?php echo $hidden; ?>">
			<?php echo $footer; ?>
		</div>
		<?php
	}

	public function maybe_nav_button() {
		global $post;

		// Get the story pages
		$pages = $this->get_story_pages();
		// And set global post to the first page so that page_meta works correctly
		$post = $pages[0];

		$args = array( 'classes' => 'js-shifty-toggle btn mr2 bg-dynamic light footer-button-text-color' );
		if ( ! $this->story_meta( 'enable_toc' ) && ! $this->page_meta( 'enable_toc' ) ) {
			$args['classes'] .= ' super-hide';
		}

		$this->nav_button( $args );
	}

	public function maybe_facebook_button() {
		$args = array( 'classes' => 'btn bg-dynamic light facebook-share-button footer-button-text-color' );
		if ( ! $this->social( 'facebook' ) && ! $this->social( 'facebook', true ) ) {
			$args['classes'] .= ' super-hide';
		}

		$this->facebook_button( $args );
	}

	public function maybe_twitter_button() {
		$args = array( 'classes' => 'btn bg-dynamic light tweet-button footer-button-text-color' );
		if ( ! $this->social( 'twitter' ) && ! $this->social( 'twitter', true ) ) {
			$args['classes'] .= ' super-hide';
		}

		$this->tweet_button( $args );
	}

	public function nav_prev() {
		?>
		<div class="table-cell">
		  <div class="nav-prev-arrow nav-arrow light dashicons dashicons-arrow-left-alt2 arrow-color" title="<?php _e( 'Previous', 'storyftw' ); ?>"></div>
		</div>
		<?php
	}

	public function nav_next() {
		if ( $this->story_meta( 'coach_text' ) ) {
			echo '<p class="js-coach-mark table-cell bold light hide arrow-color">'. $this->story_meta( 'coach_text' ) .'</p>';
		}
		?>
		<div class="table-cell">
		  <div class="nav-next-arrow nav-arrow light dashicons dashicons-arrow-right-alt2 arrow-color" title="<?php _e( 'Next', 'storyftw' ); ?>"></div>
		</div>
		<?php
	}

	public function add_edit_link() {
		edit_post_link( __( 'Edit', 'storyftw' ), '<strong>', '</strong>' );
	}

	public function menu_item() {
		global $wp_admin_bar;

		$pages = $this->get_story_pages();
		if ( ! $pages || ! is_array( $pages ) ) {
			return;
		}

		$edit_link = get_edit_post_link( $pages[0]->ID );
		$edit_link_replace = str_ireplace( $pages[0]->ID, 'idreplace', $edit_link );
		$wp_admin_bar->add_menu( array(
			'id' => 'storyftw-edit-page',
			'title' => '<span data-href="'. esc_url( $edit_link_replace ) .'" class="edit-story-page-menu dashicons-before dashicons-edit"></span> '. $this->cpts->story_pages->labels->edit_item,
			'href' => esc_url( $edit_link ),
			'position' => 0
		) );

		$wp_admin_bar->add_menu( array(
			'id' => 'storyftw-edit',
			'parent' => 'storyftw-edit-page',
			'title' => '<span title="'. sprintf( __( 'Edit &ldquo;%s&rdquo;', 'storyftw' ), get_the_title( $this->story_id ) ) .'"><span class="edit-story-page-menu dashicons-before dashicons-edit"></span> '. $this->cpt->labels->edit_item .'</span>',
			'href' => esc_url( get_edit_post_link( $this->story_id ) ),
			'position' => 0
		) );

	}

	public function style_menu_item() {
		?>
		<style type="text/css" media="screen">
			#wpadminbar .edit-story-page-menu {
				display: inline-block;
				line-height: 1.65em;
			}
			#wpadminbar .edit-story-page-menu:before {
				font-size: 1.6em;
				margin-right: 3px;
				color: #999;
			}
		</style>
		<?php
	}

	public function scripts_styles() {
		$StoryFTW = StoryFTW::start();

		if ( ! $this->story_meta( 'include_theme_style' ) ) {
			$this->dequeue_theme_styles_dependencies();
		} elseif ( $this->story_meta( 'disable_wp_head' ) ) {
			$this->enqueue_theme_styles_dependencies();
		}


		$prefix = StoryFTW::start()->minnified_suffix;
		if ( $this->story_meta( 'css_override' ) ) {
			$prefix = '-override'. $prefix;
		}

		wp_enqueue_style( 'storyftw-basic', StoryFTW::url( "assets/css/storyftw{$prefix}.css" ), array( 'dashicons' ), StoryFTW::VERSION );

		if ( $StoryFTW->minnified_suffix ) {

			wp_register_script( 'vendor-combined', StoryFTW::url( 'assets/js/vendor-combined.min.js' ), array( 'jquery' ), StoryFTW::VERSION, true );

			$dependencies = array( 'vendor-combined' );

		} else {

			wp_register_script( 'flipsnap', StoryFTW::url( 'assets/js/vendor/flipsnap.js' ), null, StoryFTW::VERSION, true );
			wp_register_script( 'modernizr', StoryFTW::url( 'assets/js/vendor/modernizr.custom.js' ), null, StoryFTW::VERSION, true );
			wp_register_script( 'fastclick', StoryFTW::url( 'assets/js/vendor/fastclick.js' ), null, StoryFTW::VERSION, true );
			wp_register_script( 'fitvids', StoryFTW::url( 'assets/js/vendor/jquery.fitvids.js' ), array( 'jquery' ), StoryFTW::VERSION, true );

			$dependencies = array( 'flipsnap', 'modernizr', 'fastclick', 'fitvids', 'jquery' );
		}

		wp_enqueue_script( 'storyftw', StoryFTW::url( "assets/js/storyftw{$StoryFTW->minnified_suffix}.js" ), $dependencies, StoryFTW::VERSION, true );

		$index_names = wp_list_pluck( $this->get_story_pages(), 'post_name' );

		$l10n = array(
			'debug'       => ! $StoryFTW->minnified_suffix,
			'index_names' => $index_names,
			'names_index' => array_flip( $index_names ),
			'story'       => array(
				'footertitleshow'       => $this->story_meta( 'footer_title' ),
				'tocshow'               => $this->story_meta( 'enable_toc' ),
				'fbshow'                => !! $this->social( 'facebook' ),
				'twshow'                => !! $this->social( 'twitter' ),
				'linkcolor'             => $this->story_meta( 'link_color' ),
				'arrowcolor'            => $this->story_meta( 'arrow_color', '#fffff' ),
				'footertextcolor'       => $this->story_meta( 'footer_text_color', '#fffff' ),
				'footerbuttontextcolor' => $this->story_meta( 'footer_button_text_color', '#fffff' ),
			),
		);
		wp_localize_script( 'storyftw', 'StoryFTW_l10n', $l10n );

	}

	public function enqueue_theme_styles_dependencies() {
		global $wp_styles;

		if ( $stylesheet_handle = $this->get_theme_stylesheet_handle() ) {
			$this->whitelist_css[] = $stylesheet_handle;
			foreach ( $wp_styles->registered as $handle => $info ) {
				if ( ! empty( $info->deps ) && in_array( $stylesheet_handle, $info->deps ) ) {
					$this->whitelist_css[] = $info->handle;
				}
			}
		}
	}

	public function dequeue_theme_styles_dependencies() {
		global $wp_styles;

		if ( $stylesheet_handle = $this->get_theme_stylesheet_handle() ) {
			wp_deregister_style( $stylesheet_handle );
			wp_dequeue_style( $stylesheet_handle );

			foreach ( $wp_styles->registered as $handle => $info ) {
				if ( ! empty( $info->deps ) && in_array( $stylesheet_handle, $info->deps ) ) {
					wp_deregister_style( $info->handle );
					wp_dequeue_style( $info->handle );
				}
			}
		}
	}

	public function get_theme_stylesheet_handle() {
		if ( $this->stylesheet_handle ) {
			return $this->stylesheet_handle;
		}

		global $wp_styles;

		$style_sheet_url = parse_url( get_stylesheet_uri() );
		if ( isset( $style_sheet_url['path'], $wp_styles->registered ) ) {
			foreach ( $wp_styles->registered as $handle => $info ) {
				if ( isset( $info->src ) && false !== stripos( $info->src, $style_sheet_url['path'] ) ) {
					$this->stylesheet_handle = $info->handle;
					break;
				}
			}
		}

		return $this->stylesheet_handle;
	}

	public function get_story_image() {
		if ( null !== $this->story_image ) {
			return $this->story_image;
		}

		$this->story_image = false;

		if ( $id = get_post_thumbnail_id( $this->story_id ) ) {
			$img_array = wp_get_attachment_image_src( $id, 'full' );
			$this->story_image = isset( $img_array[0] ) ? $img_array[0] : false;
		}
		return $this->story_image;
	}

	public function get_story_pages() {
		if ( isset( $this->pages ) ) {
			return $this->pages;
		}
		$this->pages = $this->cpt->pages( array( 'fields' => 'all' ) );
		return $this->pages;
	}

	public function set_to_first_background_color( $transparency = false, $echo = true ) {

		$color = $this->get_first_background_color( $transparency );

		$style = 'style="background:'. $color .'"';

		if ( $echo ) {
			echo $style;
		}

		return $style;
	}

	public function get_first_background_color( $transparency = false, $echo = true ) {

		$color = $this->first_background_color();
		$color = $this->hex2rgb( ( $color ? $color : '#000' ), $transparency  );

		return $color;
	}

	public function first_background_color() {
		$pages = $this->get_story_pages();
		if ( ! $pages || ! is_array( $pages ) ) {
			return;
		}

		if ( isset( $this->first_background_color ) ) {
			return $this->first_background_color;
		}

		$color = get_post_meta( $pages[0]->ID, $this->prefix .'background', 1 );

		$this->first_background_color = $color ? $color : $this->story_meta( 'fallback_bg_color', '#000' );

		return $this->first_background_color;
	}

	public function social( $service = '', $page_meta = false ) {
		$story_social = $page_meta
			? (array) $this->page_meta( 'social' )
			: (array) $this->story_meta( 'social' );
		return $service ? in_array( $service, $story_social ) : ! empty( $story_social );
	}

	public function nav_button( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'echo'         => true,
			'button_text'  => $this->page_meta( 'nav_button_text', $this->story_meta( 'nav_button_text', __( 'View all', 'storyftw' ) ) ),
			'button_color' => $this->set_to_first_background_color( false, false ),
			'classes'      => 'js-shifty-toggle btn mr2 bg-dynamic light',
		) );

		$button = '<button class="'. $args['classes'] .'" '. $args['button_color'] .'>
			<span class="dashicons dashicons-visibility"></span>
			<span class="mobile-hide">'. $args['button_text'] .'</span>
		</button>';

		if ( $args['echo'] ) {
			echo $button;
		}

		return $button;
	}

	public function facebook_button( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'echo'         => true,
			'button_text'  => __( 'Share', 'storyftw' ),
			'button_color' => $this->set_to_first_background_color( false, false ),
			'share_url'    => esc_url( get_permalink( $this->post->ID ) ),
			'classes'      => 'btn bg-dynamic light facebook-share-button',
		) );

		$button = '<a href="https://www.facebook.com/sharer/sharer.php?u='. $args['share_url'] .'" target="_blank" class="'. $args['classes'] .'" '. $args['button_color'] .'>
			<span class="dashicons dashicons-facebook-alt"></span>
			<span class="mobile-hide">'. $args['button_text'] .'</span>
		</a>';

		if ( $args['echo'] ) {
			echo $button;
		}

		return $button;
	}

	public function tweet_button( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'echo'         => true,
			'button_text'  => __( 'Tweet', 'storyftw' ),
			'tweet_text'   => $this->story_meta( 'tweet_text' ) ? $this->story_meta( 'tweet_text' ) : $this->post->post_title,
			'button_color' => $this->set_to_first_background_color( false, false ),
			'share_url'    => esc_url( get_permalink( $this->post->ID ) ),
			'classes'      => 'btn bg-dynamic light tweet-button',
		) );

		$button = '<a href="https://twitter.com/intent/tweet?text='. urlencode( html_entity_decode( $args['tweet_text'] ) ) .'&url='. $args['share_url'] .'" target="_blank" class="'. $args['classes'] .'" '. $args['button_color'] .'>
			<span class="dashicons dashicons-twitter"></span>
			<span class="mobile-hide">'. $args['button_text'] .'</span>
		</a>';

		if ( $args['echo'] ) {
			echo $button;
		}

		return $button;
	}

	public function story_page_slug() {
		global $post;
		echo 'story-'. esc_attr( $post->post_name );
	}

	public function story_page_classes() {
		$classes = 'story-page '. $this->page_meta( 'text_color', 'light' );

		if ( $story_page_classes = $this->page_meta( 'story_page_classes' ) ) {
			$story_page_classes = array_map( 'sanitize_html_class', explode( ' ', $story_page_classes ) );
			$classes .= ' '. implode( ' ', $story_page_classes );
		}

		if ( $this->get_story_page_bg_image() ) {

			$class = 'bg-cover bg-center';

			$position = $this->page_meta( 'img_options', 'middle' );
			if ( $position && 'middle' != $position ) {
				$class = 'bg-cover bg-cover-'. $position;
			}

			$classes .= ' '. $class;
		}

		echo $classes;
	}

	public function story_page_inner_wrap_classes() {
		$classes = 'story-page-inner y100 '. $this->page_meta( 'text_align', 'center' );
		$classes .= $this->story_page_has_bg_video() ? '' : ' table';
		echo $classes;
	}

	public function story_page_content_wrap_classes() {
		$position = $this->page_meta( 'content_position' );

		if ( ! $this->story_page_has_bg_video() && 'middle' == $position ) {
			echo 'table-cell';
		} else {
			echo $position ? 'box box-'. $position : 'table-cell';
		}
	}

	public function story_page_has_bg_video() {
		return $this->page_meta( 'video_mp4' ) || $this->page_meta( 'video_webm' );
	}

	public function content() {
		$is_video_and_content_middle = $this->story_page_has_bg_video() && 'middle' == $this->page_meta( 'content_position' );

		if ( ! $is_video_and_content_middle ) {
			return the_content();
		}

		echo '<div class="table-cell center">', the_content() ,'</div>';
	}

	public function get_story_page_bg_image() {
		global $post;

		if ( isset( $post->story_page_bg_image ) ) {
			return $post->story_page_bg_image;
		}

		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'storyftw_bg' );
		$post->story_page_bg_image = $src && isset( $src[0] ) ? $src[0] : false;

		return $post->story_page_bg_image;
	}

	public function photo_credit() {
		$credit = $this->page_meta( 'photo_credit' );

		if ( ! $credit ) {
			return;
		}

		if ( $credit_url = $this->page_meta( 'photo_credit_url' ) ) {
			$credit = '<a href="'. esc_url( $credit_url ) .'" rel="bookmark" target="_blank">'. $credit .'</a>';
		}

		switch ( $this->page_meta( 'photo_credit_position' ) ) {
			case 'top-left':
				$class = 't0 l0';
				break;
			case 'bottom-right':
				$class = 'b0 r0';
				break;
			case 'bottom-left':
				$class = 'b0 l0';
				break;
			case 'top-right':
			default:
				$class = 't0 r0';
				break;
		}

		echo '<div class="', $class ,' absolute p1 small">', $credit ,'</div>';
	}

	public function story_page_footer( $page_number ) {
		if ( 1 === $page_number ) {
			echo '<div class="mobile-show light swipe-to-start">'. __( 'Swipe to start', 'storyftw' ) .'</div>';
		}

		if ( $this->page_meta( 'footer' ) ) {
			echo do_shortcode( wpautop( $this->page_meta( 'footer' ) ) );
		}
	}

	public function story_page_attributes() {
		if ( $src = $this->get_story_page_bg_image() ) {
			printf( ' style="background-image: url(%s)"', esc_url( $src ) );
		}

		$meta = array(
			'id' => get_the_ID(),

			// colors
			'color'     => esc_attr( $this->page_meta( 'background', $this->story_meta( 'fallback_bg_color', false ) ) ),
			'textcolor' => esc_attr( $this->page_meta( 'text_color', 'light' ) ),

			// bg video sources
			'mp4'  => esc_url( $this->page_meta( 'video_mp4', false ) ),
			'webm' => esc_url( $this->page_meta( 'video_webm', false ) ),
			'ogv'  => esc_url( $this->page_meta( 'video_ogv', false ) ),

			// color overrides
			'linkcolor' => esc_attr( $this->page_meta( 'link_color' ) ),
			'arrowcolor' => esc_attr( $this->page_meta( 'arrow_color', false ) ),
			'footertextcolor' => esc_attr( $this->page_meta( 'footer_text_color', false ) ),
			'footerbuttontextcolor' => esc_attr( $this->page_meta( 'footer_button_text_color', false ) ),

			// Element show/hide overrides
			'footertitleshow' => !! $this->page_meta( 'footer_title' ),
			'tocshow' => !! $this->page_meta( 'enable_toc' ),
			'fbshow' => !! $this->social( 'facebook', true ),
			'twshow' => !! $this->social( 'twitter', true ),
		);

		printf( " data-storymeta='%s'", json_encode( $meta ) );
	}

	/**
	 * Convert hex codes to rgb
	 * @since  1.0.0
	 * @param  string  $color  Color code
	 * @param  int     $transp Transparency value
	 * @return string          RGB color code
	 */
	public function hex2rgb( $color, $transp = false ) {

		$color = $this->checkcolor( $color );

		if ( !$color )
			return false;

		list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );

		$r = hexdec( $r ); $g = hexdec( $g ); $b = hexdec( $b );

		if ( is_numeric( $transp ) )
			return "rgba($r, $g, $b, $transp)";

		return "rgb($r, $g, $b)";
		// return array( 'red' => $r, 'green' => $g, 'blue' => $b );
	}

	/**
	 * Check if input is a valid color
	 * @since  1.0.0
	 * @param  string  $color Color code
	 * @return string         Valid color code
	 */
	public function checkcolor( $color ) {

		$color = substr( preg_replace( '/[^A-Za-z0-9]/', '', $color ), 0, 6 );

		if ( ! ( $length = strlen( $color ) ) )
			return false;

		if ( $length == 3 ) {
			$color = $color . $color;
		} elseif ( $length < 6 ) {
			$color = zeroise( $color, 6 );
		}

		return $color;
	}

	public function css_output() {
		global $post;

		// Get the story pages
		$pages = $this->get_story_pages();
		// And set global post to the first page so that page_meta works correctly
		$post = $pages[0];

		$colors = array(
			'link_color'               => array(
				'selectors' => array( '.story-page a:not(.btn)' ),
				'default'   => 'inherit'
			),
			'arrow_color'              => array(
				'selectors' => array( '.nav-arrow.arrow-color', '.js-coach-mark.arrow-color', '.swipe-to-start' ),
				'default'   => '#fff'
			),
			'footer_text_color'        => array(
				'selectors' => array( '.footer-text-color', '.footer-text-color a' ),
				'default'   => '#fff'
			),
			'footer_button_text_color' => array(
				'selectors' => array( '.footer-button-text-color' ),
				'default'   => '#fff'
			),
		);

		$css = '';

		foreach ( $colors as $key => $info ) {
			$color = $this->story_meta( $key, $info['default'] );
			if ( ! $color ) {
				continue;
			}

			$css .= '
			#storyftw-story '. implode( ', #storyftw-story ', $info['selectors'] ) .' {
				color: '. $color .';
				-webkit-transition: color 0.3s linear;
				-moz-transition: color 0.3s linear;
				transition: color 0.3s linear;
			}
			';
		}

		if ( $css ) {
			echo '<style type="text/css" media="screen">'. $css .'</style>';
		}
	}

	public function header_scripts() {
		echo $this->story_meta( 'header_scripts' );
	}

	public function footer_scripts() {
		echo $this->story_meta( 'footer_scripts' );
	}

	public function story_meta( $key_no_prefix, $fallback = null ) {
		$this->story->meta = isset( $this->story->meta ) ? $this->story->meta : array();
		$meta = get_post_meta( $this->story->ID, $this->prefix . $key_no_prefix, 1 );
		$this->story->meta[ $key_no_prefix ] = $meta ? $meta : $fallback;

		return $this->story->meta[ $key_no_prefix ];
	}

	public function page_meta( $key_no_prefix, $fallback = null ) {
		global $post;
		$post->meta = isset( $post->meta ) ? $post->meta : array();
		$meta = get_post_meta( $post->ID, $this->prefix . $key_no_prefix, 1 );
		$post->meta[ $key_no_prefix ] = $meta ? $meta : $fallback;

		return $post->meta[ $key_no_prefix ];
	}

}
