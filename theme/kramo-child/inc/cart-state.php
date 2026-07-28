<?php
/**
 * Cache-safe cart signal for the header badge and the recovery banner.
 *
 * Shop and marketing pages are served from a shared page cache, so the cart
 * count must never be rendered into HTML. It travels in a first-party cookie
 * that carries only an item count and a formatted total, and the badge plus
 * the "wróć do koszyka" banner are filled in on the client.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Name of the cart-state cookie.
 */
const KRAMO_CART_COOKIE = 'kramo_cart_state';

/**
 * Whether the current request may write cookies.
 *
 * @return bool
 */
function kramo_can_write_cart_cookie() {
	return ! headers_sent() && ! is_admin() && function_exists( 'WC' ) && ! wp_doing_cron();
}

/**
 * Write the cart count and total into a first-party cookie.
 *
 * The payload is intentionally limited to a count and a formatted total, so a
 * shared cache or a browser extension reading it learns nothing personal.
 */
function kramo_store_cart_state() {
	if ( ! kramo_can_write_cart_cookie() ) {
		return;
	}

	$cart = WC()->cart;

	if ( ! $cart instanceof WC_Cart ) {
		return;
	}

	$count = (int) $cart->get_cart_contents_count();

	if ( 0 === $count ) {
		kramo_clear_cart_state();

		return;
	}

	$total = html_entity_decode(
		wp_strip_all_tags( wc_price( $cart->get_cart_contents_total() ) ),
		ENT_QUOTES,
		'UTF-8'
	);

	$payload = wp_json_encode(
		array(
			'count' => $count,
			'total' => $total,
		)
	);

	if ( ! is_string( $payload ) ) {
		return;
	}

	kramo_set_cart_cookie( $payload, time() + WEEK_IN_SECONDS );
}
add_action( 'woocommerce_cart_updated', 'kramo_store_cart_state', 20 );
add_action( 'woocommerce_thankyou', 'kramo_clear_cart_state', 20 );

/**
 * Drop the cart-state cookie once the cart is empty or the order is placed.
 */
function kramo_clear_cart_state() {
	if ( ! kramo_can_write_cart_cookie() ) {
		return;
	}

	kramo_set_cart_cookie( '', time() - DAY_IN_SECONDS );
}

/**
 * Write the cart-state cookie with hardened attributes.
 *
 * The value must stay readable by the front-end script, so HttpOnly is off by
 * design. SameSite=Lax blocks cross-site reads and the Secure flag follows the
 * current scheme.
 *
 * @param string $value   Cookie payload.
 * @param int    $expires Expiry timestamp.
 */
function kramo_set_cart_cookie( $value, $expires ) {
	setcookie(
		KRAMO_CART_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);
}

/**
 * Build the header cart link. The badge stays empty until the client reads
 * the cookie, so the markup is identical for every cached visitor.
 *
 * @return string
 */
function kramo_cart_link_markup() {
	if ( ! function_exists( 'wc_get_cart_url' ) ) {
		return '';
	}

	return sprintf(
		'<a class="kramo-cart-link" href="%1$s" data-kramo-cart-link>'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.55L20.5 8H6"/>'
		. '<circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>'
		. '</svg>'
		. '<span class="kramo-cart-link__label">%2$s</span>'
		. '<span class="kramo-cart-count" data-kramo-cart-count hidden></span>'
		. '</a>',
		esc_url( wc_get_cart_url() ),
		esc_html__( 'Koszyk', 'kramo' )
	);
}

/**
 * Print the header cart link.
 */
function kramo_render_cart_link() {
	echo kramo_cart_link_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in kramo_cart_link_markup().
}

/**
 * Render the cart recovery banner shell.
 *
 * The banner stays hidden until the client confirms a cart exists, which keeps
 * it out of the cached HTML for visitors who have no cart at all.
 */
function kramo_render_cart_recovery() {
	if ( ! function_exists( 'wc_get_cart_url' ) || is_cart() || is_checkout() ) {
		return;
	}

	printf(
		'<div class="kramo-cart-recovery" data-kramo-cart-recovery hidden>'
		. '<span>%1$s</span>'
		. '<span class="kramo-cart-recovery__total" data-kramo-cart-total></span>'
		. '<a class="kramo-cart-recovery__link" href="%2$s">%3$s</a>'
		. '<button type="button" class="kramo-cart-recovery__dismiss" data-kramo-cart-dismiss aria-label="%4$s">&times;</button>'
		. '</div>',
		esc_html__( 'Masz produkty w koszyku', 'kramo' ),
		esc_url( wc_get_cart_url() ),
		esc_html__( 'Wróć do koszyka', 'kramo' ),
		esc_attr__( 'Zamknij powiadomienie', 'kramo' )
	);
}
add_action( 'wp_body_open', 'kramo_render_cart_recovery', 20 );

/**
 * Render the toast region used for cart and wishlist confirmations.
 */
function kramo_render_toast_region() {
	echo '<div class="kramo-toasts" data-kramo-toasts role="status" aria-live="polite"></div>';
}
add_action( 'wp_footer', 'kramo_render_toast_region', 5 );
