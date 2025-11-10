<?php
/**
 * Plugin Name: jazzsequence Media
 * Description: A plugin to manage and display video content on the jazzsequence.com.
 * Version: 1.0.0
 * Author: Chris Reynolds
 * Author URI: https://jazzsequence.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: js-media
 */

namespace Jazzsequence\Media;

/**
 * Kick it off.
 */
function bootstrap() {
	add_action( 'init', __NAMESPACE__ . '\\create_media_post_type' );
	add_action( 'init', __NAMESPACE__ . '\\register_media_url_meta' );
	add_action( 'add_meta_boxes_media', __NAMESPACE__ . '\\register_media_meta_box' );
	add_action( 'save_post_media', __NAMESPACE__ . '\\save_media_meta_box', 5, 3 );
	add_action( 'save_post_media', __NAMESPACE__ . '\\sideload_media_thumbnail', 10, 3 );
	add_filter( 'the_content', __NAMESPACE__ . '\\oembed_media_content' );
	add_action( 'admin_notices', __NAMESPACE__ . '\\render_media_admin_notices' );
	add_filter( 'render_block', __NAMESPACE__ . '\\tune_media_post_content_block', 10, 2 );
	add_filter( 'get_the_excerpt', __NAMESPACE__ . '\\filter_media_excerpt', 10, 2 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_media_assets' );
	add_filter( 'the_content', __NAMESPACE__ . '\\filter_media_archive_content', 9 );
}

/**
 * Register the 'media' custom post type.
 */
function create_media_post_type() {
	$labels = [
		'name' => __( 'Media', 'js-media' ),
		'singular_name' => __( 'Media Item', 'js-media' ),
		'menu_name' => __( 'Media', 'js-media' ),
		'name_admin_bar' => __( 'Media Item', 'js-media' ),
		'add_new' => __( 'Add New', 'js-media' ),
		'add_new_item' => __( 'Add New Media Item', 'js-media' ),
		'new_item' => __( 'New Media Item', 'js-media' ),
		'edit_item' => __( 'Edit Media', 'js-media' ),
		'view_item' => __( 'View Media Item', 'js-media' ),
		'all_items' => __( 'All Media', 'js-media' ),
		'search_items' => __( 'Search Media', 'js-media' ),
		'parent_item_colon' => __( 'Parent Media:', 'js-media' ),
		'not_found' => __( 'No media found.', 'js-media' ),
		'not_found_in_trash' => __( 'No media found in Trash.', 'js-media' ),
	];

	$args = [
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'query_var' => true,
		'rewrite' => [ 'slug' => 'media' ],
		'capability_type' => 'post',
		'has_archive' => true,
		'hierarchical' => false,
		'show_in_rest' => true,
		'rest_base' => 'media',
		'menu_icon' => 'dashicons-video-alt',
		'supports' => [ 'title', 'thumbnail', 'excerpt' ],
	];

	register_post_type( 'media', $args );
}

/**
 * Sideload thumbnail as featured image from attached media
 *
 * Media type can be YouTube, WordPress.tv, or an external WordPress post.
 *
 * @param int     $post_id Post ID.
 * @param \WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated.
 * @return void
 */
function sideload_media_thumbnail( $post_id, $post, $update ) {
	if ( ! $post || 'media' !== $post->post_type ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$media_url = get_post_meta( $post_id, 'media_url', true );
	if ( ! $media_url ) {
		if ( did_request_manual_sideload() ) {
			queue_media_admin_notice( __( 'Please enter a media URL before sideloading a thumbnail.', 'js-media' ), 'error' );
		}
		return;
	}

	$media_url      = esc_url_raw( $media_url );
	$stored_source  = get_post_meta( $post_id, '_js_media_thumbnail_source', true );
	$manual_request = did_request_manual_sideload();

	// Skip if we already sideloaded an image for this URL and nothing has changed.
	if ( has_post_thumbnail( $post_id ) && $stored_source === $media_url && ! $manual_request ) {
		return;
	}

	if ( $manual_request && has_post_thumbnail( $post_id ) ) {
		delete_post_thumbnail( $post_id );
	}

	$image_url = get_media_thumbnail_url( $media_url );
	if ( ! $image_url ) {
		if ( $manual_request ) {
			queue_media_admin_notice( __( 'Could not determine a thumbnail for that URL. Check that the provider exposes a thumbnail.', 'js-media' ), 'error' );
		}
		return;
	}

	// Sideload the image and set as featured image.
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	if ( $manual_request ) {
		queue_media_admin_notice(
			sprintf(
				/* translators: %s: Thumbnail image URL. */
				__( 'Attempting sideload from: %s', 'js-media' ),
				esc_html( $image_url )
			),
			'info'
		);
	}
	$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		if ( $manual_request ) {
			queue_media_admin_notice(
				sprintf(
					/* translators: %s: error message */
					__( 'Thumbnail sideload failed: %s', 'js-media' ),
					esc_html( $attachment_id->get_error_message() )
				),
				'error'
			);
		}
		return;
	}

	set_post_thumbnail( $post_id, $attachment_id );
	update_post_meta( $post_id, '_js_media_thumbnail_source', $media_url );

	if ( $manual_request ) {
		queue_media_admin_notice( __( 'Featured image updated from the embed thumbnail.', 'js-media' ), 'success' );
	}
}

/**
 * Determine the best thumbnail URL for a remote media source.
 *
 * @param string $media_url Remote media URL.
 * @return string
 */
function get_media_thumbnail_url( $media_url ) {
	$data = get_media_oembed_data( $media_url );

	if ( $data && ! empty( $data->thumbnail_url ) ) {
		return esc_url_raw( $data->thumbnail_url );
	}

	$youtube_id = get_youtube_video_id( $media_url );
	if ( $youtube_id ) {
		return sprintf( 'https://img.youtube.com/vi/%s/maxresdefault.jpg', $youtube_id );
	}

	return '';
}

/**
 * Fetch oEmbed data for a media URL.
 *
 * @param string $media_url Remote media URL.
 * @return object|null
 */
function get_media_oembed_data( $media_url ) {
	$oembed = _wp_oembed_get_object();
	if ( ! $oembed ) {
		return null;
	}

	$data = $oembed->get_data( $media_url );
	if ( ! $data || is_wp_error( $data ) ) {
		return null;
	}

	return $data;
}

/**
 * Extract a YouTube video ID from a URL if one exists.
 *
 * @param string $media_url Remote media URL.
 * @return string
 */
function get_youtube_video_id( $media_url ) {
	$parts = wp_parse_url( $media_url );
	if ( ! $parts || empty( $parts['host'] ) ) {
		return '';
	}

	$host = strtolower( preg_replace( '/^www\./', '', $parts['host'] ) );

	if ( 'youtu.be' === $host && ! empty( $parts['path'] ) ) {
		return trim( $parts['path'], '/' );
	}

	if ( in_array( $host, [ 'youtube.com', 'm.youtube.com' ], true ) ) {
		if ( ! empty( $parts['path'] ) && 0 === strpos( $parts['path'], '/shorts/' ) ) {
			return basename( $parts['path'] );
		}

		if ( empty( $parts['query'] ) ) {
			return '';
		}

		parse_str( $parts['query'], $query_vars );
		if ( ! empty( $query_vars['v'] ) ) {
			return sanitize_text_field( $query_vars['v'] );
		}
	}

	return '';
}

/**
 * Register meta field for media URL to embed media
 * 
 * @return void
 */
function register_media_url_meta() {
	register_post_meta( 'media', 'media_url', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'description' => 'URL of the media to embed',
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
	] );
}

