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
