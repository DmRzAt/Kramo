<?php
/**
 * Front-end assets.
 *
 * @package Kramo
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
function kramo_asset_version( $relative_path ) {
	$path = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );

	return file_exists( $path ) ? (string) filemtime( $path ) : null;
}

/**
 * Enqueue child-theme styles and scripts in dependency order.
 */
function kramo_enqueue_assets() {
	$theme_uri = get_stylesheet_directory_uri();

	// GeneratePress enqueues style.css, which intentionally contains metadata only.
	wp_dequeue_style( 'generate-child' );

	wp_enqueue_style(
		'kramo-tokens',
		$theme_uri . '/assets/css/tokens.css',
		array(),
		kramo_asset_version( 'assets/css/tokens.css' )
	);

	wp_enqueue_style(
		'kramo-base',
		$theme_uri . '/assets/css/base.css',
		array( 'kramo-tokens' ),
		kramo_asset_version( 'assets/css/base.css' )
	);

	$preset_dependencies = array( 'kramo-base' );
	if ( kramo_needs_woocommerce_assets() ) {
		wp_enqueue_style(
			'kramo-woocommerce',
			$theme_uri . '/assets/css/woo.css',
			array( 'kramo-base' ),
			kramo_asset_version( 'assets/css/woo.css' )
		);
		$preset_dependencies = array( 'kramo-woocommerce' );

		wp_enqueue_script(
			'kramo-catalog',
			$theme_uri . '/assets/js/catalog.js',
			array(),
			kramo_asset_version( 'assets/js/catalog.js' ),
			true
		);
		wp_script_add_data( 'kramo-catalog', 'strategy', 'defer' );

		wp_enqueue_script(
			'kramo-wishlist',
			$theme_uri . '/assets/js/wishlist.js',
			array(),
			kramo_asset_version( 'assets/js/wishlist.js' ),
			true
		);
		wp_script_add_data( 'kramo-wishlist', 'strategy', 'defer' );
		wp_localize_script(
			'kramo-wishlist',
			'kramoWishlist',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'kramo_wishlist' ),
				'isLoggedIn'   => is_user_logged_in(),
				'serverIds'    => is_user_logged_in()
					? kramo_get_customer_wishlist( get_current_user_id() )
					: array(),
				'addLabel'     => __( 'Dodaj do ulubionych', 'kramo' ),
				'removeLabel'  => __( 'Usuń z ulubionych', 'kramo' ),
				'emptyMessage' => __( 'Nie masz jeszcze ulubionych produktów.', 'kramo' ),
				'errorMessage' => __( 'Nie udało się zaktualizować ulubionych.', 'kramo' ),
			)
		);

		$personalization_settings = array();
		if (
			function_exists( 'is_product' )
			&& is_product()
			&& function_exists( 'kramo_get_personalization_settings' )
		) {
			$personalization_settings = kramo_get_personalization_settings(
				get_queried_object_id()
			);
		}

		if ( ! empty( $personalization_settings['enabled'] ) ) {
			wp_enqueue_script(
				'kramo-personalization',
				$theme_uri . '/assets/js/personalization.js',
				array(),
				kramo_asset_version( 'assets/js/personalization.js' ),
				true
			);
			wp_script_add_data( 'kramo-personalization', 'strategy', 'defer' );
		}
	}

	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		wp_enqueue_script(
			'kramo-filters',
			$theme_uri . '/assets/js/filters.js',
			array(),
			kramo_asset_version( 'assets/js/filters.js' ),
			true
		);
		wp_script_add_data( 'kramo-filters', 'strategy', 'defer' );
		wp_localize_script(
			'kramo-filters',
			'kramoFilters',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'kramo_catalog' ),
				'loadingText'  => __( 'Ładowanie produktów…', 'kramo' ),
				'errorMessage' => __( 'Nie udało się załadować produktów.', 'kramo' ),
			)
		);
	}

	$preset_path = kramo_get_active_preset_stylesheet();
	if ( $preset_path ) {
		wp_enqueue_style(
			'kramo-preset',
			$theme_uri . '/' . $preset_path,
			$preset_dependencies,
			kramo_asset_version( $preset_path )
		);
	}

	wp_enqueue_script(
		'kramo-main',
		$theme_uri . '/assets/js/main.js',
		array(),
		kramo_asset_version( 'assets/js/main.js' ),
		true
	);
	wp_script_add_data( 'kramo-main', 'strategy', 'defer' );
	wp_script_add_data( 'generate-menu', 'strategy', 'defer' );
}
add_action( 'wp_enqueue_scripts', 'kramo_enqueue_assets', 20 );

/**
 * Preload the primary local font.
 */
function kramo_preload_primary_font() {
	$font_url = get_stylesheet_directory_uri() . '/assets/fonts/InterVariable.woff2';

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( $font_url )
	);
}
add_action( 'wp_head', 'kramo_preload_primary_font', 1 );
