<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kramo_is_demo() {
	if ( defined( 'KRAMO_DEMO' ) ) {
		return (bool) KRAMO_DEMO;
	}

	return '1' === getenv( 'KRAMO_DEMO' );
}

function kramo_render_demo_badge() {
	if ( ! kramo_is_demo() || is_admin() ) {
		return;
	}

	printf(
		'<div class="kramo-demo-badge"><strong>%s</strong> %s</div>',
		esc_html__( 'Wersja demonstracyjna', 'kramo' ),
		esc_html__( 'Zamówienia nie są realizowane, płatności nie są pobierane.', 'kramo' )
	);
}
add_action( 'wp_body_open', 'kramo_render_demo_badge' );

/**
 * Keep demo checkouts away from real payment processors.
 *
 * The badge promises that no payment is taken, so the demo must not be able to
 * hand a card to a live gateway even when KRAMO_PAYMENT_MODE is set to live.
 * Only the offline methods survive: they record the order and charge nothing.
 *
 * @param array<string,WC_Payment_Gateway> $gateways Available gateways.
 * @return array<string,WC_Payment_Gateway>
 */
function kramo_demo_payment_gateways( $gateways ) {
	if ( ! kramo_is_demo() || is_admin() ) {
		return $gateways;
	}

	return array_intersect_key(
		$gateways,
		array_flip( array( 'bacs', 'cheque', 'cod' ) )
	);
}
add_filter( 'woocommerce_available_payment_gateways', 'kramo_demo_payment_gateways' );

/**
 * Repeat the demo warning where the money would be taken.
 */
function kramo_demo_checkout_notice() {
	if ( ! kramo_is_demo() || ! function_exists( 'wc_add_notice' ) ) {
		return;
	}

	wc_add_notice(
		__( 'Wersja demonstracyjna: zamówienie nie zostanie zrealizowane, a płatność nie zostanie pobrana.', 'kramo' ),
		'notice'
	);
}
add_action( 'woocommerce_before_checkout_form', 'kramo_demo_checkout_notice' );

function kramo_block_demo_emails( $args ) {
	if ( kramo_is_demo() ) {
		$args['to'] = array();
	}

	return $args;
}
add_filter( 'wp_mail', 'kramo_block_demo_emails' );

function kramo_demo_admin_notice() {
	if ( ! kramo_is_demo() ) {
		return;
	}

	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		esc_html__( 'Tryb demonstracyjny: wysyłka e-maili jest wyłączona, a płatności działają tylko jako podgląd.', 'kramo' )
	);
}
add_action( 'admin_notices', 'kramo_demo_admin_notice' );
