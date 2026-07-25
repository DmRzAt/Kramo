<?php
/**
 * Host-agnostic page-cache layer.
 *
 * The template must not depend on one hosting stack, so this module detects
 * whichever cache plugin is present (LiteSpeed Cache, WP Rocket, W3 Total
 * Cache) and applies the same policy to it: never cache the dynamic
 * WooCommerce pages, and purge product pages when stock or price changes.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect the active cache backend.
 *
 * @return string One of 'litespeed', 'wp-rocket', 'w3tc' or 'none'.
 */
function woostarter_cache_backend() {
	if ( defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\Core' ) ) {
		return 'litespeed';
	}

	if ( defined( 'WP_ROCKET_VERSION' ) ) {
		return 'wp-rocket';
	}

	if ( defined( 'W3TC' ) || defined( 'W3TC_VERSION' ) ) {
		return 'w3tc';
	}

	return 'none';
}

/**
 * Whether the server itself runs LiteSpeed, regardless of installed plugins.
 *
 * @return bool
 */
function woostarter_server_is_litespeed() {
	$software = isset( $_SERVER['SERVER_SOFTWARE'] )
		? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) )
		: '';

	return false !== stripos( $software, 'litespeed' );
}

/**
 * URL paths that must never be served from a shared page cache.
 *
 * Caching these would let one customer receive another customer's cart,
 * checkout or account page.
 *
 * @return array<int,string>
 */
function woostarter_cache_excluded_paths() {
	$paths = array();

	foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
		$page_id = wc_get_page_id( $page );
		if ( $page_id > 0 ) {
			$path = wp_parse_url( get_permalink( $page_id ), PHP_URL_PATH );
			if ( $path ) {
				$paths[] = untrailingslashit( $path );
			}
		}
	}

	return array_values( array_unique( array_filter( $paths ) ) );
}

/**
 * Whether the current request must bypass the page cache.
 *
 * @return bool
 */
function woostarter_request_is_uncacheable() {
	if ( is_user_logged_in() || is_admin() ) {
		return true;
	}

	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return true;
	}

	if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
		return true;
	}

	return false;
}

/**
 * Apply the no-cache policy to whichever backend is active.
 */
function woostarter_apply_cache_policy() {
	if ( ! woostarter_request_is_uncacheable() ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	do_action( 'litespeed_control_set_nocache', 'WooStarter dynamic page' );
	do_action( 'rocket_do_not_cache' );
}
add_action( 'template_redirect', 'woostarter_apply_cache_policy', 0 );

/**
 * Purge a product's cached page when its stock or price changes.
 *
 * @param int $product_id Product ID.
 */
function woostarter_purge_product_cache( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return;
	}

	$url = get_permalink( $product_id );

	switch ( woostarter_cache_backend() ) {
		case 'litespeed':
			do_action( 'litespeed_purge_post', $product_id );
			break;
		case 'wp-rocket':
			if ( function_exists( 'rocket_clean_post' ) ) {
				rocket_clean_post( $product_id );
			}
			break;
		case 'w3tc':
			if ( function_exists( 'w3tc_flush_post' ) ) {
				w3tc_flush_post( $product_id );
			}
			break;
	}

	do_action( 'woostarter_purged_product_cache', $product_id, $url );
}
add_action( 'woocommerce_product_set_stock', 'woostarter_purge_product_cache' );
add_action( 'woocommerce_variation_set_stock', 'woostarter_purge_product_cache' );
add_action( 'woocommerce_update_product', 'woostarter_purge_product_cache' );

/**
 * Recommended settings for the detected backend, shown to the shop manager.
 *
 * @return array<string,string>
 */
function woostarter_cache_recommendations() {
	$excluded = implode( ', ', woostarter_cache_excluded_paths() );

	switch ( woostarter_cache_backend() ) {
		case 'litespeed':
			return array(
				'plugin'   => 'LiteSpeed Cache',
				'settings' => sprintf(
					/* translators: %s: excluded URL paths. */
					__( 'Włącz cache publiczny, Object Cache i wykluczenia URI: %s', 'woostarter' ),
					$excluded
				),
			);
		case 'wp-rocket':
			return array(
				'plugin'   => 'WP Rocket',
				'settings' => sprintf(
					/* translators: %s: excluded URL paths. */
					__( 'Włącz cache stron i dodaj wykluczenia: %s', 'woostarter' ),
					$excluded
				),
			);
		case 'w3tc':
			return array(
				'plugin'   => 'W3 Total Cache',
				'settings' => sprintf(
					/* translators: %s: excluded URL paths. */
					__( 'Włącz Page Cache i dodaj wykluczenia: %s', 'woostarter' ),
					$excluded
				),
			);
	}

	return array(
		'plugin'   => woostarter_server_is_litespeed() ? 'LiteSpeed Cache' : 'WP Rocket',
		'settings' => __( 'Brak wtyczki cache. Zainstaluj zalecaną wtyczkę dla tego hostingu.', 'woostarter' ),
	);
}

/**
 * Show the cache recommendation on the WooCommerce status screen.
 */
function woostarter_cache_admin_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'woocommerce_page_wc-status' !== $screen->id ) {
		return;
	}

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$recommendation = woostarter_cache_recommendations();

	printf(
		'<div class="notice notice-info"><p><strong>%s</strong> %s</p></div>',
		esc_html( $recommendation['plugin'] ),
		esc_html( $recommendation['settings'] )
	);
}
add_action( 'admin_notices', 'woostarter_cache_admin_notice' );
