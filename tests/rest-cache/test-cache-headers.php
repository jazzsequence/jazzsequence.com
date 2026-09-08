<?php
/**
 * Tests for the REST cache headers mu-plugin.
 *
 * The stakes are asymmetric. Failing to cache something cacheable costs
 * performance; caching something private is a disclosure bug. So most of these
 * tests assert that a header is ABSENT, and the ones that matter most are the
 * ones trying to get a private response cached.
 *
 * @package JazzSequence\Tests\RestCache
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
 */

/**
 * Tests for jazzsequence_rest_cache_headers() and its guards.
 */
class Test_Rest_Cache_Headers extends WP_UnitTestCase {

	/**
	 * Dispatch a request through the real REST server and return the response.
	 *
	 * Dispatches for real, then applies rest_post_dispatch the way the server
	 * does. That second step is not optional: WP_REST_Server applies the filter
	 * inside serve_request() (class-wp-rest-server.php:464), NOT inside
	 * dispatch(), so rest_do_request() alone never fires it. Testing through
	 * rest_do_request without this produced four failures against a plugin that
	 * was working correctly.
	 *
	 * @param string $route  REST route.
	 * @param array  $params Query parameters.
	 * @param string $method HTTP method.
	 * @return WP_REST_Response
	 */
	private function dispatch( string $route, array $params = [], string $method = 'GET' ): WP_REST_Response {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = rest_do_request( $request );

		return apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), rest_get_server(), $request );
	}

	/**
	 * Reset the cross-request cacheable flag between tests.
	 */
	public function set_up() {
		parent::set_up();
		unset( $GLOBALS['jazzsequence_rest_cacheable'] );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Overrides that made this inert in production
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Core stamps nocache_headers() over Cache-Control unless told not to.
	 *
	 * This is why the first release of this plugin did nothing: every public REST
	 * read still came back `no-cache, no-store, private`. Headers alone are not
	 * enough, and no amount of asserting on the response object would have shown
	 * it — the override happens after the filter returns.
	 */
	public function test_suppresses_core_nocache_for_a_cacheable_response() {
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->dispatch( '/wp/v2/posts' );

		$this->assertFalse( apply_filters( 'rest_send_nocache_headers', true ) );
	}

	/**
	 * A request that was never cacheable must not have nocache suppressed.
	 */
	public function test_leaves_core_nocache_alone_for_a_private_response() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->dispatch( '/wp/v2/posts' );

		$this->assertTrue( apply_filters( 'rest_send_nocache_headers', true ) );
	}

	/**
	 * Cache-Control value from a response, or empty string.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return string
	 */
	private function cache_control( WP_REST_Response $response ): string {
		$headers = $response->get_headers();
		return $headers['Cache-Control'] ?? '';
	}

	/*
	 * -------------------------------------------------------------------------
	 * The happy path
	 * -------------------------------------------------------------------------
	 */

	/**
	 * A plain public read is the whole point of the change.
	 */
	public function test_public_get_is_cacheable() {
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatch( '/wp/v2/posts' );

		$this->assertStringContainsString( 's-maxage=', $this->cache_control( $response ) );
	}

	public function test_signals_litespeed_through_its_action_api() {
		/*
		 * Setting X-LiteSpeed-Cache-Control by hand does not work — LiteSpeed
		 * computes its own and overwrote it with no-cache, which is one of the two
		 * reasons the first release of this plugin was inert. The supported route
		 * is its action API (litespeed-cache/src/api.cls.php:82,86). LiteSpeed is
		 * not loaded in tests, so a listener stands in for it.
		 */
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$cacheable = 0;
		$ttl_seen  = null;
		add_action(
			'litespeed_control_set_cacheable',
			static function () use ( &$cacheable ) {
				$cacheable++;
			}
		);
		add_action(
			'litespeed_control_set_ttl',
			static function ( $ttl ) use ( &$ttl_seen ) {
				$ttl_seen = $ttl;
			}
		);

		$this->dispatch( '/wp/v2/posts' );

		$this->assertSame( 1, $cacheable, 'LiteSpeed was not told the response is cacheable' );
		$this->assertSame( jazzsequence_rest_cache_ttl(), $ttl_seen );
	}

	public function test_does_not_signal_litespeed_for_a_private_response() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$cacheable = 0;
		add_action(
			'litespeed_control_set_cacheable',
			static function () use ( &$cacheable ) {
				$cacheable++;
			}
		);

		$this->dispatch( '/wp/v2/posts' );

		$this->assertSame( 0, $cacheable );
	}

	public function test_browsers_still_revalidate() {
		/*
		 * max-age=0 keeps a person refreshing from seeing cached data while
		 * shared caches use s-maxage.
		 */
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatch( '/wp/v2/posts' );

		$this->assertStringContainsString( 'max-age=0', $this->cache_control( $response ) );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Requests that must never be cached
	 * -------------------------------------------------------------------------
	 */

	/**
	 * A logged-in response is per-user; caching it publicly would disclose it.
	 */
	public function test_logged_in_request_is_not_cached() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatch( '/wp/v2/posts' );

		$this->assertSame( '', $this->cache_control( $response ) );
	}

	public function test_edit_context_is_not_cached() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->dispatch( '/wp/v2/posts', [ 'context' => 'edit' ] );

		$this->assertSame( '', $this->cache_control( $response ) );
	}

	public function test_authorization_header_is_not_cached() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_header( 'authorization', 'Basic dXNlcjpwYXNz' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_nonce_request_is_not_cached() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( '_wpnonce', 'abc123' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_non_publish_status_query_is_not_cached() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'status', 'draft' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_password_param_is_not_cached() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'password', 'hunter2' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_write_methods_are_not_cached() {
		foreach ( [ 'POST', 'PUT', 'PATCH', 'DELETE' ] as $method ) {
			$request = new WP_REST_Request( $method, '/wp/v2/posts' );
			$this->assertFalse(
				jazzsequence_rest_request_is_cacheable( $request ),
				"{$method} must not be cacheable"
			);
		}
	}

	/*
	 * -------------------------------------------------------------------------
	 * Route derivation
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Users are excluded by derivation, not by a named denylist.
	 */
	public function test_users_route_is_not_cacheable() {
		/*
		 * Not excluded by name anywhere — it simply never enters the allowlist,
		 * because users are not a public post type. This asserts the allowlist is
		 * genuinely derived rather than a denylist with holes.
		 */
		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_settings_route_is_not_cacheable() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_non_public_post_type_is_not_cacheable() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/secret-things' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_non_public_taxonomy_is_not_cacheable() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/secret-tax' );

		$this->assertFalse( jazzsequence_rest_request_is_cacheable( $request ) );
	}

	public function test_public_custom_post_types_are_derived_not_hardcoded() {
		// A registered public type must appear without anyone editing this plugin.
		register_post_type(
			'widget_thing',
			[
				'public'       => true,
				'show_in_rest' => true,
				'rest_base'    => 'widget-things',
			]
		);

		$this->assertContains( '/wp/v2/widget-things', jazzsequence_rest_cacheable_routes() );

		unregister_post_type( 'widget_thing' );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Failure and configuration
	 * -------------------------------------------------------------------------
	 */

	/**
	 * A cached failure would outlast the failure itself.
	 */
	public function test_error_responses_are_not_cached() {
		/*
		 * Caching a transient 404 or 500 would pin the failure in front of every
		 * subsequent request — worse than not caching at all.
		 */
		$response = $this->dispatch( '/wp/v2/posts/999999999' );

		$this->assertSame( '', $this->cache_control( $response ) );
	}

	public function test_zero_ttl_disables_caching() {
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		add_filter( 'jazzsequence_rest_cache_ttl', '__return_zero' );
		$response = $this->dispatch( '/wp/v2/posts' );
		remove_filter( 'jazzsequence_rest_cache_ttl', '__return_zero' );

		$this->assertSame( '', $this->cache_control( $response ) );
	}

	public function test_ttl_is_filterable() {
		self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$ttl = static function () {
			return 123;
		};

		add_filter( 'jazzsequence_rest_cache_ttl', $ttl );
		$response = $this->dispatch( '/wp/v2/posts' );
		remove_filter( 'jazzsequence_rest_cache_ttl', $ttl );

		$this->assertStringContainsString( 's-maxage=123', $this->cache_control( $response ) );
	}
}
