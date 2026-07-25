<?php
/**
 * Front-end cleanup.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove unnecessary discovery links and emoji integrations.
 */
function kramo_cleanup_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
}
add_action( 'init', 'kramo_cleanup_head', 20 );

/**
 * Remove the emoji CDN from resource hints.
 *
 * @param array  $urls          Resource hint URLs.
 * @param string $relation_type Hint relationship.
 * @return array
 */
function kramo_remove_emoji_resource_hint( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/' );
		$urls      = array_diff( $urls, array( $emoji_url ) );
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'kramo_remove_emoji_resource_hint', 10, 2 );

/**
 * Remove jQuery Migrate on the public site.
 *
 * @param WP_Scripts $scripts Registered scripts.
 */
function kramo_remove_jquery_migrate( $scripts ) {
	if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
		return;
	}

	$jquery = $scripts->registered['jquery'];
	if ( $jquery->deps ) {
		$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
	}
}
add_action( 'wp_default_scripts', 'kramo_remove_jquery_migrate' );

/**
 * Remove scripts that are unnecessary for anonymous visitors.
 */
function kramo_cleanup_public_scripts() {
	wp_deregister_script( 'wp-embed' );

	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'kramo_cleanup_public_scripts', 100 );

/**
 * Disable comments on pages while preserving product reviews.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function kramo_disable_page_comments( $open, $post_id ) {
	return 'page' === get_post_type( $post_id ) ? false : $open;
}
add_filter( 'comments_open', 'kramo_disable_page_comments', 10, 2 );
add_filter( 'pings_open', 'kramo_disable_page_comments', 10, 2 );

/**
 * Hide any existing page comments.
 *
 * @param array $comments Page comments.
 * @param int   $post_id  Post ID.
 * @return array
 */
function kramo_hide_existing_page_comments( $comments, $post_id ) {
	return 'page' === get_post_type( $post_id ) ? array() : $comments;
}
add_filter( 'comments_array', 'kramo_hide_existing_page_comments', 10, 2 );
