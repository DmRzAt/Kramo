<?php

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not loaded.' );
}

$zone_name = 'Polska';
$zone_id   = 0;

foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
	if ( $zone_name === $zone_data['zone_name'] ) {
		$zone_id = (int) $zone_data['zone_id'];
		break;
	}
}

$zone = $zone_id ? new WC_Shipping_Zone( $zone_id ) : new WC_Shipping_Zone();

if ( ! $zone_id ) {
	$zone->set_zone_name( $zone_name );
	$zone->set_zone_order( 1 );
}

$has_pl = false;
foreach ( $zone->get_zone_locations() as $location ) {
	if ( 'country' === $location->type && 'PL' === $location->code ) {
		$has_pl = true;
	}
}

if ( ! $has_pl ) {
	$zone->add_location( 'PL', 'country' );
}

$zone->save();

$existing = array();
foreach ( $zone->get_shipping_methods() as $method ) {
	$existing[] = $method->get_title();
}

$wanted = array(
	array( 'flat_rate', 'Paczkomat InPost', '12' ),
	array( 'flat_rate', 'Kurier InPost', '16' ),
	array( 'flat_rate', 'Orlen Paczka', '12' ),
	array( 'free_shipping', 'Darmowa dostawa', '' ),
);

foreach ( $wanted as $method ) {
	list( $type, $title, $cost ) = $method;

	if ( in_array( $title, $existing, true ) ) {
		continue;
	}

	$instance_id = $zone->add_shipping_method( $type );
	$option_key  = 'woocommerce_' . $type . '_' . $instance_id . '_settings';
	$settings    = get_option( $option_key, array() );
	$settings    = is_array( $settings ) ? $settings : array();

	$settings['title'] = $title;

	if ( 'flat_rate' === $type ) {
		$settings['cost']       = $cost;
		$settings['tax_status'] = 'none';
	}

	if ( 'free_shipping' === $type ) {
		$settings['requires']   = 'min_amount';
		$settings['min_amount'] = '200';
	}

	update_option( $option_key, $settings );
}

// The public demo has no live gateway credentials, so cash on delivery keeps
// the checkout completable end to end for visitors.
$cod                 = get_option( 'woocommerce_cod_settings', array() );
$cod                 = is_array( $cod ) ? $cod : array();
$cod['enabled']      = 'yes';
$cod['title']        = 'Płatność przy odbiorze (demo)';
$cod['description']  = 'To wersja demonstracyjna — zamówienie nie zostanie zrealizowane.';
$cod['instructions'] = 'Demo: żadna płatność nie jest pobierana.';
update_option( 'woocommerce_cod_settings', $cod );

update_option( 'rank_math_wizard_completed', true );
update_option( 'rank_math_registration_skip', true );

$general = get_option( 'rank-math-options-general', array() );
$general = is_array( $general ) ? $general : array();
$general['breadcrumbs'] = 'on';
update_option( 'rank-math-options-general', $general );

$sitemap = get_option( 'rank-math-options-sitemap', array() );
$sitemap = is_array( $sitemap ) ? $sitemap : array();
$sitemap['authors_sitemap']         = 'off';
$sitemap['tax_post_tag_sitemap']    = 'off';
$sitemap['tax_product_tag_sitemap'] = 'off';
update_option( 'rank-math-options-sitemap', $sitemap );

$titles = get_option( 'rank-math-options-titles', array() );
$titles = is_array( $titles ) ? $titles : array();
$titles['disable_author_archives'] = 'on';
$titles['knowledgegraph_type']     = 'company';
if ( empty( $titles['knowledgegraph_name'] ) ) {
	$titles['knowledgegraph_name'] = get_bloginfo( 'name' );
}
update_option( 'rank-math-options-titles', $titles );

$modules = (array) get_option( 'rank_math_modules', array() );
if ( ! in_array( 'sitemap', $modules, true ) ) {
	$modules[] = 'sitemap';
	update_option( 'rank_math_modules', $modules );
}

if ( class_exists( '\\RankMath\\Sitemap\\Router' ) ) {
	new \RankMath\Sitemap\Router();
	do_action( 'init' );
}

update_option( 'updraft_interval', 'weekly' );
update_option( 'updraft_interval_database', 'daily' );

flush_rewrite_rules( true );

WP_CLI::success( 'Shipping, SEO and demo payment configuration applied.' );
