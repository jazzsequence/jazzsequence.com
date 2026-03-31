<?php
/**
 * Plugin Name: Headless Contact Form
 * Plugin URI:  https://github.com/jazzsequence/jazz-nextjs
 * Description: REST endpoint for the Next.js contact form. Processes submissions
 *              through Ninja Forms' native action pipeline (save, email, etc.).
 * Version:     1.3.0
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
 * Builds the data structure Ninja Forms' action pipeline expects and calls
 * each active action's process() method using Ninja_Forms()->actions[ $type ].
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response|WP_Error Success response or error on failure.
 */
function jazz_handle_contact_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$name    = $request->get_param( 'name' );
	$email   = $request->get_param( 'email' );
	$message = $request->get_param( 'message' );

	$form_id     = JAZZ_CONTACT_FORM_ID;
	$field_values = [
		'name'    => $name,
		'email'   => $email,
		'message' => $message,
	];

	// Load form fields and build the flat field arrays NF's action pipeline expects.
	$form_fields   = Ninja_Forms()->form( $form_id )->get_fields();
	$fields        = [];
	$fields_by_key = [];

	foreach ( $form_fields as $field_id => $field_obj ) {
		$settings = $field_obj->get_settings();
		$key      = $settings['key'] ?? '';
		$value    = $field_values[ $key ] ?? '';

		// Flatten: NF merges settings into the top-level field array.
		$field                     = array_merge( $settings, [
			'id'    => $field_id,
			'key'   => $key,
			'value' => $value,
		] );
		$field['settings']['value'] = $value;

		$fields[ $field_id ]        = $field;
		$fields_by_key[ $key ]      = $field;
	}

	/*
	 * Initialize NF's field merge tag resolver so {field:key} tags resolve in action settings
	 * (e.g. reply_to: "{field:email}" in the Email Notification action).
	 */
	$field_merge_tags = Ninja_Forms()->merge_tags['fields'];
	$field_merge_tags->set_form_id( $form_id );
	foreach ( $fields as $field ) {
		$field_merge_tags->add_field( $field );
	}

	// Build the submission data array that NF action handlers receive.
	$form_obj  = Ninja_Forms()->form( $form_id )->get();
	$data      = [
		'form_id'           => $form_id,
		'settings'          => $form_obj->get_settings(),
		'fields'            => $fields,
		'fields_by_key'     => $fields_by_key,
		'extra'             => [],
		'errors'            => [
			'fields' => [],
			'form'   => [],
		],
		'processed_actions' => [],
	];

	// Run each active action through its handler, matching the NF submission controller.
	$actions = Ninja_Forms()->form( $form_id )->get_actions();

	foreach ( $actions as $action_obj ) {
		$action_settings = $action_obj->get_settings();

		if ( empty( $action_settings['active'] ) ) {
			continue;
		}

		$type = $action_settings['type'] ?? '';

		if ( ! $type || ! isset( Ninja_Forms()->actions[ $type ] ) ) {
			continue;
		}

		$action_class = Ninja_Forms()->actions[ $type ];

		if ( ! method_exists( $action_class, 'process' ) ) {
			continue;
		}

		$result = $action_class->process( $action_settings, $form_id, $data );

		if ( is_array( $result ) ) {
			$data = $result;
		}
	}

	if ( ! empty( $data['errors']['fields'] ) || ! empty( $data['errors']['form'] ) ) {
		return new WP_Error(
			'ninja_forms_error',
			sprintf( 'Form submission failed: %s', wp_json_encode( $data['errors'] ) ),
			[ 'status' => 422 ]
		);
	}

	return new WP_REST_Response( [ 'success' => true ], 200 );
}
