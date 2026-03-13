<?php
/**
 * Plugin Name: jazzsequence Media
 * Description: A plugin to manage and display video content on the jazzsequence.com.
 * Version: 1.1.0
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
	add_action( 'init', __NAMESPACE__ . '\\ensure_media_import_schedule' );
	add_action( 'add_meta_boxes_media', __NAMESPACE__ . '\\register_media_meta_box' );
	add_action( 'save_post_media', __NAMESPACE__ . '\\save_media_meta_box', 5, 3 );
	add_action( 'save_post_media', __NAMESPACE__ . '\\sideload_media_thumbnail', 10, 3 );
	add_filter( 'the_content', __NAMESPACE__ . '\\oembed_media_content' );
	add_action( 'admin_notices', __NAMESPACE__ . '\\render_media_admin_notices' );
	add_filter( 'render_block', __NAMESPACE__ . '\\tune_media_post_content_block', 10, 2 );
	add_filter( 'get_the_excerpt', __NAMESPACE__ . '\\filter_media_excerpt', 10, 2 );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_media_assets' );
	add_filter( 'the_content', __NAMESPACE__ . '\\filter_media_archive_content', 9 );
	add_filter( 'cron_schedules', __NAMESPACE__ . '\\add_weekly_schedule' );
	add_action( 'js_media_import_sources', __NAMESPACE__ . '\\run_media_imports' );
	add_action( 'admin_menu', __NAMESPACE__ . '\\register_media_sources_menu' );
	add_action( 'init', __NAMESPACE__ . '\\add_media_pagination_rewrite' );
	add_filter( 'query_vars', __NAMESPACE__ . '\\allow_media_pagination_query_var' );
}

/**
 * YouTube URL regex pattern.
 * 
 * @return string
 */
function get_youtube_pattern() {
	return '/https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([^\s"\'>?#&]+)/i';
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

	$youtube_id = get_youtube_id_from_string( $media_url );
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

/**
 * Add pretty pagination for Media archives (/videos/page/2/).
 *
 * @return void
 */
function add_media_pagination_rewrite() {
	// Pretty pagination for the "Videos" page using the query loop pagination var.
	add_rewrite_rule( '^videos/page/([0-9]+)/?$', 'index.php?pagename=videos&query-24-page=$matches[1]', 'top' );
}

/**
 * Allow the query loop pagination variable used in the Media archive template.
 *
 * @param array $vars Public query vars.
 * @return array
 */
function allow_media_pagination_query_var( $vars ) {
	$vars[] = 'query-24-page';
	return $vars;
}

/**
 * Add a weekly cron schedule.
 *
 * @param array $schedules Existing schedules.
 * @return array
 */
function add_weekly_schedule( $schedules ) {
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = [
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Once Weekly', 'js-media' ),
		];
	}

	return $schedules;
}

/**
 * Ensure the import event is scheduled.
 *
 * @return void
 */
function ensure_media_import_schedule() {
	if ( wp_next_scheduled( 'js_media_import_sources' ) ) {
		return;
	}

	wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', 'js_media_import_sources' );
}

/**
 * Add the Media Sources submenu.
 *
 * @return void
 */
function register_media_sources_menu() {
	add_submenu_page(
		'edit.php?post_type=media',
		__( 'Media Sources', 'js-media' ),
		__( 'Media Sources', 'js-media' ),
		'manage_options',
		'js-media-sources',
		__NAMESPACE__ . '\\render_media_sources_page'
	);
}

/**
 * Render the Media Sources settings page.
 *
 * @return void
 */
