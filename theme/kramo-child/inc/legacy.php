<?php
/**
 * Legacy key helpers (ws_* → kramo_*).
 *
 * Reads accept the old woostarter keys so existing demo data and bookmarks
 * keep working; new writes always use the kramo_* namespace.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map a Kramo meta/request key to its legacy ws_ equivalent.
 *
 * @param string $key Current key.
 * @return string
 */
function kramo_legacy_key( $key ) {
	if ( 0 === strpos( $key, '_kramo_' ) ) {
		return '_ws_' . substr( $key, 7 );
	}

	if ( 0 === strpos( $key, 'kramo_' ) ) {
		return 'ws_' . substr( $key, 6 );
	}

	return $key;
}

/**
 * Read product meta with Kramo key first, then legacy.
 *
 * @param WC_Product $product Product.
 * @param string     $key     Meta key starting with _kramo_.
 * @return mixed
 */
function kramo_product_meta( $product, $key ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$value = $product->get_meta( $key );

	if ( '' !== $value && null !== $value && false !== $value ) {
		return $value;
	}

	$legacy = kramo_legacy_key( $key );

	return $legacy !== $key ? $product->get_meta( $legacy ) : $value;
}

/**
 * Read post meta with Kramo key first, then legacy.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key starting with _kramo_.
 * @return mixed
 */
function kramo_post_meta_value( $post_id, $key ) {
	$value = get_post_meta( $post_id, $key, true );

	if ( '' !== $value && false !== $value ) {
		return $value;
	}

	$legacy = kramo_legacy_key( $key );

	return $legacy !== $key ? get_post_meta( $post_id, $legacy, true ) : $value;
}

/**
 * Read a request/query value with Kramo key first, then legacy.
 *
 * @param array<string,mixed> $source Source array.
 * @param string              $key    Key starting with kramo_.
 * @return mixed|null
 */
function kramo_source_value( array $source, $key ) {
	if ( isset( $source[ $key ] ) ) {
		return $source[ $key ];
	}

	$legacy = kramo_legacy_key( $key );

	return ( $legacy !== $key && isset( $source[ $legacy ] ) ) ? $source[ $legacy ] : null;
}

/**
 * Read a cart-item personalization field with legacy fallback.
 *
 * @param array<string,mixed> $cart_item Cart item.
 * @param string              $key       Key starting with _kramo_.
 * @return mixed|null
 */
function kramo_cart_item_value( array $cart_item, $key ) {
	if ( isset( $cart_item[ $key ] ) ) {
		return $cart_item[ $key ];
	}

	$legacy = kramo_legacy_key( $key );

	return ( $legacy !== $key && isset( $cart_item[ $legacy ] ) ) ? $cart_item[ $legacy ] : null;
}
