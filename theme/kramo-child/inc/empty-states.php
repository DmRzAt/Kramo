<?php
/**
 * Designed empty states for the filtered catalog and the cart.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return an inline icon used by the empty states.
 *
 * @param string $name Icon key.
 * @return string
 */
function kramo_empty_state_icon( $name ) {
	$icons = array(
		'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
		'cart'   => '<path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.4a2 2 0 0 0 2-1.55L20.5 8H6"/><circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>',
	);

	if ( empty( $icons[ $name ] ) ) {
		return '';
	}

	return '<svg class="kramo-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $icons[ $name ] . '</svg>';
}

/**
 * Build one empty-state block.
 *
 * @param string $icon    Icon key.
 * @param string $title   Heading.
 * @param string $text    Supporting copy.
 * @param array  $actions List of label and url pairs.
 * @return string
 */
function kramo_empty_state_markup( $icon, $title, $text, $actions = array() ) {
	$buttons = '';

	foreach ( $actions as $action ) {
		$buttons .= sprintf(
			'<a class="button" href="%1$s">%2$s</a>',
			esc_url( $action['url'] ),
			esc_html( $action['label'] )
		);
	}

	return sprintf(
		'<div class="kramo-empty">%1$s<p class="kramo-empty__title">%2$s</p><p class="kramo-empty__text">%3$s</p>%4$s</div>',
		kramo_empty_state_icon( $icon ),
		esc_html( $title ),
		esc_html( $text ),
		$buttons ? '<div class="kramo-empty__actions">' . $buttons . '</div>' : ''
	);
}

/**
 * Print the designed replacement for the stock "no products found" notice.
 */
function kramo_no_products_found_markup() {
	$filters = kramo_get_catalog_filters();
	$active  = array_filter(
		array(
			$filters['category'],
			$filters['color'],
			$filters['size'],
			$filters['min_price'],
			$filters['max_price'],
		),
		static function ( $value ) {
			return '' !== $value;
		}
	);

	$actions = array();

	if ( $active ) {
		$actions[] = array(
			'label' => __( 'Wyczyść filtry', 'kramo' ),
			'url'   => wc_get_page_permalink( 'shop' ),
		);
	}

	echo kramo_empty_state_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'search',
		$active
			? __( 'Nic nie pasuje do tych filtrów', 'kramo' )
			: __( 'Nie ma tu jeszcze produktów', 'kramo' ),
		$active
			? __( 'Spróbuj poszerzyć zakres ceny albo wybrać inny kolor lub rozmiar.', 'kramo' )
			: __( 'Zajrzyj za chwilę — asortyment jest w drodze.', 'kramo' ),
		$actions
	);
}

/**
 * Replace the stock empty-cart block with a designed one.
 */
function kramo_empty_cart_markup() {
	$actions = array(
		array(
			'label' => __( 'Wróć do sklepu', 'kramo' ),
			'url'   => wc_get_page_permalink( 'shop' ),
		),
	);

	$wishlist_id = function_exists( 'kramo_wishlist_page_id' ) ? kramo_wishlist_page_id() : 0;

	if ( $wishlist_id ) {
		$actions[] = array(
			'label' => __( 'Zobacz ulubione', 'kramo' ),
			'url'   => get_permalink( $wishlist_id ),
		);
	}

	echo kramo_empty_state_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'cart',
		__( 'Koszyk jest pusty', 'kramo' ),
		__( 'Dodaj produkty, a wrócimy do nich razem. Darmowa dostawa liczy się od progu podanego pod nagłówkiem.', 'kramo' ),
		$actions
	);
}

/**
 * Swap the stock WooCommerce empty states for the designed blocks.
 *
 * Both defaults are registered in wc-template-hooks.php, so they are removed
 * by the same name and priority WooCommerce used to add them.
 */
function kramo_replace_empty_states() {
	remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
	add_action( 'woocommerce_cart_is_empty', 'kramo_empty_cart_markup', 10 );

	remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
	add_action( 'woocommerce_no_products_found', 'kramo_no_products_found_markup', 10 );
}
add_action( 'wp_loaded', 'kramo_replace_empty_states' );
