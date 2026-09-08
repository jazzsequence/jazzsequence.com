<?php
/**
 * Plugin Name: REST Cache Headers
 * Plugin URI:  https://github.com/jazzsequence/jazzsequence.com
 * Description: Lets LiteSpeed and any upstream CDN serve repeat public REST reads without invoking PHP.
 * Version:     1.0.0
 * Author:      Chris Reynolds
 *
 * WHY THIS EXISTS
 *
 * LiteSpeed serves cached HTML without touching PHP, which is why the site's
 * pages, feed and login stay fast under load. REST responses are not cached by
 * default, so every REST request occupies an LSAPI worker for the life of the
 * query. On a single droplet with a fixed worker pool, a burst saturates it and
 * further requests queue with no response at all — measured 2026-09-08 as
 * repeated `http=000` on /wp-json/ after a 25s wait, and HTTP 524 from the MCP
 * endpoint, while cached HTML kept answering in about a second.
 *
 * The burst that matters is the headless frontend's build: it fetches /, /games
 * and /sitemap.xml with eleven parallel workers, each paginating, and Next.js
 * retries a route three times before failing. Those retries re-fetch the same
 * endpoints, so a build that is already struggling generates several times the
 * traffic of a healthy one. Caching turns each retry into a cache hit instead of
 * another worker.
 *
 * Configuration (optional, wp-config.php):
 *   define( 'JAZZSEQUENCE_REST_CACHE_TTL', 60 );  // seconds; 0 disables entirely
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How long a public REST read may be served from cache, in seconds.
 *
 * Short on purpose. This exists to collapse bursts — a build's parallel workers
 * and its retries all land inside a minute — not to serve stale content for
 * long. Publishing already pushes invalidation to the frontend separately via
 * the Headless Revalidation mu-plugin, and that path is unaffected: it is a
 * POST from WordPress outward, not a cached GET.
 *
 * @return int Seconds. Zero disables caching entirely.
 */
function jazzsequence_rest_cache_ttl(): int {
	$ttl = defined( 'JAZZSEQUENCE_REST_CACHE_TTL' ) ? (int) JAZZSEQUENCE_REST_CACHE_TTL : 60;

	/**
	 * Filter the REST cache TTL.
	 *
	 * @param int $ttl Seconds. Return 0 to disable caching.
	 */
	return (int) apply_filters( 'jazzsequence_rest_cache_ttl', $ttl );
}

/**
 * REST route prefixes that may be cached, derived from what WordPress actually
 * has registered.
 *
 * Derived rather than hardcoded, deliberately. A fixed list of route names is a
 * second source of truth that drifts the moment a post type or taxonomy is
 * added or its rest_base changes — and then either silently stops caching
 * something, or worse, keeps caching a route whose visibility has changed.
 * Asking WordPress each time means the allowlist cannot disagree with the site.
 *
 * Only public, show_in_rest types and taxonomies qualify. Everything else —
 * users, settings, the MCP and abilities namespaces, form submissions — is
 * excluded by omission rather than by a denylist, so anything new is private by
 * default instead of needing to be remembered.
 *
 * @return string[] Route prefixes, e.g. '/wp/v2/posts'.
 */
function jazzsequence_rest_cacheable_routes(): array {
	$routes = [];

	$public_args = [
		'public'       => true,
		'show_in_rest' => true,
	];

	foreach ( get_post_types( $public_args, 'objects' ) as $type ) {
		if ( empty( $type->rest_base ) && empty( $type->name ) ) {
			continue;
		}
		$namespace = ! empty( $type->rest_namespace ) ? $type->rest_namespace : 'wp/v2';
		$base      = ! empty( $type->rest_base ) ? $type->rest_base : $type->name;
		$routes[]  = '/' . $namespace . '/' . $base;
	}

	foreach ( get_taxonomies( $public_args, 'objects' ) as $taxonomy ) {
		$namespace = ! empty( $taxonomy->rest_namespace ) ? $taxonomy->rest_namespace : 'wp/v2';
		$base      = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
		$routes[]  = '/' . $namespace . '/' . $base;
	}

	// Read-only and consulted by the frontend on every build.
	$routes[] = '/wp/v2/types';
	$routes[] = '/wp/v2/taxonomies';

	/**
	 * Filter the cacheable REST route prefixes.
	 *
	 * @param string[] $routes Route prefixes.
	 */
	return (array) apply_filters( 'jazzsequence_rest_cacheable_routes', array_values( array_unique( $routes ) ) );
}

/**
 * Whether this request may have its response cached.
 *
 * Every check here is a reason NOT to cache. The ordering is deliberate: the
 * cheap, absolute disqualifiers come first, and the route allowlist last, so a
 * private request never reaches the point where a matching route could let it
 * through.
 *
 * @param \WP_REST_Request $request The request.
 * @return bool
 */
