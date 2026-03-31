<?php
/**
 * Plugin Name: Headless Contact Form
 * Plugin URI:  https://github.com/jazzsequence/jazz-nextjs
 * Description: REST endpoint for the Next.js contact form. Accepts submissions
 *              and processes them through Ninja Forms (save + email actions).
 * Version:     1.1.0
 * Author:      Chris Reynolds
 *
 * Endpoint: POST /wp-json/jazz-nextjs/v1/contact
 * Auth:     WordPress Application Password (Authorization: Basic <base64>)
 * Body:     { "name": string, "email": string, "message": string }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The Ninja Forms form ID for the "Contact Me" form. */
const JAZZ_CONTACT_FORM_ID = 1;

add_action( 'rest_api_init', 'jazz_register_contact_endpoint' );

/**
 * Register the headless contact form REST API endpoint.
 *
 * @return void
 */
function jazz_register_contact_endpoint(): void {
	register_rest_route(
		'jazz-nextjs/v1',
		'/contact',
		[
			'methods'             => 'POST',
			'callback'            => 'jazz_handle_contact_submission',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => [
				'name'    => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => fn( $v ) => ! empty( trim( $v ) ),
				],
				'email'   => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
					'validate_callback' => 'is_email',
				],
				'message' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
					'validate_callback' => fn( $v ) => ! empty( trim( $v ) ),
				],
			],
		]
	);
}

/**
 * Handle an incoming contact form submission.
 *
 * Builds the Ninja Forms data structure expected by its action pipeline and
 * calls each active action's process() method — saving the submission to the
 * NF backend and triggering email notification/confirmation.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response|WP_Error Success response or error on failure.
 */
function jazz_handle_contact_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$name    = $request->get_param( 'name' );
	$email   = $request->get_param( 'email' );
	$message = $request->get_param( 'message' );

	$field_values = [
		'name'    => $name,
		'email'   => $email,
		'message' => $message,
	];

	// Load form fields and build the data structure NF's action pipeline expects.
	$form_fields = Ninja_Forms()->form( JAZZ_CONTACT_FORM_ID )->get_fields();
	$fields_data = [];

	foreach ( $form_fields as $field_id => $field_obj ) {
		$key      = $field_obj->get_setting( 'key' );
		$settings = $field_obj->get_settings();

		// Inject the submitted value; leave blank for fields we don't populate (e.g. submit).
		$settings['value'] = isset( $field_values[ $key ] ) ? $field_values[ $key ] : '';

		$fields_data[ $field_id ] = $settings;
	}

	// Build the complete form data array that NF action handlers expect.
	$form_data = [
		'id'       => JAZZ_CONTACT_FORM_ID,
		'settings' => Ninja_Forms()->form( JAZZ_CONTACT_FORM_ID )->get(),
		'fields'   => $fields_data,
		'extra'    => [],
		'errors'   => [
			'fields' => [],
			'form'   => [],
		],
		'response' => [],
	];

	// Process each active action (save, email notification, confirmation, success message).
	$actions = Ninja_Forms()->form( JAZZ_CONTACT_FORM_ID )->get_actions();

	foreach ( $actions as $action_obj ) {
		if ( ! $action_obj->get_setting( 'active' ) ) {
			continue;
		}

		$action_type = $action_obj->get_setting( 'type' );

		// Retrieve the registered action handler for this type.
		$action_controller = Ninja_Forms()->action_types->get( $action_type );

		if ( ! $action_controller ) {
			continue;
		}

		$form_data = $action_controller->process( $action_obj->get_settings(), $form_data );
	}

	if ( ! empty( $form_data['errors']['fields'] ) || ! empty( $form_data['errors']['form'] ) ) {
		return new WP_Error(
			'ninja_forms_error',
			sprintf( 'Form submission failed: %s', wp_json_encode( $form_data['errors'] ) ),
			[ 'status' => 422 ]
		);
	}

	return new WP_REST_Response( [ 'success' => true ], 200 );
}