function render_media_sources_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['js_media_sources_action'] ) ) {
		check_admin_referer( 'js_media_sources_action', 'js_media_sources_nonce' );

		$action = sanitize_text_field( wp_unslash( $_POST['js_media_sources_action'] ) );
		if ( 'add' === $action ) {
			$source_url  = isset( $_POST['js_media_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['js_media_source_url'] ) ) : '';
			$source_name = isset( $_POST['js_media_source_name'] ) ? sanitize_text_field( wp_unslash( $_POST['js_media_source_name'] ) ) : '';

			if ( $source_url && $source_name ) {
				$sources   = get_media_sources();
				$sources[] = [
					'id'   => uniqid( '', true ),
					'url'  => $source_url,
					'name' => $source_name,
				];

				update_option( 'js_media_sources', $sources, false );
			}
		}

		if ( 'delete' === $action && ! empty( $_POST['js_media_source_id'] ) ) {
			$delete_id = sanitize_text_field( wp_unslash( $_POST['js_media_source_id'] ) );
			$sources   = array_values(
				array_filter(
					get_media_sources(),
					static function ( $source ) use ( $delete_id ) {
						return isset( $source['id'] ) && $delete_id !== $source['id'];
					}
				)
			);
			update_option( 'js_media_sources', $sources, false );
		}

		if ( 'run' === $action ) {
			run_media_imports();
		}
	}

	$sources = get_media_sources();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Media Sources', 'js-media' ); ?></h1>

		<h2><?php esc_html_e( 'Add Source', 'js-media' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'js_media_sources_action', 'js_media_sources_nonce' ); ?>
			<input type="hidden" name="js_media_sources_action" value="add" />
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="js_media_source_name"><?php esc_html_e( 'Source Name', 'js-media' ); ?></label></th>
					<td><input name="js_media_source_name" id="js_media_source_name" type="text" class="regular-text" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="js_media_source_url"><?php esc_html_e( 'Source URL (RSS or WP REST)', 'js-media' ); ?></label></th>
					<td><input name="js_media_source_url" id="js_media_source_url" type="url" class="regular-text" required /></td>
				</tr>
			</table>
			<?php submit_button( __( 'Add Source', 'js-media' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Existing Sources', 'js-media' ); ?></h2>
		<?php if ( empty( $sources ) ) : ?>
			<p><?php esc_html_e( 'No sources added yet.', 'js-media' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'js-media' ); ?></th>
						<th><?php esc_html_e( 'URL', 'js-media' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'js-media' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $sources as $source ) : ?>
					<tr>
						<td><?php echo esc_html( $source['name'] ); ?></td>
						<td><code><?php echo esc_html( $source['url'] ); ?></code></td>
						<td>
							<form method="post" style="display:inline">
								<?php wp_nonce_field( 'js_media_sources_action', 'js_media_sources_nonce' ); ?>
								<input type="hidden" name="js_media_sources_action" value="delete" />
								<input type="hidden" name="js_media_source_id" value="<?php echo esc_attr( $source['id'] ); ?>" />
								<?php submit_button( __( 'Delete', 'js-media' ), 'delete', 'submit', false ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<form method="post" style="margin-top:20px;">
			<?php wp_nonce_field( 'js_media_sources_action', 'js_media_sources_nonce' ); ?>
			<input type="hidden" name="js_media_sources_action" value="run" />
			<?php submit_button( __( 'Run Import Now', 'js-media' ), 'secondary' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Get configured media sources.
 *
 * @return array
 */
function get_media_sources() {
	$sources = get_option( 'js_media_sources', [] );

	return is_array( $sources ) ? $sources : [];
}

/**
 * Run imports for all sources.
 *
 * @return void
 */
function run_media_imports() {
	$sources = get_media_sources();
	if ( empty( $sources ) ) {
		return;
	}

	foreach ( $sources as $source ) {
		import_media_from_source( $source );
	}
}

/**
 * Import media items from a single source.
 *
 * @param array $source Source data.
 * @return void
 */
function import_media_from_source( $source ) {
	if ( empty( $source['url'] ) || empty( $source['name'] ) ) {
		return;
	}

	$items = fetch_remote_items( $source['url'] );
	if ( empty( $items ) ) {
		return;
	}

	foreach ( $items as $item ) {
		$remote_id   = isset( $item['id'] ) ? sanitize_text_field( (string) $item['id'] ) : '';
		$title       = isset( $item['title'] ) ? wp_strip_all_tags( (string) $item['title'] ) : '';
		$content     = isset( $item['content'] ) ? (string) $item['content'] : '';
		$permalink   = isset( $item['link'] ) ? esc_url_raw( $item['link'] ) : '';
		$title_parts = parse_episode_title( $title );

		$episode_number = $title_parts['episode'];
		$guest_name     = $title_parts['guest'];
		$youtube_url    = extract_youtube_url( $content );

		if ( ! $youtube_url && $permalink ) {
			$youtube_url = extract_youtube_url( $permalink );
		}

		if ( ! $youtube_url ) {
			continue;
		}

		// Prevent duplicates by remote ID or media URL.
		if ( remote_media_exists( $remote_id, $youtube_url ) ) {
			continue;
		}

		$post_title = $guest_name ? $guest_name : $title;
		$post_body  = trim(
			sprintf(
				'%s%s',
				$source['name'],
				$episode_number ? ' #' . $episode_number : ''
			)
		);

		wp_insert_post(
			[
				'post_type'     => 'media',
				'post_status'   => 'publish',
				'post_title'    => $post_title,
				'post_content'  => $post_body,
				'post_excerpt'  => $post_body,
				'post_date'     => ! empty( $item['date'] ) ? $item['date'] : current_time( 'mysql' ),
				'post_date_gmt' => ! empty( $item['date_gmt'] ) ? $item['date_gmt'] : current_time( 'mysql', true ),
				'meta_input'    => [
					'media_url'                => esc_url_raw( $youtube_url ),
					'_js_media_remote_id'      => $remote_id ? $remote_id : md5( $youtube_url ),
					'_js_media_remote_title'   => $title,
					'_js_media_remote_link'    => $permalink,
					'_js_media_source_name'    => sanitize_text_field( $source['name'] ),
					'_js_media_source_url'     => esc_url_raw( $source['url'] ),
					'_js_media_episode_number' => $episode_number,
				],
			]
		);
	}
}

/**
 * Fetch remote items from a URL (tries JSON then RSS/Atom).
 *
 * @param string $url Source URL.
 * @return array
 */
function fetch_remote_items( $url ) {
	$response = wp_remote_get( $url, [ 'timeout' => 15 ] ); // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
	if ( is_wp_error( $response ) ) {
		return [];
	}

	$body = wp_remote_retrieve_body( $response );
	if ( ! $body ) {
		return [];
	}

	$data = json_decode( $body, true );
	if ( is_array( $data ) ) {
		return normalize_rest_items( $data );
	}

	require_once ABSPATH . WPINC . '/feed.php';
	$feed = fetch_feed( $url );
	if ( is_wp_error( $feed ) ) {
		return [];
	}

	$items     = [];
	$simplepie = $feed->get_items();

	foreach ( $simplepie as $item ) {
		$items[] = [
			'id'        => $item->get_id(),
			'title'     => $item->get_title(),
			'content'   => $item->get_content(),
			'link'      => $item->get_permalink(),
			'date'      => $item->get_date( 'Y-m-d H:i:s' ),
			'date_gmt'  => $item->get_gmdate( 'Y-m-d H:i:s' ),
		];
	}

	return $items;
}

/**
 * Normalize REST items from WP JSON responses.
 *
 * @param array $data Raw JSON data.
 * @return array
 */
function normalize_rest_items( array $data ) {
	$items = [];

	// Handle both plain arrays of posts and wrapped responses.
	$list = isset( $data['posts'] ) && is_array( $data['posts'] ) ? $data['posts'] : $data;

	foreach ( $list as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$items[] = [
			'id'       => $entry['id'] ?? '',
			'title'    => $entry['title']['rendered'] ?? ( $entry['title'] ?? '' ),
			'content'  => $entry['content']['rendered'] ?? ( $entry['content'] ?? '' ),
			'link'     => $entry['link'] ?? '',
			'date'     => $entry['date'] ?? '',
			'date_gmt' => $entry['date_gmt'] ?? '',
		];
	}

	return $items;
}

/**
 * Parse episode title into components.
 *
 * @param string $title Remote title.
 * @return array{episode:string,guest:string}
 */
function parse_episode_title( $title ) {
	$episode = '';
	$guest   = trim( $title );

	if ( preg_match( '/episode\s*#?\s*(\d+)\s*:\s*(.+)/i', $title, $matches ) ) {
		$episode = $matches[1];
		$guest   = $matches[2];
	}

	return [
		'episode' => $episode,
		'guest'   => $guest,
	];
}

/**
 * Extract the first YouTube URL from HTML/text.
 *
 * @param string $content HTML content.
 * @return string
 */
function extract_youtube_url( $content ) {
	if ( ! $content ) {
		return '';
	}

	$id_part = get_youtube_id_from_string( $content );
	if ( ! $id_part ) {
		return '';
	}

	return 'https://www.youtube.com/watch?v=' . rawurlencode( $id_part );
}

/**
 * Extract a YouTube video ID from arbitrary text or URL.
 *
 * @param string $text Text that may contain a YouTube URL.
 * @return string
 */
function get_youtube_id_from_string( $text ) {
	if ( ! $text ) {
		return '';
	}

	if ( ! preg_match( get_youtube_pattern(), $text, $matches ) ) {
		return '';
	}

	$id_part = strtok( $matches[1], '?&' );

	return sanitize_text_field( $id_part );
}

/**
 * Determine if a remote media item already exists.
 *
 * @param string $remote_id  Remote identifier.
 * @param string $media_url  Media URL.
 * @return bool
 */
function remote_media_exists( $remote_id, $media_url ) {
	$meta_queries = [];

	if ( $remote_id ) {
		$meta_queries[] = [
			'key'   => '_js_media_remote_id',
			'value' => $remote_id,
		];
	}

	if ( $media_url ) {
		$meta_queries[] = [
			'key'   => 'media_url',
			'value' => $media_url,
		];
	}

	if ( empty( $meta_queries ) ) {
		return false;
	}

	$query = new \WP_Query(
		[
			'post_type'      => 'media',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'OR',
				...$meta_queries,
			],
		]
	);

	return $query->have_posts();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );
