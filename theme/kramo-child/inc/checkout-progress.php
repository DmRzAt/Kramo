<?php
/**
 * Checkout step indicator.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the checkout steps and which one is current.
 *
 * @return array<int, array{key:string,label:string,state:string}>
 */
function kramo_checkout_steps() {
	$current = 'cart';

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		$current = is_wc_endpoint_url( 'order-received' ) ? 'done' : 'checkout';
	}

	$order = array( 'cart', 'checkout', 'done' );
	$index = array_search( $current, $order, true );
	$index = false === $index ? 0 : $index;

	$labels = array(
		'cart'     => __( 'Koszyk', 'kramo' ),
		'checkout' => __( 'Dane i dostawa', 'kramo' ),
		'done'     => __( 'Płatność', 'kramo' ),
	);

	$steps = array();

	foreach ( $order as $position => $key ) {
		if ( $position < $index ) {
			$state = 'done';
		} elseif ( $position === $index ) {
			$state = 'current';
		} else {
			$state = 'upcoming';
		}

		$steps[] = array(
			'key'   => $key,
			'label' => $labels[ $key ],
			'state' => $state,
		);
	}

	return $steps;
}

/**
 * Print the step indicator above the cart and checkout forms.
 */
function kramo_render_checkout_steps() {
	if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
		return;
	}

	$steps = kramo_checkout_steps();

	printf( '<ol class="kramo-checkout-steps" aria-label="%s">', esc_attr__( 'Etapy zamówienia', 'kramo' ) );

	foreach ( $steps as $step ) {
		printf(
			'<li class="kramo-checkout-steps__item kramo-checkout-steps__item--%1$s"%2$s>%3$s</li>',
			esc_attr( $step['state'] ),
			'current' === $step['state'] ? ' aria-current="step"' : '',
			esc_html( $step['label'] )
		);
	}

	echo '</ol>';
}
add_action( 'woocommerce_before_cart', 'kramo_render_checkout_steps', 5 );
add_action( 'woocommerce_before_checkout_form', 'kramo_render_checkout_steps', 5 );
