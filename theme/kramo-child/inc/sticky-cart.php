<?php
/**
 * Mobile sticky purchase bar for the product page.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the bar. It is hidden until the main add-to-cart button scrolls out
 * of view, and it submits the real product form rather than duplicating it.
 */
function kramo_render_sticky_cart() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;

	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}

	printf(
		'<div class="kramo-sticky-cart" data-kramo-sticky-cart hidden>'
		. '<span class="kramo-sticky-cart__info">'
		. '<span class="kramo-sticky-cart__name">%1$s</span>'
		. '<span class="kramo-sticky-cart__price">%2$s</span>'
		. '</span>'
		. '<button type="button" class="button kramo-sticky-cart__action" data-kramo-sticky-cart-action>%3$s</button>'
		. '</div>',
		esc_html( $product->get_name() ),
		wp_kses_post( $product->get_price_html() ),
		esc_html__( 'Dodaj do koszyka', 'kramo' )
	);
}
add_action( 'wp_footer', 'kramo_render_sticky_cart', 7 );
