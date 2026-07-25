<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function woostarter_security_headers( $headers ) {
	$headers['X-Frame-Options']        = 'SAMEORIGIN';
	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']     = 'geolocation=(), microphone=(), camera=(), interest-cohort=()';
	$headers['Cross-Origin-Opener-Policy'] = 'same-origin';

	if ( is_ssl() ) {
		$headers['Strict-Transport-Security'] = 'max-age=15768000';
	}

	return $headers;
}
add_filter( 'wp_headers', 'woostarter_security_headers' );

function woostarter_hide_version() {
	return '';
}
add_filter( 'the_generator', 'woostarter_hide_version' );
remove_action( 'wp_head', 'wp_generator' );

function woostarter_strip_version_from_assets( $src ) {
	if ( $src && false !== strpos( $src, 'ver=' . get_bloginfo( 'version' ) ) ) {
		$src = remove_query_arg( 'ver', $src );
	}

	return $src;
}
add_filter( 'style_loader_src', 'woostarter_strip_version_from_assets', 20 );
add_filter( 'script_loader_src', 'woostarter_strip_version_from_assets', 20 );

function woostarter_xmlrpc_enabled() {
	return (bool) apply_filters( 'woostarter_enable_xmlrpc', false );
}

if ( ! woostarter_xmlrpc_enabled() ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'xmlrpc_methods', '__return_empty_array' );
	add_filter( 'pings_open', '__return_false', 20 );

	function woostarter_block_xmlrpc_requests() {
		if ( ! isset( $_SERVER['SCRIPT_FILENAME'] ) ) {
			return;
		}

		$script = basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) );
		if ( 'xmlrpc.php' === $script ) {
			status_header( 403 );
			exit;
		}
	}
	add_action( 'init', 'woostarter_block_xmlrpc_requests', 1 );
}

function woostarter_remove_login_error_details() {
	return __( 'Nieprawidłowe dane logowania.', 'woostarter' );
}
add_filter( 'login_errors', 'woostarter_remove_login_error_details' );

function woostarter_disable_user_enumeration() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}

	$author = isset( $_GET['author'] ) ? sanitize_text_field( wp_unslash( $_GET['author'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' !== $author && is_numeric( $author ) ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'woostarter_disable_user_enumeration' );

function woostarter_restrict_user_rest_route( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}

	foreach ( array( '/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)' ) as $route ) {
		unset( $endpoints[ $route ] );
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'woostarter_restrict_user_rest_route' );

function woostarter_form_spam_field_names() {
	return array(
		'honeypot' => 'ws_website_url',
		'started'  => 'ws_form_started',
	);
}

function woostarter_render_spam_trap() {
	$fields = woostarter_form_spam_field_names();

	printf(
		'<div class="woostarter-spam-trap" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
			<label for="%1$s">%2$s</label>
			<input type="text" id="%1$s" name="%1$s" value="" tabindex="-1" autocomplete="off">
		</div>
		<input type="hidden" name="%3$s" value="%4$s">',
		esc_attr( $fields['honeypot'] ),
		esc_html__( 'Zostaw to pole puste', 'woostarter' ),
		esc_attr( $fields['started'] ),
		esc_attr( (string) time() )
	);
}

function woostarter_spam_trap_triggered() {
	$fields       = woostarter_form_spam_field_names();
	$minimum_time = (int) apply_filters( 'woostarter_minimum_form_seconds', 3 );

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

function woostarter_guard_comment_form( $commentdata ) {
	if ( is_user_logged_in() ) {
		return $commentdata;
	}

	if ( woostarter_spam_trap_triggered() ) {
		wp_die(
			esc_html__( 'Nie udało się wysłać formularza. Spróbuj ponownie.', 'woostarter' ),
			esc_html__( 'Błąd', 'woostarter' ),
			array( 'response' => 403 )
		);
	}

	return $commentdata;
}
add_filter( 'preprocess_comment', 'woostarter_guard_comment_form' );
add_action( 'comment_form_after_fields', 'woostarter_render_spam_trap' );
add_action( 'comment_form_logged_in_after', 'woostarter_render_spam_trap' );