function jazzsequence_rest_request_is_cacheable( \WP_REST_Request $request ): bool {
	if ( 'GET' !== $request->get_method() ) {
		return false;
	}

	/*
	 * Anything tied to a user is per-user by definition. Caching it publicly
	 * would be a disclosure bug, not a performance trade.
	 */
	if ( is_user_logged_in() ) {
		return false;
	}

	/*
	 * Belt and braces: a request can carry credentials without a session having
	 * been established yet at this point in the lifecycle, and an app-password
	 * or nonce request must never be served from a shared cache.
	 */
	if ( ! empty( $request->get_header( 'authorization' ) ) ) {
		return false;
	}

	if ( ! empty( $request->get_param( '_wpnonce' ) ) || ! empty( $request->get_header( 'x_wp_nonce' ) ) ) {
		return false;
	}

	// 'edit' exposes unpublished and private fields.
	if ( 'edit' === $request->get_param( 'context' ) ) {
		return false;
	}

	// Explicit status/password queries can surface non-public content.
	if ( ! empty( $request->get_param( 'password' ) ) ) {
		return false;
	}

	/*
	 * Collection endpoints type `status` as an array — /wp/v2/posts defaults it
	 * to array( 'publish' ), not the string. Comparing it to a string is always
	 * unequal, which rejected every collection request and made this plugin
	 * silently inert. Caught by running the suite, not by reading it.
	 */
	$status = $request->get_param( 'status' );
	if ( ! empty( $status ) ) {
		foreach ( (array) $status as $status_value ) {
			if ( 'publish' !== $status_value ) {
				return false;
			}
		}
	}

	$route = $request->get_route();
	foreach ( jazzsequence_rest_cacheable_routes() as $prefix ) {
		if ( 0 === strpos( $route, $prefix ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Attach cache headers to eligible REST responses.
 *
 * Setting headers is necessary but NOT sufficient, and shipping it as though it
 * were made this plugin completely inert in production on 2026-09-08 — public
 * REST reads still came back `no-cache, no-store, private`. Two other things
 * overrule a response header, and both are handled here:
 *
 *  1. WordPress itself. serve_request() applies `rest_send_nocache_headers`
 *     (class-wp-rest-server.php:487) and calls nocache_headers() if it is true,
 *     which stamps over Cache-Control. It defaults to is_user_logged_in(), but
 *     other code filters it — so it has to be answered explicitly. This filter
 *     runs at line 464, BEFORE that check, which is what makes the flag below
 *     work at all.
 *
 *  2. LiteSpeed. It computes its own X-LiteSpeed-Cache-Control and overwrote the
 *     hand-set one with `no-cache`. The supported route is its action API —
 *     litespeed_control_set_cacheable / litespeed_control_set_ttl
 *     (litespeed-cache/src/api.cls.php:82,86).
 *
 * Cache-Control is still sent for any upstream proxy or CDN that reads it.
 *
 * `max-age=0` keeps browsers revalidating while shared caches use `s-maxage`, so
 * a person refreshing sees current data even while a build is being served from
 * cache.
 *
 * @param \WP_HTTP_Response $response Result to send.
 * @param \WP_REST_Server   $server   Server instance.
 * @param \WP_REST_Request  $request  Request used to generate the response.
 * @return \WP_HTTP_Response
 */
function jazzsequence_rest_cache_headers( $response, $server, $request ) {
	if ( ! $response instanceof \WP_HTTP_Response || ! $request instanceof \WP_REST_Request ) {
		return $response;
	}

	$ttl = jazzsequence_rest_cache_ttl();
	if ( $ttl <= 0 ) {
		return $response;
	}

	/*
	 * Only cache a clean success. Caching a 404 or a 500 would pin a transient
	 * failure in front of every subsequent request — the opposite of the goal.
	 */
	if ( 200 !== $response->get_status() ) {
		return $response;
	}

	if ( ! jazzsequence_rest_request_is_cacheable( $request ) ) {
		return $response;
	}

	$stale = $ttl * 10;

	$response->header( 'Cache-Control', sprintf( 'public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', $ttl, $stale ) );

	// Read by the rest_send_nocache_headers filter below, which core evaluates later.
	$GLOBALS['jazzsequence_rest_cacheable'] = true;

	/*
	 * LiteSpeed's own API. Without these it computes no-cache for REST and
	 * overwrites anything set by hand, which is exactly what happened the first
	 * time this shipped.
	 */
	if ( has_action( 'litespeed_control_set_cacheable' ) ) {
		do_action( 'litespeed_control_set_cacheable' );
		do_action( 'litespeed_control_set_ttl', $ttl );
	}

	return $response;
}
add_filter( 'rest_post_dispatch', 'jazzsequence_rest_cache_headers', 10, 3 );

/**
 * Stop WordPress stamping no-cache over a response we just marked cacheable.
 *
 * WP_REST_Server::serve_request() calls nocache_headers() when this is true, and those
 * headers replace Cache-Control outright. The default is is_user_logged_in(),
 * but it is filtered elsewhere in core and by plugins, so an anonymous request
 * can still arrive here as true — it did in production.
 *
 * Only ever answers for a request already judged cacheable by the dispatch
 * filter above, which runs first. Everything else keeps core's behaviour, so
 * this cannot widen what gets cached.
 *
 * @param bool $send_no_cache_headers Whether core intends to send them.
 * @return bool
 */
function jazzsequence_rest_allow_cache_headers( $send_no_cache_headers ) {
	return ! empty( $GLOBALS['jazzsequence_rest_cacheable'] ) ? false : $send_no_cache_headers;
}
add_filter( 'rest_send_nocache_headers', 'jazzsequence_rest_allow_cache_headers' );
