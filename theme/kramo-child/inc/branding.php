<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed tags/attributes for the inlined storefront logo SVG.
 *
 * @return array<string, array<string, bool>>
 */
function kramo_logo_kses_allowed_html() {
	return array(
		'svg'   => array(
			'xmlns'      => true,
			'viewbox'    => true,
			'role'       => true,
			'aria-label' => true,
			'class'      => true,
		),
		'title' => array(),
		'g'     => array(
			'class'           => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
		),
		'path'  => array(
			'd'     => true,
			'class' => true,
		),
		'text'  => array(
			'class' => true,
			'x'     => true,
			'y'     => true,
			'fill'  => true,
		),
	);
}

function kramo_logo_markup() {
	$path = get_stylesheet_directory() . '/assets/img/logo.svg';

	if ( ! is_readable( $path ) ) {
		return '';
	}

	$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	return false === $svg ? '' : $svg;
}

function kramo_render_site_branding() {
	$svg = kramo_logo_markup();

	if ( '' === $svg ) {
		return;
	}

	printf(
		'<a class="kramo-branding" href="%s" rel="home" aria-label="%s">%s</a>',
		esc_url( home_url( '/' ) ),
		esc_attr( get_bloginfo( 'name' ) ),
		wp_kses( $svg, kramo_logo_kses_allowed_html() )
	);
}

function kramo_replace_site_title( $title ) {
	if ( is_admin() ) {
		return $title;
	}

	ob_start();
	kramo_render_site_branding();
	$branding = trim( ob_get_clean() );

	return '' !== $branding ? $branding : $title;
}
add_filter( 'generate_site_title_output', 'kramo_replace_site_title', 20 );

function kramo_hide_site_description( $output ) {
	return is_admin() ? $output : '';
}
add_filter( 'generate_site_description_output', 'kramo_hide_site_description', 20 );

function kramo_hide_front_page_title( $show ) {
	return is_front_page() ? false : $show;
}
add_filter( 'generate_show_title', 'kramo_hide_front_page_title' );
