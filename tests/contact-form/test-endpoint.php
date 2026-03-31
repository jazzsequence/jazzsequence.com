<?php
/**
 * Tests for the headless contact form REST endpoint.
 *
 * Covers REST route registration, field validation, honeypot, the
 * ninja_forms_run_action_settings merge-tag regression, and auth.
 *
 * @package JazzSequence\Tests\ContactForm
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
 */

/**
 * Tests for jazz_handle_contact_submission() and supporting functions.
 */
class Test_Contact_Form_Endpoint extends WP_UnitTestCase {

	/**
	 * Valid payload used across tests.
	 *
	 * @var array
	 */
	private array $valid = [
		'name'    => 'Test User',
		'email'   => 'test@example.com',
		'message' => 'Hello, this is a test message.',
	];

	/*
	 * -------------------------------------------------------------------------
	 * Route registration
	 * -------------------------------------------------------------------------
	 */

	/**
	 * REST route is registered at rest_api_init.
	 */
	public function test_rest_route_is_registered(): void {
		do_action( 'rest_api_init' );
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey(
			'/jazz-nextjs/v1/contact',
			$routes,
			'Contact form REST route should be registered'
		);
	}

	/*
	 * -------------------------------------------------------------------------
	 * Field validation
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Missing name returns 400.
	 */
	public function test_missing_name_returns_400(): void {
		$response = $this->post( [
			'email'   => 'test@example.com',
			'message' => 'Hi',
		] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Missing email returns 400.
	 */
	public function test_missing_email_returns_400(): void {
		$response = $this->post( [
			'name'    => 'Test',
			'message' => 'Hi',
		] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Invalid email format returns 400.
	 */
	public function test_invalid_email_returns_400(): void {
		$response = $this->post( [
			'name'    => 'Test',
			'email'   => 'not-an-email',
			'message' => 'Hi',
		] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Missing message returns 400.
	 */
	public function test_missing_message_returns_400(): void {
		$response = $this->post( [
			'name'  => 'Test',
			'email' => 'test@example.com',
		] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Honeypot
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Filled honeypot returns 200 but never reaches NF action pipeline.
	 */
	public function test_honeypot_accepts_silently_without_nf_processing(): void {
		$nf_filter_fired = false;

		add_filter(
			'ninja_forms_run_action_settings',
			function ( $settings ) use ( &$nf_filter_fired ) {
				$nf_filter_fired = true;
				return $settings;
			}
		);

		$response = $this->post( array_merge( $this->valid, [ 'website' => 'http://spam.com' ] ) );

		$this->assertEquals( 200, $response->get_status(), 'Honeypot fill should return 200 silently' );
		$this->assertFalse( $nf_filter_fired, 'NF action pipeline must not run for honeypot submissions' );
	}

	/*
	 * -------------------------------------------------------------------------
	 * NF action pipeline — merge tag resolution regression test
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Ninja_forms_run_action_settings filter fires before each NF action runs.
	 *
	 * Regression: {field:email} in Email Notification reply_to was never resolved,
	 * causing "invalid_email" validation failure. The fix applies this filter for
	 * every action before process() is invoked, matching NF's submission controller.
	 */
	public function test_action_settings_filter_fires_before_nf_actions(): void {
		$filter_call_count = 0;

		add_filter(
			'ninja_forms_run_action_settings',
			function ( $settings ) use ( &$filter_call_count ) {
				$filter_call_count++;
				return $settings;
			},
			10,
			3
		);

		$this->post( $this->valid );

		$this->assertGreaterThan(
			0,
			$filter_call_count,
			'ninja_forms_run_action_settings must fire at least once before NF actions run.'
		);
	}

	/*
	 * -------------------------------------------------------------------------
	 * Authentication
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Unauthenticated request is rejected with 401.
	 */
	public function test_unauthenticated_request_is_rejected(): void {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/jazz-nextjs/v1/contact' );
		$request->set_body( wp_json_encode( $this->valid ) );
		$request->set_header( 'content-type', 'application/json' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Dispatch a POST request to the contact endpoint as an admin user.
	 *
	 * @param array $payload JSON body.
	 * @return WP_REST_Response
	 */
	private function post( array $payload ): WP_REST_Response {
		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		$request = new WP_REST_Request( 'POST', '/jazz-nextjs/v1/contact' );
		$request->set_body( wp_json_encode( $payload ) );
			$request->set_header( 'content-type', 'application/json' );
		$response = rest_get_server()->dispatch( $request );

		wp_set_current_user( 0 );
		return $response;
	}
}
