<?php
/**
 * Stock and dispatch signals on the product page.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the total managed stock of a product, including variations.
 *
 * @param WC_Product $product Product.
 * @return int|null Null when stock is not managed anywhere.
 */
function kramo_total_stock( $product ) {
	if ( $product->is_type( 'variable' ) ) {
		$total   = null;
		$manages = false;

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( ! $variation instanceof WC_Product_Variation || ! $variation->managing_stock() ) {
				continue;
			}

			$manages = true;
			$total   = (int) $total + (int) $variation->get_stock_quantity();
		}

		return $manages ? (int) $total : null;
	}

	return $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
}

/**
 * Describe availability as a state key, label and CSS modifier.
 *
 * @param WC_Product $product Product.
 * @return array{state:string,label:string}
 */
function kramo_availability_status( $product ) {
	if ( ! $product->is_in_stock() ) {
		return array(
			'state' => 'out',
			'label' => __( 'Chwilowo niedostępny', 'kramo' ),
		);
	}

	if ( $product->is_on_backorder( 1 ) ) {
		return array(
			'state' => 'backorder',
			'label' => __( 'Na zamówienie', 'kramo' ),
		);
	}

	$stock = kramo_total_stock( $product );
	$low   = (int) apply_filters( 'kramo_low_stock_amount', wc_get_low_stock_amount( $product ), $product );

	if ( null !== $stock && $low > 0 && $stock <= $low ) {
		// "szt." does not decline, so the label stays correct for every numeral
		// without shipping a Polish plural-forms catalogue.
		$label = 1 === $stock
			? __( 'Ostatnia sztuka', 'kramo' )
			: sprintf(
				/* translators: %d: remaining stock quantity. */
				__( 'Zostało %d szt.', 'kramo' ),
				$stock
			);

		return array(
			'state' => 'low',
			'label' => $label,
		);
	}

	return array(
		'state' => 'in',
		'label' => __( 'Dostępny', 'kramo' ),
	);
}

/**
 * Return the dispatch promise, or an empty string when nothing can ship.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function kramo_dispatch_promise( $product ) {
	if ( ! $product->is_in_stock() ) {
		return '';
	}

	return (string) apply_filters(
		'kramo_dispatch_promise',
		__( 'Wysyłka w 24 h', 'kramo' ),
		$product
	);
}

/**
 * Print stock and dispatch signals under the price.
 */
function kramo_render_availability() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$status   = kramo_availability_status( $product );
	$dispatch = kramo_dispatch_promise( $product );

	echo '<ul class="kramo-availability">';

	printf(
		'<li class="kramo-availability__item kramo-availability__item--%1$s"><span class="kramo-availability__dot" aria-hidden="true"></span>%2$s</li>',
		esc_attr( $status['state'] ),
		esc_html( $status['label'] )
	);

	if ( '' !== $dispatch ) {
		printf(
			'<li class="kramo-availability__item kramo-availability__item--dispatch">%s</li>',
			esc_html( $dispatch )
		);
	}

	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'kramo_render_availability', 15 );
