<?php

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not loaded.' );
}

if ( ! function_exists( 'kramo_get_personalization_settings' ) ) {
	WP_CLI::error( 'Kramo personalization module is not loaded.' );
}

/**
 * Print one test result and remember failures.
 *
 * @param bool          $condition Test condition.
 * @param string        $label     Test label.
 * @param array<string> $failures  Failure list.
 */
function kramo_personalization_check( $condition, $label, &$failures ) {
	if ( $condition ) {
		WP_CLI::log( '[PASS] ' . $label );
		return;
	}

	$failures[] = $label;
	WP_CLI::log( '[FAIL] ' . $label );
}

$product_id = wc_get_product_id_by_sku( 'DEMO-01' );
$product    = wc_get_product( $product_id );

if ( ! $product instanceof WC_Product_Variable ) {
	WP_CLI::error( 'Demo variable product DEMO-01 was not found.' );
}

$product->update_meta_data( '_ws_personalization_enabled', 'yes' );
$product->update_meta_data( '_ws_personalization_type', 'font' );
$product->update_meta_data( '_ws_personalization_label', 'Imię do haftu' );
$product->update_meta_data( '_ws_personalization_max_length', 20 );
$product->update_meta_data( '_ws_personalization_required', 'yes' );
$product->update_meta_data( '_ws_personalization_surcharge', 20 );
$product->save();

$variation_id = (int) current( $product->get_children() );
$variation    = wc_get_product( $variation_id );

if ( ! $variation instanceof WC_Product_Variation ) {
	WP_CLI::error( 'A demo variation was not found.' );
}

if ( ! WC()->session ) {
	WC()->session = new WC_Session_Handler();
	WC()->session->init();
}

if ( ! WC()->customer ) {
	WC()->customer = new WC_Customer( 0, true );
}

WC()->cart = new WC_Cart();

$failures = array();
$order    = false;

