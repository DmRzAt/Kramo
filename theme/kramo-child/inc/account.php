<?php
/**
 * Customer account entry points.
 *
 * The provisioning script creates the "Moje konto" page with the WooCommerce
 * shortcode and points woocommerce_myaccount_page_id at it, but the page was
 * only linked from the footer menu. That left the login and registration forms
 * effectively unreachable from the store, so the header now carries an account
 * link and the registration form is enabled by default.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permalink of the WooCommerce account page, empty when it is not configured.
 *
 * wc_get_page_permalink() falls back to the home page for a missing page, which
 * would render a link that silently goes nowhere useful, so the page id is
 * resolved directly instead.
 *
 * @return string
 */
function kramo_account_url() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return '';
	}

	$page_id = (int) wc_get_page_id( 'myaccount' );

	if ( $page_id < 1 ) {
		return '';
	}

	$permalink = get_permalink( $page_id );

	return is_string( $permalink ) ? $permalink : '';
}

/**
 * Whether the account link can be rendered.
 *
 * @return bool
 */
function kramo_has_account() {
	return '' !== kramo_account_url();
}

/**
 * Build the header account link.
 *
 * @return string
 */
function kramo_account_link_markup() {
	$url = kramo_account_url();

	if ( '' === $url ) {
		return '';
	}

	$label = is_user_logged_in()
		? __( 'Moje konto', 'kramo' )
		: __( 'Zaloguj się', 'kramo' );

	return sprintf(
		'<a class="kramo-account-link" href="%1$s">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>'
		. '</svg>'
		. '<span class="kramo-account-link__label">%2$s</span>'
		. '</a>',
		esc_url( $url ),
		esc_html( $label )
	);
}

/**
 * Print the header account link.
 */
function kramo_render_account_link() {
	echo kramo_account_link_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in kramo_account_link_markup().
}

/**
 * Offer registration on the account page unless the store explicitly opted out.
 *
 * WooCommerce ships this setting as "no", which hides the registration form and
 * leaves returning customers with a login box and no way to create an account.
 * Filtering the default keeps the admin toggle authoritative: once the option is
 * saved in the database, the store owner's choice wins.
 *
 * @return string
 */
function kramo_enable_account_registration() {
	return 'yes';
}
add_filter( 'default_option_woocommerce_enable_myaccount_registration', 'kramo_enable_account_registration' );
add_filter( 'default_option_woocommerce_enable_signup_and_login_from_checkout', 'kramo_enable_account_registration' );
add_filter( 'default_option_woocommerce_enable_checkout_login_reminder', 'kramo_enable_account_registration' );
