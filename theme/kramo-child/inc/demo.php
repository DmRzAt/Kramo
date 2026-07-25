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