try {
	$variation_attributes = $variation->get_variation_attributes();
	$base_price           = (float) $variation->get_price();

	$_POST = array(
		'ws_personalization_text'   => 'Zażółć & <b>Gęślą</b>',
		'ws_personalization_choice' => 'classic',
	);
	$first_key = WC()->cart->add_to_cart(
		$product_id,
		1,
		$variation_id,
		$variation_attributes
	);

	$_POST = array(
		'ws_personalization_text'   => 'Anna',
		'ws_personalization_choice' => 'modern',
	);
	$second_key = WC()->cart->add_to_cart(
		$product_id,
		1,
		$variation_id,
		$variation_attributes
	);

	$cart          = WC()->cart->get_cart();
	$first_item    = $first_key && isset( $cart[ $first_key ] ) ? $cart[ $first_key ] : array();
	$display_data  = apply_filters( 'woocommerce_get_item_data', array(), $first_item );
	$display_texts = wp_list_pluck( $display_data, 'value', 'key' );

	kramo_personalization_check(
		isset( $display_texts['Imię do haftu'] )
			&& 'Zażółć & Gęślą' === $display_texts['Imię do haftu'],
		'1. Tekst personalizacji jest widoczny w koszyku',
		$failures
	);

	kramo_personalization_check(
		$first_key
			&& $second_key
			&& $first_key !== $second_key
			&& 2 === count( $cart ),
		'2. Różne teksty tworzą dwie osobne pozycje',
		$failures
	);

	WC()->cart->calculate_totals();
	$cart       = WC()->cart->get_cart();
	$first_item = $cart[ $first_key ];
	kramo_personalization_check(
		abs( (float) $first_item['data']->get_price() - ( $base_price + 20 ) ) < 0.001,
		'Dopłata personalizacji jest doliczona dokładnie raz',
		$failures
	);

	$order = wc_create_order(
		array(
			'status' => 'pending',
		)
	);
	$order->set_billing_first_name( 'Test' );
	$order->set_billing_last_name( 'Personalizacji' );
	$order->set_billing_email( 'test@example.com' );

	$order_item = new WC_Order_Item_Product();
	$order_item->set_props(
		array(
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'quantity'     => 1,
			'subtotal'     => $first_item['data']->get_price(),
			'total'        => $first_item['data']->get_price(),
		)
	);
	do_action(
		'woocommerce_checkout_create_order_line_item',
		$order_item,
		(string) $first_key,
		$first_item,
		$order
	);
	$order->add_item( $order_item );
	$order->calculate_totals();
	$order->save();

	$emails         = WC()->mailer()->get_emails();
	$customer_email = $emails['WC_Email_Customer_Processing_Order'];
	$customer_email->object = $order;
	$customer_html = $customer_email->get_content_html();

	kramo_personalization_check(
		false !== strpos( $customer_html, 'Zażółć' )
			&& false !== strpos( $customer_html, 'Gęślą' ),
		'3. Tekst jest w HTML wiadomości klienta',
		$failures
	);

	$admin_email = $emails['WC_Email_New_Order'];
	$admin_email->object = $order;
	$admin_html = $admin_email->get_content_html();

	kramo_personalization_check(
		false !== strpos( $admin_html, 'Zażółć' )
			&& false !== strpos( $admin_html, 'Gęślą' ),
		'4. Tekst jest w HTML wiadomości administratora',
		$failures
	);

	$formatted_meta = $order_item->get_formatted_meta_data();
	$visible_meta   = array();
	foreach ( $formatted_meta as $meta ) {
		$visible_meta[ $meta->display_key ] = html_entity_decode(
			wp_strip_all_tags( $meta->display_value ),
			ENT_QUOTES | ENT_HTML5,
			get_bloginfo( 'charset' )
		);
	}

	kramo_personalization_check(
		isset( $visible_meta['Imię do haftu'] )
			&& 'Zażółć & Gęślą' === $visible_meta['Imię do haftu'],
		'5. Tekst jest widoczny w metadanych pozycji zamówienia',
		$failures
	);

	kramo_personalization_check(
		isset( $first_item['_ws_personalization_text'] )
			&& 'Zażółć & Gęślą' === $first_item['_ws_personalization_text'],
		'6. Polskie litery i znaki specjalne przechodzą sanitizację',
		$failures
	);

	kramo_personalization_check(
		'Zażółć g' === kramo_sanitize_personalization_text(
			'Zażółć gęślą jaźń',
			8
		),
		'Tekst wielobajtowy jest obcinany do ustawionego limitu znaków',
		$failures
	);

	wc_clear_notices();
	$_POST = array(
		'ws_personalization_text'   => '',
		'ws_personalization_choice' => 'classic',
	);
	$required_result = apply_filters(
		'woocommerce_add_to_cart_validation',
		true,
		$product_id,
		1,
		$variation_id
	);

	kramo_personalization_check(
		false === $required_result && wc_notice_count( 'error' ) > 0,
		'7. Puste pole obowiązkowe jest odrzucane przez serwer',
		$failures
	);

	$product->update_meta_data( '_ws_personalization_required', 'no' );
	$product->save();
	wc_clear_notices();
	$_POST = array(
		'ws_personalization_text'   => '',
		'ws_personalization_choice' => '',
	);
	$optional_result = apply_filters(
		'woocommerce_add_to_cart_validation',
		true,
		$product_id,
		1,
		$variation_id
	);

	kramo_personalization_check(
		true === $optional_result && 0 === wc_notice_count( 'error' ),
		'Pusta opcjonalna personalizacja nie blokuje dodania produktu',
		$failures
	);
} finally {
	$_POST = array();
	wc_clear_notices();
	WC()->cart->empty_cart();
	$product->update_meta_data( '_ws_personalization_required', 'yes' );
	$product->save();

	if ( $order instanceof WC_Order ) {
		$order->delete( true );
	}
}

if ( $failures ) {
	WP_CLI::error( sprintf( '%d personalization check(s) failed.', count( $failures ) ) );
}

WP_CLI::success( 'All seven personalization test cases passed.' );
