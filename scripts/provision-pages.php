<?php

if ( ! defined( 'ABSPATH' ) ) {
	WP_CLI::error( 'WordPress is not loaded.' );
}

function kramo_provision_page( $title, $slug, $content ) {
	$existing = get_page_by_path( $slug );
	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( $page_id->get_error_message() );
	}

	return (int) $page_id;
}

$pages = array(
	'shop'     => array( 'Sklep', 'sklep', '' ),
	'cart'     => array( 'Koszyk', 'koszyk', '[woocommerce_cart]' ),
	'checkout' => array( 'Zamówienie', 'zamowienie', '[woocommerce_checkout]' ),
	'account'  => array( 'Moje konto', 'moje-konto', '[woocommerce_my_account]' ),
	'terms'    => array( 'Regulamin', 'regulamin', '' ),
	'privacy'  => array( 'Polityka prywatności', 'polityka-prywatnosci', '' ),
	'shipping' => array(
		'Dostawa i płatności',
		'dostawa-i-platnosci',
		'<!-- wp:paragraph --><p>Informacje o formach dostawy i płatności uzupełni właściciel sklepu.</p><!-- /wp:paragraph -->',
	),
	'returns'  => array(
		'Zwroty i reklamacje',
		'zwroty-i-reklamacje',
		'<!-- wp:paragraph --><p>Zasady zwrotów i reklamacji uzupełni właściciel sklepu.</p><!-- /wp:paragraph -->',
	),
	'cookies'  => array(
		'Polityka cookies',
		'polityka-cookies',
		'<!-- wp:paragraph --><p>Informacje o plikach cookies uzupełni właściciel sklepu.</p><!-- /wp:paragraph -->',
	),
);

$ids = array();
foreach ( $pages as $key => $page ) {
	list( $title, $slug, $content ) = $page;
	$ids[ $key ] = kramo_provision_page( $title, $slug, $content );
}

update_option( 'woocommerce_shop_page_id', $ids['shop'] );
update_option( 'woocommerce_cart_page_id', $ids['cart'] );
update_option( 'woocommerce_checkout_page_id', $ids['checkout'] );
update_option( 'woocommerce_myaccount_page_id', $ids['account'] );
update_option( 'woocommerce_terms_page_id', $ids['terms'] );
update_option( 'wp_page_for_privacy_policy', $ids['privacy'] );

// WordPress and WooCommerce create their own English pages on activation.
// They duplicate the Polish set above and clutter the generated menu.
$removed = 0;
foreach ( array( 'shop', 'cart', 'checkout', 'my-account', 'refund_returns', 'privacy-policy' ) as $slug ) {
	$duplicate = get_page_by_path( $slug );
	if ( ! $duplicate instanceof WP_Post ) {
		continue;
	}
	if ( in_array( (int) $duplicate->ID, array_map( 'intval', $ids ), true ) ) {
		continue;
	}
	wp_delete_post( $duplicate->ID, true );
	$removed++;
}

WP_CLI::success( sprintf( 'Store pages ready (%d), duplicates removed (%d).', count( $ids ), $removed ) );
