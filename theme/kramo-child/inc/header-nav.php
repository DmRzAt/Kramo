<?php
/**
 * Header utilities inside the primary navigation.
 *
 * The account link and the cart used to live in a separate bordered strip under
 * the header, which left them stranded across a wide empty row. They now ride in
 * the primary menu next to "Sklep", so the header is a single row, and the strip
 * only comes back as a fallback for installs with no primary menu assigned yet.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the header utilities have anything to show.
 *
 * @return bool
 */
function kramo_has_header_utility() {
	return function_exists( 'wc_get_cart_url' ) || kramo_has_account();
}

/**
 * Append the account and cart controls to the primary menu.
 *
 * The product search is deliberately absent: it belongs to the catalog filter
 * toolbar, next to the category control, and a second field in the header only
 * competed with it.
 *
 * @param string   $items Menu item markup.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @return string
 */
function kramo_nav_utility_items( $items, $args ) {
	if ( ! isset( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	$controls = array(
		'account' => kramo_account_link_markup(),
		'cart'    => kramo_cart_link_markup(),
	);

	foreach ( $controls as $name => $markup ) {
		if ( '' === $markup ) {
			continue;
		}

		$items .= sprintf(
			'<li class="menu-item kramo-nav-utility kramo-nav-utility--%1$s">%2$s</li>',
			esc_attr( $name ),
			$markup
		);
	}

	return $items;
}
add_filter( 'wp_nav_menu_items', 'kramo_nav_utility_items', 10, 2 );

/**
 * Render the utility row for installs with no primary menu.
 *
 * Without a menu assigned to that location GeneratePress prints no list for the
 * filter above to extend, which would drop the cart and the account link from
 * the site entirely.
 */
function kramo_render_header_utility() {
	if ( has_nav_menu( 'primary' ) || ! kramo_has_header_utility() ) {
		return;
	}

	echo '<div class="kramo-utility"><div class="kramo-utility__inner">';

	kramo_render_account_link();
	kramo_render_cart_link();
	echo '</div></div>';
}
add_action( 'generate_after_header', 'kramo_render_header_utility', 4 );
