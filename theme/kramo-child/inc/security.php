<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kramo_security_headers( $headers ) {
	$headers['X-Frame-Options']        = 'SAMEORIGIN';
	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']     = 'geolocation=(), microphone=(), camera=(), interest-cohort=()';

	// PayPal and the BLIK flows finish in a popup that reports back through
	// window.opener, which "same-origin" severs. Cart, checkout and account keep
	// the permissive variant so the payment window can close the order.
	$headers['Cross-Origin-Opener-Policy'] = kramo_request_is_dynamic_page()
		? 'same-origin-allow-popups'
		: 'same-origin';

	if ( is_ssl() ) {
		$headers['Strict-Transport-Security'] = 'max-age=15768000';
	}

	return $headers;
}
add_filter( 'wp_headers', 'kramo_security_headers' );

function kramo_hide_version() {
	return '';
}
add_filter( 'the_generator', 'kramo_hide_version' );
remove_action( 'wp_head', 'wp_generator' );

function kramo_strip_version_from_assets( $src ) {
	if ( $src && false !== strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
		$src = remove_query_arg( 'ver', $src );
	}

	return $src;
}
add_filter( 'style_loader_src', 'kramo_strip_version_from_assets', 20 );
add_filter( 'script_loader_src', 'kramo_strip_version_from_assets', 20 );

function kramo_xmlrpc_enabled() {
	return (bool) apply_filters( 'kramo_enable_xmlrpc', false );
}

if ( ! kramo_xmlrpc_enabled() ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array' );
	add_filter( 'pings_open', '__return_false', 20 );

	function kramo_block_xmlrpc_requests() {
		if ( ! isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
			return;
		}

		$script = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) );
		if ( 'xmlrpc.php' === $script ) {
			status_header( 403 );
			exit;
		}
	}
	add_action( 'init', 'kramo_block_xmlrpc_requests', 1 );
}

function kramo_remove_login_error_details() {
	return __( 'Nieprawidłowe dane logowania.', 'kramo' );
}
add_filter( 'login_errors', 'kramo_remove_login_error_details' );

function kramo_disable_user_enumeration() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}

	$author = isset( $_GET['author'] ) ? sanitize_text_field( wp_unslash( $_GET['author'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' !== $author && is_numeric( $author ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'kramo_disable_user_enumeration' );

function kramo_restrict_user_rest_route( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}

	foreach ( array( '/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)' ) as $route ) {
		unset( $endpoints[ $route ] );
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'kramo_restrict_user_rest_route' );

function kramo_form_spam_field_names() {
	return array(
		'honeypot' => 'kramo_website_url',
		'started'  => 'kramo_form_started',
	);
}

function kramo_render_spam_trap() {
	$fields = kramo_form_spam_field_names();

	printf(
		'<div class="kramo-spam-trap" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
			<label for="%1$s">%2$s</label>
			<input type="text" id="%1$s" name="%1$s" value="" tabindex="-1" autocomplete="off">
		</div>
		<input type="hidden" name="%3$s" value="%4$s">',
		esc_attr( $fields['honeypot'] ),
		esc_html__( 'Zostaw to pole puste', 'kramo' ),
		esc_attr( $fields['started'] ),
		esc_attr( (string) time() )
	);
}

function kramo_spam_trap_triggered() {
	$fields       = kramo_form_spam_field_names();
	$minimum_time = (int) apply_filters( 'kramo_minimum_form_seconds', 3 );

	$honeypot = isset( $_POST[ $fields['honeypot'] ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? trim( sanitize_text_field( wp_unslash( $_POST[ $fields['honeypot'] ] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';

	if ( '' !== $honeypot ) {
		return true;
	}

	$started = isset( $_POST[ $fields['started'] ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? absint( wp_unslash( $_POST[ $fields['started'] ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: 0;

	if ( $started > 0 && ( time() - $started ) < $minimum_time ) {
		return true;
	}

	return false;
}

function kramo_guard_comment_form( $commentdata ) {
	if ( is_user_logged_in() ) {
		return $commentdata;
	}

	if ( kramo_spam_trap_triggered() ) {
		wp_die(
			esc_html__( 'Nie udało się wysłać formularza. Spróbuj ponownie.', 'kramo' ),
			esc_html__( 'Błąd', 'kramo' ),
			array( 'response' => 403 )
		);
	}

	return $commentdata;
}
add_filter( 'preprocess_comment', 'kramo_guard_comment_form' );
add_action( 'comment_form_after_fields', 'kramo_render_spam_trap' );
add_action( 'comment_form_logged_in_after', 'kramo_render_spam_trap' );
