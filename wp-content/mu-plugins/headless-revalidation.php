<?php
/**
 * Plugin Name: Headless Revalidation
 * Plugin URI:  https://github.com/jazzsequence/jazz-nextjs
 * Description: Triggers Next.js ISR revalidation when WordPress content is published or updated.
 * Version:     1.0.0
 * Author:      Chris Reynolds
 *
 * Configuration (add to wp-config.php or a mu-plugin):
 *   define( 'NEXTJS_SITE_URL', 'https://live-jazz-nextjs.pantheonsite.io' );
 *   define( 'NEXTJS_REVALIDATE_SECRET', 'your-secret-here' );
 *
 * Or store as WordPress options (lower precedence than constants):
 *   nextjs_site_url
 *   nextjs_revalidate_secret
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Next.js site URL and revalidation secret from constants or options.
 *
 * @return array{url: string, secret: string}|null Null if not configured.
 */
function jazzsequence_revalidate_config(): ?array {
	$url    = defined( 'NEXTJS_SITE_URL' )          ? NEXTJS_SITE_URL          : get_option( 'nextjs_site_url', '' );
	$secret = defined( 'NEXTJS_REVALIDATE_SECRET' ) ? NEXTJS_REVALIDATE_SECRET : get_option( 'nextjs_revalidate_secret', '' );

	if ( empty( $url ) || empty( $secret ) ) {
		return null;
	}

	return [ 'url' => trailingslashit( $url ), 'secret' => $secret ];
}

/**
 * Fire a revalidation request to the Next.js endpoint.
 *
 * @param array $payload JSON body to send.
 */
function jazzsequence_send_revalidate( array $payload ): void {
	$config = jazzsequence_revalidate_config();
	if ( null === $config ) {
		return;
	}

	wp_remote_post(
		$config['url'] . 'api/revalidate',
		[
			'headers'  => [
				'X-Revalidate-Secret' => $config['secret'],
				'Content-Type'        => 'application/json',
			],
			'body'     => wp_json_encode( $payload ),
			'blocking' => false, // fire-and-forget; don't slow down the save
			'timeout'  => 5,
		]
	);
}

/**
 * Revalidate on post publish/update/trash.
 * Fires for all post types including gc_game and pages.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function jazzsequence_revalidate_on_save( int $post_id, WP_Post $post ): void {
	// Skip auto-saves, revisions, and non-public statuses.
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! in_array( $post->post_status, [ 'publish', 'trash' ], true ) ) {
		return;
	}

	// When a post is trashed, WordPress appends __trashed to post_name.
	// Strip it so the revalidation targets the original slug, not the mangled one.
	$slug = rtrim( str_replace( '__trashed', '', $post->post_name ), '-' );

	jazzsequence_send_revalidate( [
		'post_type' => $post->post_type,
		'post_slug' => $slug,
		'post_id'   => $post_id,
		'action'    => 'trash' === $post->post_status ? 'delete' : 'publish',
	] );
}
add_action( 'save_post', 'jazzsequence_revalidate_on_save', 10, 2 );

/**
 * Revalidate navigation when a menu is updated.
 * Fires when items are added, removed, or reordered.
 */
function jazzsequence_revalidate_on_menu_update(): void {
	// Pages cache menu data under both 'menu' and 'header' tags — revalidate both.
	jazzsequence_send_revalidate( [ 'tags' => [ 'menu', 'header' ] ] );
}
add_action( 'wp_update_nav_menu', 'jazzsequence_revalidate_on_menu_update' );
