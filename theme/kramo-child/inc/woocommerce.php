<?php
/**
 * WooCommerce hooks.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace GeneratePress wrappers and remove its store sidebar.
 */
function kramo_configure_woocommerce_hooks() {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'generate_woocommerce_start', 10 );
	remove_action( 'woocommerce_after_main_content', 'generate_woocommerce_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	remove_action( 'woocommerce_sidebar', 'generate_construct_sidebars', 10 );

	add_action( 'woocommerce_before_main_content', 'kramo_woocommerce_wrapper_start', 10 );
	add_action( 'woocommerce_after_main_content', 'kramo_woocommerce_wrapper_end', 10 );
}
add_action( 'after_setup_theme', 'kramo_configure_woocommerce_hooks', 30 );

/**
 * Open the child-theme WooCommerce content wrapper.
 */
function kramo_woocommerce_wrapper_start() {
	echo '<div id="primary" class="content-area kramo-content-area">';
	echo '<main id="main" class="site-main kramo-main" role="main">';
}

/**
 * Close the child-theme WooCommerce content wrapper.
 */
function kramo_woocommerce_wrapper_end() {
	echo '</main>';
	echo '</div>';
}

/**
 * Force a no-sidebar layout on WooCommerce screens.
 *
 * @param string $layout GeneratePress sidebar layout.
 * @return string
 */
function kramo_woocommerce_sidebar_layout( $layout ) {
	if (
		function_exists( 'is_woocommerce' )
		&& ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() )
	) {
		return 'no-sidebar';
	}

	return $layout;
}
add_filter( 'generate_sidebar_layout', 'kramo_woocommerce_sidebar_layout' );

/**
 * Display twelve products per catalog page.
 *
 * @return int
 */
function kramo_products_per_page() {
	return 12;
}
add_filter( 'loop_shop_per_page', 'kramo_products_per_page', 20 );

/**
 * Display four related products in one row.
 *
 * @param array $args Related-products query arguments.
 * @return array
 */
function kramo_related_products_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'kramo_related_products_args' );

/**
 * Check whether the current request renders the product catalog.
 *
 * Product search results are a listing too: they share the flush grid, the
 * filter toolbar and the search field, so they must count as catalog screens.
 *
 * @return bool
 */
function kramo_is_catalog_screen() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	if ( is_shop() || is_product_taxonomy() ) {
		return true;
	}

	return is_search() && 'product' === get_query_var( 'post_type' );
}

/**
 * URL paths of the per-customer WooCommerce pages.
 *
 * Resolved from the page ids rather than from conditional tags, so callers that
 * run before the main query is set up - security headers, cache policy - can
 * still recognise cart, checkout and account requests.
 *
 * @return array<int,string>
 */
function kramo_dynamic_page_paths() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return array();
	}

	$paths = array();

	foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
		$page_id = (int) wc_get_page_id( $page );

		if ( $page_id < 1 ) {
			continue;
		}

		$path = wp_parse_url( (string) get_permalink( $page_id ), PHP_URL_PATH );

		if ( $path ) {
			$paths[] = untrailingslashit( $path );
		}
	}

	return array_values( array_unique( array_filter( $paths ) ) );
}

/**
 * Whether the current request targets one of the per-customer pages.
 *
 * @return bool
 */
function kramo_request_is_dynamic_page() {
	$request = isset( $_SERVER['REQUEST_URI'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
		: '';

	if ( '' === $request ) {
		return false;
	}

	$path = untrailingslashit( (string) wp_parse_url( $request, PHP_URL_PATH ) );

	foreach ( kramo_dynamic_page_paths() as $dynamic_path ) {
		if ( $path === $dynamic_path || 0 === strpos( $path . '/', $dynamic_path . '/' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check whether the current request contains WooCommerce content.
 *
 * @return bool
 */
function kramo_needs_woocommerce_assets() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	if (
		is_woocommerce()
		|| is_cart()
		|| is_checkout()
		|| is_account_page()
	) {
		return true;
	}

	if ( ! is_singular() ) {
		return false;
	}

	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$shortcodes = array(
		'products',
		'product',
		'product_page',
		'product_category',
		'product_categories',
		'kramo_wishlist',
		'woocommerce_cart',
		'woocommerce_checkout',
		'woocommerce_my_account',
	);

	foreach ( $shortcodes as $shortcode ) {
		if ( has_shortcode( $post->post_content, $shortcode ) ) {
			return true;
		}
	}

	return false !== strpos( $post->post_content, '<!-- wp:woocommerce/' );
}

/**
 * Avoid loading the complete WooCommerce front-end bundle on unrelated pages.
 */
function kramo_dequeue_unneeded_woocommerce_assets() {
	if ( kramo_needs_woocommerce_assets() ) {
		return;
	}

	$styles = array(
		'wc-blocks-style',
		'woocommerce-layout',
		'woocommerce-smallscreen',
		'woocommerce-general',
	);

	$scripts = array(
		'wc-jquery-blockui',
		'wc-add-to-cart',
		'wc-js-cookie',
		'woocommerce',
		'wc-cart-fragments',
		'sourcebuster-js',
		'wc-order-attribution',
	);

	foreach ( $styles as $style ) {
		wp_dequeue_style( $style );
	}

	foreach ( $scripts as $script ) {
		wp_dequeue_script( $script );
	}
}
add_action( 'wp_enqueue_scripts', 'kramo_dequeue_unneeded_woocommerce_assets', 999 );
add_action( 'wp_print_styles', 'kramo_dequeue_unneeded_woocommerce_assets', 999 );

/**
 * Suppress late WooCommerce stylesheet tags on unrelated pages.
 *
 * @param string $html   Stylesheet tag.
 * @param string $handle Registered style handle.
 * @return string
 */
function kramo_filter_unneeded_woocommerce_style_tag( $html, $handle ) {
	if ( kramo_needs_woocommerce_assets() ) {
		return $html;
	}

	$styles = array(
		'wc-blocks-style',
		'woocommerce-layout',
		'woocommerce-smallscreen',
		'woocommerce-general',
	);

	return in_array( $handle, $styles, true ) ? '' : $html;
}
add_filter( 'style_loader_tag', 'kramo_filter_unneeded_woocommerce_style_tag', 100, 2 );
