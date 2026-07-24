<?php
/**
 * Front-end assets.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return an asset version based on its modification time.
 *
 * @param string $relative_path Path relative to the child theme.
 * @return string|null
 */
function woostarter_asset_version( $relative_path ) {
	$path = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );

	return file_exists( $path ) ? (string) filemtime( $path ) : null;
}

/**
 * Enqueue child-theme styles and scripts in dependency order.
 */
function woostarter_enqueue_assets() {
	$theme_uri = get_stylesheet_directory_uri();

	// GeneratePress enqueues style.css, which intentionally contains metadata only.
	wp_dequeue_style( 'generate-child' );

	wp_enqueue_style(
		'woostarter-tokens',
		$theme_uri . '/assets/css/tokens.css',
		array(),
		woostarter_asset_version( 'assets/css/tokens.css' )
	);

	wp_enqueue_style(
		'woostarter-base',
		$theme_uri . '/assets/css/base.css',
		array( 'woostarter-tokens' ),
		woostarter_asset_version( 'assets/css/base.css' )
	);

	$preset_dependencies = array( 'woostarter-base' );
	if ( woostarter_needs_woocommerce_assets() ) {
		wp_enqueue_style(
			'woostarter-woocommerce',
			$theme_uri . '/assets/css/woo.css',
			array( 'woostarter-base' ),
			woostarter_asset_version( 'assets/css/woo.css' )
		);
		$preset_dependencies = array( 'woostarter-woocommerce' );

		wp_enqueue_script(
			'woostarter-catalog',
			$theme_uri . '/assets/js/catalog.js',
			array(),
			woostarter_asset_version( 'assets/js/catalog.js' ),
			true
		);
		wp_script_add_data( 'woostarter-catalog', 'strategy', 'defer' );

		wp_enqueue_script(
			'woostarter-wishlist',
			$theme_uri . '/assets/js/wishlist.js',
			array(),
			woostarter_asset_version( 'assets/js/wishlist.js' ),
			true
		);
		wp_script_add_data( 'woostarter-wishlist', 'strategy', 'defer' );
		wp_localize_script(
			'woostarter-wishlist',
			'wooStarterWishlist',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'woostarter_wishlist' ),
				'isLoggedIn'   => is_user_logged_in(),
				'serverIds'    => is_user_logged_in()
					? woostarter_get_customer_wishlist( get_current_user_id() )
					: array(),
				'addLabel'     => __( 'Dodaj do ulubionych', 'woostarter' ),
				'removeLabel'  => __( 'Usuń z ulubionych', 'woostarter' ),
				'emptyMessage' => __( 'Nie masz jeszcze ulubionych produktów.', 'woostarter' ),
				'errorMessage' => __( 'Nie udało się zaktualizować ulubionych.', 'woostarter' ),
			)
		);
	}

	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		wp_enqueue_script(
			'woostarter-filters',
			$theme_uri . '/assets/js/filters.js',
			array(),
			woostarter_asset_version( 'assets/js/filters.js' ),
			true
		);
		wp_script_add_data( 'woostarter-filters', 'strategy', 'defer' );
		wp_localize_script(
			'woostarter-filters',
			'wooStarterFilters',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'woostarter_catalog' ),
				'loadingText'  => __( 'Ładowanie produktów…', 'woostarter' ),
				'errorMessage' => __( 'Nie udało się załadować produktów.', 'woostarter' ),
			)
		);
	}

	$preset_path = woostarter_get_active_preset_stylesheet();
	if ( $preset_path ) {
		wp_enqueue_style(
			'woostarter-preset',
			$theme_uri . '/' . $preset_path,
			$preset_dependencies,
			woostarter_asset_version( $preset_path )
		);
	}

	wp_enqueue_script(
		'woostarter-main',
		$theme_uri . '/assets/js/main.js',
		array(),
		woostarter_asset_version( 'assets/js/main.js' ),
		true
	);
	wp_script_add_data( 'woostarter-main', 'strategy', 'defer' );
	wp_script_add_data( 'generate-menu', 'strategy', 'defer' );
}
add_action( 'wp_enqueue_scripts', 'woostarter_enqueue_assets', 20 );

/**
 * Preload the primary local font.
 */
function woostarter_preload_primary_font() {
	$font_url = get_stylesheet_directory_uri() . '/assets/fonts/InterVariable.woff2';

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( $font_url )
	);
}
add_action( 'wp_head', 'woostarter_preload_primary_font', 1 );
