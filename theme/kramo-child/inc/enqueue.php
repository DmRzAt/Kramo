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
		wp_localize_script(
			'kramo-catalog',
			'kramoQuickView',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'kramo_quick_view' ),
				'addedMessage' => __( 'Dodano do koszyka', 'kramo' ),
				'errorMessage' => __( 'Nie udało się załadować podglądu.', 'kramo' ),
			)
		);

		wp_enqueue_script(
			'kramo-recently-viewed',
			$theme_uri . '/assets/js/recently-viewed.js',
			array(),
			kramo_asset_version( 'assets/js/recently-viewed.js' ),
			true
		);
		wp_script_add_data( 'kramo-recently-viewed', 'strategy', 'defer' );
		wp_localize_script(
			'kramo-recently-viewed',
			'kramoRecent',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'kramo_recent' ),
				'limit'          => kramo_recently_viewed_limit(),
				'currentProduct' => is_product() ? get_queried_object_id() : 0,
			)
		);

		if ( kramo_is_catalog_screen() ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}

		if ( is_checkout() ) {
			wp_enqueue_script(
				'kramo-checkout',
				$theme_uri . '/assets/js/checkout.js',
				array(),
				kramo_asset_version( 'assets/js/checkout.js' ),
				true
			);
			wp_script_add_data( 'kramo-checkout', 'strategy', 'defer' );
		}

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
				'addLabel'       => __( 'Dodaj do ulubionych', 'kramo' ),
				'removeLabel'    => __( 'Usuń z ulubionych', 'kramo' ),
				'addedMessage'   => __( 'Dodano do ulubionych', 'kramo' ),
				'removedMessage' => __( 'Usunięto z ulubionych', 'kramo' ),
				'emptyMessage'   => __( 'Nie masz jeszcze ulubionych produktów.', 'kramo' ),
				'errorMessage'   => __( 'Nie udało się zaktualizować ulubionych.', 'kramo' ),
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

	if ( kramo_is_catalog_screen() ) {
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
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'kramo_catalog' ),
				'loadingText'   => __( 'Ładowanie produktów…', 'kramo' ),
				'resultsText'   => __( 'Liczba produktów: %d', 'kramo' ),
				'noResultsText' => __( 'Brak produktów dla wybranych filtrów', 'kramo' ),
				'errorMessage'  => __( 'Nie udało się załadować produktów.', 'kramo' ),
				'densityKey'    => 'kramoCatalogDensity',
			)
		);
	}

	if ( kramo_has_header_utility() ) {
		wp_enqueue_script(
			'kramo-cart',
			$theme_uri . '/assets/js/cart.js',
			array(),
			kramo_asset_version( 'assets/js/cart.js' ),
			true
		);
		wp_script_add_data( 'kramo-cart', 'strategy', 'defer' );
		wp_localize_script(
			'kramo-cart',
			'kramoCart',
			array(
				'cookieName'   => KRAMO_CART_COOKIE,
				'addedMessage' => __( 'Dodano do koszyka', 'kramo' ),
			)
		);
	}

	// The search field only exists in the catalog toolbar, so its suggestions
	// script has nothing to bind to anywhere else.
	if ( kramo_has_search() && kramo_is_catalog_screen() ) {
		wp_enqueue_script(
			'kramo-search',
			$theme_uri . '/assets/js/search.js',
			array(),
			kramo_asset_version( 'assets/js/search.js' ),
			true
		);
		wp_script_add_data( 'kramo-search', 'strategy', 'defer' );
		wp_localize_script(
			'kramo-search',
			'kramoSearch',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'kramo_search' ),
				'noResultsText' => __( 'Brak wyników', 'kramo' ),
				'errorMessage'  => __( 'Nie udało się wyszukać produktów.', 'kramo' ),
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
	$base = get_stylesheet_directory_uri() . '/assets/fonts/';

	foreach ( array( 'InstrumentSans.woff2', 'BricolageGrotesque.woff2' ) as $file ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $base . $file )
		);
	}
}
add_action( 'wp_head', 'kramo_preload_primary_font', 1 );
