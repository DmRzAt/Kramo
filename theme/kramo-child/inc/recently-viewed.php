<?php
/**
 * Recently viewed products, stored client side and rendered on demand.
 *
 * The list lives in localStorage rather than a cookie or user meta, so the
 * markup stays identical for every visitor and the page cache keeps working.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maximum number of remembered products.
 *
 * @return int
 */
function kramo_recently_viewed_limit() {
	return (int) apply_filters( 'kramo_recently_viewed_limit', 8 );
}

/**
 * Render the empty section that the client fills in.
 */
function kramo_render_recently_viewed() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}

	if ( ! is_woocommerce() && ! is_cart() ) {
		return;
	}

	static $rendered = false;

	if ( $rendered ) {
		return;
	}

	$rendered = true;

	printf(
		'<section class="kramo-recent" data-kramo-recent hidden>'
		. '<h2 class="kramo-recent__title">%1$s</h2>'
		. '<div class="kramo-recent__track" data-kramo-recent-track></div>'
		. '</section>',
		esc_html__( 'Ostatnio oglądane', 'kramo' )
	);
}
// Priority 5 keeps the section inside the content wrapper that WooCommerce
// closes at priority 10; rendering after it would place the strip beside the
// catalog column instead of under it.
add_action( 'woocommerce_after_main_content', 'kramo_render_recently_viewed', 5 );
add_action( 'woocommerce_after_cart', 'kramo_render_recently_viewed', 20 );

/**
 * Return card data for a list of remembered product IDs.
 */
function kramo_ajax_recently_viewed() {
	check_ajax_referer( 'kramo_recent', 'nonce' );

	$ids = isset( $_GET['product_ids'] )
		? kramo_validate_wishlist_ids( wp_unslash( $_GET['product_ids'] ) )
		: array();

	$ids = array_slice( $ids, 0, kramo_recently_viewed_limit() );

	if ( ! $ids ) {
		wp_send_json_success( array( 'items' => array() ) );
	}

	$items = array();

	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );

		if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
			continue;
		}

		$items[] = array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'url'       => get_permalink( $product->get_id() ),
			'price'     => kramo_plain_price( $product ),
			'thumbnail' => get_the_post_thumbnail_url( $product->get_id(), 'woocommerce_thumbnail' ),
		);
	}

	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_kramo_recently_viewed', 'kramo_ajax_recently_viewed' );
add_action( 'wp_ajax_nopriv_kramo_recently_viewed', 'kramo_ajax_recently_viewed' );