/**
 * Register the editor meta box for media URL input.
 *
 * @return void
 */
function register_media_meta_box() {
	add_meta_box(
		'js-media-url',
		__( 'Media Embed URL', 'js-media' ),
		__NAMESPACE__ . '\\render_media_meta_box',
		'media',
		'normal',
		'high'
	);
}

/**
 * Render the media URL meta box content.
 *
 * @param \WP_Post $post Current post.
 * @return void
 */
function render_media_meta_box( $post ) {
	wp_nonce_field( 'js_media_url_meta_box', 'js_media_url_nonce' );

	$media_url = get_post_meta( $post->ID, 'media_url', true );
	?>
	<p>
		<label for="js_media_url"><?php esc_html_e( 'Media URL (YouTube, WordPress.tv, external WordPress post, etc.)', 'js-media' ); ?></label>
	</p>
	<p>
		<input type="url" class="widefat" id="js_media_url" name="js_media_url" value="<?php echo esc_attr( $media_url ); ?>" placeholder="https://example.com/media-item" />
	</p>
	<p>
		<?php submit_button( __( 'Sideload Embed Thumbnail', 'js-media' ), 'secondary', 'js_media_manual_sideload', false ); ?>
	</p>
	<?php
}

/**
 * Persist meta box input when a media post is saved.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Post object.
 * @param bool     $update  Whether this is an existing post being updated.
 * @return void
 */
