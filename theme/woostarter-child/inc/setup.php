<?php
/**
 * Theme setup.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register theme features, image sizes and navigation menus.
 */
function woostarter_setup() {
	load_child_theme_textdomain( 'woostarter', get_stylesheet_directory() . '/languages' );

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	add_image_size( 'woostarter-product', 800, 1000, true );
	add_image_size( 'woostarter-thumbnail', 400, 500, true );
	add_image_size( 'woostarter-gallery', 1200, 1500, true );

	register_nav_menus(
		array(
			'primary' => __( 'Menu główne', 'woostarter' ),
			'footer'  => __( 'Menu w stopce', 'woostarter' ),
		)
	);
}
add_action( 'after_setup_theme', 'woostarter_setup', 20 );

/**
 * Serve catalog and single-product images in the 4:5 ratio the grid expects.
 *
 * WooCommerce defaults to square thumbnails at 300px, which both breaks the
 * card proportions and ships a smaller file than the grid slot renders.
 *
 * @param array<string,mixed> $size Image size arguments.
 * @return array<string,mixed>
 */
function woostarter_woocommerce_thumbnail_size( $size ) {
	$size['width']  = 400;
	$size['height'] = 500;
	$size['crop']   = 1;

	return $size;
}
add_filter( 'woocommerce_get_image_size_thumbnail', 'woostarter_woocommerce_thumbnail_size' );

/**
 * Match the single-product image to the registered 4:5 gallery size.
 *
 * @param array<string,mixed> $size Image size arguments.
 * @return array<string,mixed>
 */
function woostarter_woocommerce_single_size( $size ) {
	$size['width']  = 800;
	$size['height'] = 1000;
	$size['crop']   = 1;

	return $size;
}
add_filter( 'woocommerce_get_image_size_single', 'woostarter_woocommerce_single_size' );
add_filter( 'woocommerce_get_image_size_gallery_thumbnail', 'woostarter_woocommerce_thumbnail_size' );
