<?php
/**
 * Plugin Name: Headless Contact Form
 * Plugin URI:  https://github.com/jazzsequence/jazz-nextjs
 * Description: REST endpoint for the Next.js contact form. Accepts submissions
 *              and processes them through Ninja Forms (save + email actions).
 * Version:     1.0.0
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

function jazz_handle_contact_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$name    = $request->get_param( 'name' );
	$email   = $request->get_param( 'email' );
	$message = $request->get_param( 'message' );

	// Build Ninja Forms field data keyed by field key.
	$fields_updates = [
		'name'    => $name,
		'email'   => $email,
		'message' => $message,
	];

	// Load the form and its fields to get field IDs.
	$form_fields = Ninja_Forms()->form( JAZZ_CONTACT_FORM_ID )->get_fields();

	$fields_data = [];
	foreach ( $form_fields as $field ) {
		$key = $field->get_setting( 'key' );
		if ( isset( $fields_updates[ $key ] ) ) {
			$fields_data[] = [
				'id'    => $field->get_id(),
				'value' => $fields_updates[ $key ],
			];
		}
	}

	// Process the submission — triggers save, email notification, confirmation, success message.
	try {
		$response = Ninja_Forms()->form( JAZZ_CONTACT_FORM_ID )->process_fields( $fields_data );
	} catch ( Exception $e ) {
		error_log( '[jazz-contact] Ninja Forms exception: ' . $e->getMessage() );
		return new WP_Error(
			'ninja_forms_error',
			'Failed to process form submission.',
			[ 'status' => 500 ]
		);
	}

	// process_fields returns errors array on failure.
	if ( ! empty( $response['errors'] ) ) {
		error_log( '[jazz-contact] Ninja Forms errors: ' . wp_json_encode( $response['errors'] ) );
		return new WP_Error(
			'ninja_forms_error',
			'Form submission failed.',
			[ 'status' => 422 ]
		);
	}

	return new WP_REST_Response( [ 'success' => true ], 200 );
}