function save_media_meta_box( $post_id, $post, $update ) {
	if ( ! $post || 'media' !== $post->post_type ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['js_media_url_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['js_media_url_nonce'] ) ) : '';
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'js_media_url_meta_box' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['js_media_url'] ) && '' !== $_POST['js_media_url'] ) {
		update_post_meta( $post_id, 'media_url', esc_url_raw( wp_unslash( $_POST['js_media_url'] ) ) );
	} else {
		delete_post_meta( $post_id, 'media_url' );
	}
}

/**
 * Queue an admin notice to display after processing.
 *
 * @param string $message Message text.
 * @param string $type    Notice type: success|warning|error|info.
 * @return void
 */
function queue_media_admin_notice( $message, $type = 'info' ) {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $GLOBALS['js_media_notices'] ) || ! is_array( $GLOBALS['js_media_notices'] ) ) {
		$GLOBALS['js_media_notices'] = [];
	}

	$notice = [
		'message' => $message,
		'type'    => $type,
	];

	$GLOBALS['js_media_notices'][] = $notice;
	persist_media_admin_notice( $notice );
}

/**
 * Output queued admin notices for the Media CPT.
 *
 * @return void
 */
function render_media_admin_notices() {
	if ( empty( $GLOBALS['js_media_notices'] ) ) {
		$GLOBALS['js_media_notices'] = [];
	}

	$notices = array_merge( $GLOBALS['js_media_notices'], fetch_persisted_media_notices() );
	if ( empty( $notices ) ) {
		return;
	}

	foreach ( $notices as $notice ) {
		printf(
			'<div class="notice notice-%1$s"><p>%2$s</p></div>',
			esc_attr( $notice['type'] ),
			wp_kses_post( $notice['message'] )
		);
	}

	unset( $GLOBALS['js_media_notices'] );
}

/**
 * Store notices so they survive the post-save redirect.
 *
 * @param array $notice Notice data.
 * @return void
 */
function persist_media_admin_notice( $notice ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$key     = 'js_media_notices_' . $user_id;
	$current = get_transient( $key );
	if ( ! is_array( $current ) ) {
		$current = [];
	}

	$current[] = $notice;
	set_transient( $key, $current, MINUTE_IN_SECONDS );
}

/**
 * Retrieve and clear persisted notices.
 *
 * @return array
 */
function fetch_persisted_media_notices() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return [];
	}

	$key     = 'js_media_notices_' . $user_id;
	$notices = get_transient( $key );
	if ( $notices ) {
		delete_transient( $key );
	}

	return is_array( $notices ) ? $notices : [];
}

/**
 * Oembed media content based on the media_url meta field.
 * 
 * Automatically handle oembedding media as the content and detecting source
 * from URL.
 * 
 * Possible embed types: WP post, YouTube URL or WordPress.tv video embed.
 * 
 * @param string $content The original post content.
 * @return string The modified content with embedded media.
 */
