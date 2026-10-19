<?php
/**
 * AKDISI Info Manager — Headless exposure.
 *
 * The Next.js frontend (akdisi.my.id) reads the "Info Website" content through
 * the WP REST API. Two things were missing for that to work:
 *
 *   1. The custom post types did not support `custom-fields`, so WordPress never
 *      exposed the `meta` object in REST responses (testimonial role, project
 *      client/role/status/visit_url).
 *   2. The company contact block lives in the `akdisi_info_contact` option,
 *      which is not public anywhere.
 *
 * This module adds both. It is deliberately small and side-effect free: it only
 * registers support/meta/routes, it never changes stored data.
 *
 * @package AKDISI_IM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Enable the REST `meta` field on every Info Website post type.
 *
 * `add_post_type_support()` can run after registration, so this works even
 * though the CPTs are registered in post-types.php.
 */
function akdisi_im_headless_post_type_support() {
	foreach ( array( 'akdisi_project', 'akdisi_client', 'akdisi_faq', 'akdisi_testimonial' ) as $type ) {
		add_post_type_support( $type, 'custom-fields' );
	}
}
add_action( 'init', 'akdisi_im_headless_post_type_support', 20 );

/**
 * 2. Register the meta keys the frontend reads, with REST visibility.
 *
 * Project meta (client/role/status/visit_url/gallery) is already registered in
 * metaboxes.php — re-registering here is harmless and keeps this module the
 * single place that documents the headless contract.
 */
function akdisi_im_headless_register_meta() {
	// Testimonial — job title / company shown under the client name.
	register_post_meta(
		'akdisi_testimonial',
		'role',
		array(
			'type'         => 'string',
			'single'       => true,
			'show_in_rest' => true,
		)
	);

	// Project — fields the detail page and card can surface.
	foreach ( array( 'client', 'role', 'status', 'frontend_slug' ) as $key ) {
		register_post_meta(
			'akdisi_project',
			$key,
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}

	// Project — editorial enrichment (work scope + page structure) as two
	// multi-line strings, one bullet per line. `content` is a reserved meta
	// key (it maps to post_content), so the page-structure list uses
	// `content_list` instead.
	foreach ( array( 'work', 'content_list' ) as $key ) {
		register_post_meta(
			'akdisi_project',
			$key,
			array(
				'type'         => 'string',
				'single'       => true,
				'show_in_rest' => true,
			)
		);
	}
}
add_action( 'init', 'akdisi_im_headless_register_meta', 20 );

/**
 * 3. Expose the company contact block (`akdisi_info_contact`) over REST.
 *
 * Read-only, no write surface — the option is edited in wp-admin only.
 */
function akdisi_im_headless_register_routes() {
	register_rest_route(
		'akdisi/v1',
		'/info',
		array(
			'methods'             => 'GET',
			'callback'            => 'akdisi_im_headless_info_handler',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'akdisi_im_headless_register_routes' );

/**
 * Build the contact payload from the stored option.
 *
 * @return array<string,mixed>
 */
function akdisi_im_headless_info_payload() {
	$raw = get_option( 'akdisi_info_contact' );
	if ( ! is_array( $raw ) ) {
		$raw = array();
	}

	$defaults = array(
		'email'     => '',
		'phone'     => '',
		'whatsapp'  => '',
		'location'  => '',
		'hours'     => '',
		'address'   => '',
		'site_name' => get_bloginfo( 'name' ),
		'tagline'   => get_bloginfo( 'description' ),
	);

	$info = array_merge( $defaults, array_intersect_key( $raw, $defaults ) );

	// Normalise the WhatsApp link to a wa.me URL when a bare number is stored.
	if ( '' !== $info['whatsapp'] && ! preg_match( '#^https?://#i', $info['whatsapp'] ) ) {
		$info['whatsapp'] = 'https://wa.me/' . preg_replace( '/\D/', '', $info['whatsapp'] );
	}

	return $info;
}

/**
 * REST handler for GET /wp-json/akdisi/v1/info.
 *
 * @param WP_REST_Request $request Request instance.
 * @return WP_REST_Response
 */
function akdisi_im_headless_info_handler( $request ) {
	return rest_ensure_response( akdisi_im_headless_info_payload() );
}
