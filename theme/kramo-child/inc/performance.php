<?php
/**
 * Front-end delivery tuning.
 *
 * Token, base and preset CSS are small and define layout plus colors, so they
 * are inlined as critical CSS and never cost a render-blocking request. The
 * larger WooCommerce component stylesheet stays blocking on store screens,
 * where deferring it would reflow the product grid and cost CLS, and is loaded
 * asynchronously everywhere else.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Style handles that are inlined instead of linked.
 *
 * @return array<int,string>
 */
function kramo_critical_style_handles() {
	return array( 'kramo-tokens', 'kramo-base', 'kramo-preset' );
}

/**
 * Whether the current screen needs WooCommerce component CSS to render
 * above-the-fold layout.
 *
 * @return bool
 */
function kramo_woocommerce_css_is_critical() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
}

/**
 * Read a theme asset from disk for inlining.
 *
 * @param string $relative_path Path relative to the child theme.
 * @return string
 */
function kramo_read_theme_asset( $relative_path ) {
	$path = realpath( get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' ) );
	$root = realpath( get_stylesheet_directory() );

	if ( ! $path || ! $root || 0 !== strpos( $path, $root ) || ! is_readable( $path ) ) {
		return '';
	}

	$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	return false === $contents ? '' : $contents;
}

/**
 * Rewrite relative url() references so inlined CSS keeps resolving assets.
 *
 * @param string $css           Stylesheet contents.
 * @param string $relative_path Original stylesheet path inside the theme.
 * @return string
 */
function kramo_rebase_css_urls( $css, $relative_path ) {
	$base = trailingslashit( get_stylesheet_directory_uri() . '/' . ltrim( dirname( $relative_path ), '/' ) );

	return preg_replace_callback(
		'#url\(\s*([\'"]?)(?!data:|https?:|//|/)([^\'")]+)\1\s*\)#i',
		static function ( $matches ) use ( $base ) {
			return 'url(' . $matches[1] . $base . $matches[2] . $matches[1] . ')';
		},
		$css
	);
}

/**
 * Inline the critical stylesheets and drop their link tags.
 */
function kramo_inline_critical_css() {
	$styles = wp_styles();
	if ( ! $styles ) {
		return;
	}

	$paths = array(
		'kramo-tokens'  => 'assets/css/tokens.css',
		'kramo-base'    => 'assets/css/base.css',
		'kramo-preset'  => kramo_get_active_preset_stylesheet(),
	);

	$inlined = '';
	foreach ( kramo_critical_style_handles() as $handle ) {
		if ( ! isset( $styles->registered[ $handle ] ) || empty( $paths[ $handle ] ) ) {
			continue;
		}

		$css = kramo_read_theme_asset( $paths[ $handle ] );
		if ( '' === $css ) {
			continue;
		}

		$inlined .= kramo_rebase_css_urls( $css, $paths[ $handle ] );
		$GLOBALS['kramo_inlined_handles'][] = $handle;
	}

	if ( '' === $inlined ) {
		return;
	}

	// The bundle rides on the first critical handle instead of a new one. A new
	// handle enqueued here would print after woo.css and invert the cascade:
	// the generic button rules in base.css would then outrank the component
	// rules in woo.css at equal specificity.
	wp_add_inline_style( 'kramo-tokens', $inlined );
}
add_action( 'wp_enqueue_scripts', 'kramo_inline_critical_css', 100 );

/**
 * Drop link tags for stylesheets whose contents were inlined.
 *
 * These handles stay registered because other stylesheets declare them as
 * dependencies; suppressing only the tag avoids shipping the same CSS twice.
 *
 * @param string $html   Link tag.
 * @param string $handle Style handle.
 * @return string
 */
function kramo_suppress_inlined_style_tags( $html, $handle ) {
	if ( is_admin() ) {
		return $html;
	}

	$inlined = isset( $GLOBALS['kramo_inlined_handles'] )
		? (array) $GLOBALS['kramo_inlined_handles']
		: array();

	return in_array( $handle, $inlined, true ) ? '' : $html;
}
add_filter( 'style_loader_tag', 'kramo_suppress_inlined_style_tags', 15, 2 );

/**
 * Load non-critical stylesheets without blocking rendering.
 *
 * @param string $html   Link tag.
 * @param string $handle Style handle.
 * @return string
 */
function kramo_async_noncritical_styles( $html, $handle ) {
	if ( is_admin() || 'kramo-woocommerce' !== $handle ) {
		return $html;
	}

	if ( kramo_woocommerce_css_is_critical() ) {
		return $html;
	}

	$async = str_replace(
		"rel='stylesheet'",
		"rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"",
		$html
	);

	return $async . '<noscript>' . $html . '</noscript>';
}
add_filter( 'style_loader_tag', 'kramo_async_noncritical_styles', 20, 2 );

/**
 * Mark the main product image as the LCP element.
 *
 * @param string $html         Image markup.
 * @param int    $thumbnail_id Attachment ID.
 * @return string
 */
function kramo_prioritize_product_image( $html, $thumbnail_id = 0 ) {
	unset( $thumbnail_id );

	static $done = false;
	if ( $done || is_admin() || false === strpos( $html, '<img' ) ) {
		return $html;
	}

	$done = true;
	$html = str_replace( ' loading="lazy"', '', $html );

	return str_replace( '<img', '<img fetchpriority="high" decoding="async"', $html );
}
add_filter( 'woocommerce_single_product_image_thumbnail_html', 'kramo_prioritize_product_image', 10, 2 );

/**
 * Keep explicit dimensions on content images so late layout does not shift.
 *
 * @param array<string,string> $attr Image attributes.
 * @return array<string,string>
 */
function kramo_image_dimension_attributes( $attr ) {
	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'kramo_image_dimension_attributes' );

/**
 * Allow the priority hints that kses would otherwise strip from img tags.
 *
 * Catalog markup passes through wp_kses_post(), whose allowed-attribute list
 * has no fetchpriority entry, so the LCP hint would be removed after being set.
 *
 * @param array<string,array<string,bool>> $tags    Allowed tags.
 * @param string                           $context Context name.
 * @return array<string,array<string,bool>>
 */
function kramo_allow_priority_attributes( $tags, $context ) {
	if ( 'post' !== $context || ! isset( $tags['img'] ) ) {
		return $tags;
	}

	$tags['img']['fetchpriority'] = true;
	$tags['img']['decoding']      = true;

	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'kramo_allow_priority_attributes', 10, 2 );

/**
 * Restrict payment-gateway assets to the screens that can take a payment.
 *
 * The PayPal SDK is an external request and the Przelewy24 bundle is dead
 * weight on catalog pages; both belong to cart and checkout only. Sites that
 * want express buttons on product pages can opt back in with the filter.
 */
function kramo_trim_payment_assets() {
	if ( is_admin() || ! function_exists( 'is_cart' ) ) {
		return;
	}

	$payment_screen = is_cart() || is_checkout() || is_account_page();
	if ( apply_filters( 'kramo_load_payment_assets', $payment_screen ) ) {
		return;
	}

	$styles = wp_styles();
	$scripts = wp_scripts();

	foreach ( array( $styles, $scripts ) as $collection ) {
		if ( ! $collection ) {
			continue;
		}

		foreach ( array_keys( $collection->registered ) as $handle ) {
			if ( preg_match( '/^(ppcp|p24)[-_]/', $handle ) || false !== strpos( $handle, 'paypal' ) ) {
				if ( $collection instanceof WP_Styles ) {
					wp_dequeue_style( $handle );
				} else {
					wp_dequeue_script( $handle );
				}
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'kramo_trim_payment_assets', 999 );

/**
 * Raise the threshold that makes WordPress lazy-load early images.
 *
 * The catalog renders four tiles above the fold on desktop.
 *
 * @return int
 */
function kramo_omit_loading_attr_threshold() {
	return 4;
}
add_filter( 'wp_omit_loading_attr_threshold', 'kramo_omit_loading_attr_threshold' );