function oembed_media_content( $content ) {
	$post = get_post();

	if ( ! $post || 'media' !== $post->post_type ) {
		return $content;
	}

	if ( ! is_singular( 'media' ) ) {
		return $content;
	}

	$media_url = get_post_meta( $post->ID, 'media_url', true );
	if ( ! $media_url ) {
		return $content;
	}

	$new_content = '';
	$description = trim( (string) get_post_field( 'post_excerpt', $post->ID ) );
	if ( ! empty( $description ) ) {
		$new_content = '<p>' . esc_html( $description ) . '</p>';
	}

	$oembed = wp_oembed_get( $media_url );

	if ( $oembed ) {
		$new_content = $oembed . $new_content;
	}

	return $new_content;
}

/**
 * Prevent automatically generated excerpts for Media posts.
 *
 * @param string   $excerpt Existing excerpt.
 * @param \WP_Post $post    Post object.
 * @return string
 */
function filter_media_excerpt( $excerpt, $post ) {
	if ( ! $post instanceof \WP_Post || 'media' !== $post->post_type ) {
		return $excerpt;
	}

	$manual_excerpt = trim( (string) get_post_field( 'post_excerpt', $post->ID ) );
	if ( '' === $manual_excerpt ) {
		return '';
	}

	return $manual_excerpt;
}

/**
 * Force the Post Content block wrapper to use aligncenter on Media items.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block data.
 * @return string
 */
function tune_media_post_content_block( $block_content, $block ) {
	if ( empty( $block['blockName'] ) || 'core/post-content' !== $block['blockName'] ) {
		return $block_content;
	}

	$current_post = get_post();
	if ( ! $current_post || 'media' !== $current_post->post_type ) {
		return $block_content;
	}

	if ( false === strpos( $block_content, 'alignfull' ) ) {
		return $block_content;
	}

	return preg_replace( '/(\bentry-content\b[^"]*)\balignfull\b/', '$1aligncenter', $block_content );
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

/**
 * Replace archive content with excerpt for Media entries.
 *
 * @param string $content Content being rendered.
 * @return string
 */
function filter_media_archive_content( $content ) {
	if ( ! is_main_query() || is_singular() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post || 'media' !== $post->post_type ) {
		return $content;
	}

	$excerpt = trim( (string) get_post_field( 'post_excerpt', $post->ID ) );
	if ( '' === $excerpt ) {
		return '';
	}

	return '<p>' . esc_html( $excerpt ) . '</p>';
}

/**
 * Check whether the current save request clicked the manual sideload button.
 *
 * @return bool
 */
function did_request_manual_sideload() {
	static $manual_request = null;

	if ( null !== $manual_request ) {
		return $manual_request;
	}

	if ( empty( $_POST['js_media_manual_sideload'] ) ) {
		$manual_request = false;
		return $manual_request;
	}

	$nonce = isset( $_POST['js_media_url_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['js_media_url_nonce'] ) ) : '';
	$manual_request = (bool) ( $nonce && wp_verify_nonce( $nonce, 'js_media_url_meta_box' ) );

	return $manual_request;
}

/**
 * Enqueue front-end assets needed for Media embeds.
 *
 * @return void
 */
function enqueue_media_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( is_singular( 'media' ) || is_post_type_archive( 'media' ) || query_contains_media_posts() ) {
		wp_enqueue_script( 'wp-embed' );
	}
}

/**
 * Detect whether the current main query contains Media posts.
 *
 * @return bool
 */
function query_contains_media_posts() {
	global $wp_query;

	if ( ! $wp_query || empty( $wp_query->posts ) ) {
		return false;
	}

	foreach ( $wp_query->posts as $post ) {
		if ( $post instanceof \WP_Post && 'media' === $post->post_type ) {
			return true;
		}
	}

	return false;
}
