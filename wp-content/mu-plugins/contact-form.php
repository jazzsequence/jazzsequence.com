<?php
/**
 * Plugin Name: Headless Contact Form
 * Plugin URI:  https://github.com/jazzsequence/jazz-nextjs
 * Description: REST endpoint for the Next.js contact form. Records submissions
 *              in Ninja Forms and sends email notifications via wp_mail().
 * Version:     1.2.0
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
 * Records the submission in Ninja Forms and sends email notifications.
 * Uses wp_mail() directly rather than NF's action pipeline to avoid
 * dependency on NF's internal API, which varies across versions.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response|WP_Error Success response or error on failure.
 */
function jazz_handle_contact_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$name    = $request->get_param( 'name' );
	$email   = $request->get_param( 'email' );
	$message = $request->get_param( 'message' );

	// Record the submission in Ninja Forms so it appears in the NF backend.
	jazz_record_ninja_forms_submission( $name, $email, $message );

	// Send admin notification email.
	$admin_email = get_option( 'admin_email' );
	$admin_sent  = wp_mail(
		$admin_email,
		sprintf( 'New message from %s', $name ),
		sprintf(
			"<p>%s</p>\n<p>— %s (%s)</p>",
			nl2br( esc_html( $message ) ),
			esc_html( $name ),
			esc_html( $email )
		),
		[
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'Reply-To: %s <%s>', $name, $email ),
		]
	);

	// Send confirmation email to the submitter.
	wp_mail(
		$email,
		'Submission Confirmation',
		sprintf(
			'<p>Thank you %s for filling out my form!</p><p>A confirmation email was sent to %s.</p>',
			esc_html( $name ),
			esc_html( $email )
		),
		[ 'Content-Type: text/html; charset=UTF-8' ]
	);

	if ( ! $admin_sent ) {
		return new WP_Error(
			'mail_error',
			'Submission recorded but email notification could not be sent.',
			[ 'status' => 500 ]
		);
	}

	return new WP_REST_Response( [ 'success' => true ], 200 );
}

/**
 * Record a submission in the Ninja Forms database.
 *
 * Creates a submission record so the entry appears in the NF Submissions
 * admin. Fails silently — email delivery is the critical path.
 *
 * @param string $name    Submitter name.
 * @param string $email   Submitter email.
 * @param string $message Submission message.
 * @return void
 */
function jazz_record_ninja_forms_submission( string $name, string $email, string $message ): void {
	if ( ! class_exists( 'Ninja_Forms' ) ) {
		return;
	}

	try {
		$form_fields = Ninja_Forms()->form( JAZZ_CONTACT_FORM_ID )->get_fields();
		$field_values = [
			'name'    => $name,
			'email'   => $email,
			'message' => $message,
		];

		global $wpdb;

		// Insert the submission record.
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'nf3_subs',
			[
				'form_id'  => JAZZ_CONTACT_FORM_ID,
				'sub_date' => current_time( 'mysql' ),
				'user_id'  => 0,
			]
		);

		$sub_id = $wpdb->insert_id;

		if ( ! $sub_id ) {
			return;
		}

		// Insert each field value into the sub meta table.
		foreach ( $form_fields as $field_obj ) {
			$key = $field_obj->get_setting( 'key' );
			if ( isset( $field_values[ $key ] ) ) {
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prefix . 'nf3_sub_meta',
					[
						'sub_id'     => $sub_id,
						'meta_key'   => $key,
						'meta_value' => $field_values[ $key ],
					]
				);
			}
		}
	} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		// Fail silently — email is the critical path, not NF recording.
	}
}
