<?php
/**
 * Shipping rules and pickup-point visibility.
 *
 * Weight-based pricing, a free-shipping threshold and "locker cheaper than
 * courier" are handled here in code so the behaviour is not tied to a paid
 * table-rate plugin. The official InPost and Orlen Paczka plugins provide the
 * live parcel-point selection (Geowidget) and label printing once the client
 * adds their ShipX / Orlen credentials; this module makes sure the point those
 * plugins store is always shown in the admin order and in e-mails.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the shipping pricing configuration.
 *
 * Costs are expressed in the store currency. A "locker" profile covers InPost
 * Paczkomaty and Orlen Paczka; a "courier" profile covers door delivery.
 *
 * @return array<string,mixed>
 */
function kramo_shipping_config() {
	return apply_filters(
		'kramo_shipping_config',
		array(
			'free_threshold' => 200.0,
			'profiles'       => array(
				// Base covers the first `free_kg`; each started kg above adds `per_kg`.
				'locker'  => array(
					'base'   => 12.0,
					'free_kg' => 1.0,
					'per_kg' => 2.0,
				),
				'courier' => array(
					'base'   => 16.0,
					'free_kg' => 1.0,
					'per_kg' => 3.0,
				),
			),
		)
	);
}

/**
 * Classify a shipping rate as a locker or courier profile.
 *
 * Classification is by label keyword so it keeps working across the native
 * placeholder methods and the official InPost / Orlen methods.
 *
 * @param WC_Shipping_Rate $rate Shipping rate.
 * @return string|null 'locker', 'courier' or null when the rate is untouched.
 */
function kramo_classify_shipping_rate( $rate ) {
	if ( 'free_shipping' === $rate->get_method_id() ) {
		return null;
	}

	$label = function_exists( 'mb_strtolower' )
		? mb_strtolower( $rate->get_label() )
		: strtolower( $rate->get_label() );

	foreach ( array( 'paczkomat', 'orlen', 'punkt', 'locker' ) as $needle ) {
		if ( false !== strpos( $label, $needle ) ) {
			return 'locker';
		}
	}

	foreach ( array( 'kurier', 'courier', 'dpd' ) as $needle ) {
		if ( false !== strpos( $label, $needle ) ) {
			return 'courier';
		}
	}

	return null;
}

/**
 * Total shippable weight of a package.
 *
 * @param array<string,mixed> $package Shipping package.
 * @return float
 */
function kramo_package_weight( $package ) {
	$weight = 0.0;

	foreach ( $package['contents'] as $item ) {
		if ( empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
			continue;
		}
		if ( ! $item['data']->needs_shipping() ) {
			continue;
		}
		$weight += (float) $item['data']->get_weight() * (int) $item['quantity'];
	}

	return $weight;
}

/**
 * Cost for a profile at a given weight.
 *
 * @param array<string,float> $profile Profile settings.
 * @param float               $weight  Package weight in kg.
 * @return float
 */
function kramo_profile_cost( $profile, $weight ) {
	$extra_kg = max( 0, (int) ceil( $weight - $profile['free_kg'] ) );

	return round( $profile['base'] + ( $extra_kg * $profile['per_kg'] ), 2 );
}

/**
 * Apply weight-based pricing, the locker/courier gap and the free threshold.
 *
 * @param array<string,WC_Shipping_Rate> $rates   Available rates.
 * @param array<string,mixed>            $package Shipping package.
 * @return array<string,WC_Shipping_Rate>
 */
function kramo_adjust_package_rates( $rates, $package ) {
	$config = kramo_shipping_config();
	$weight = kramo_package_weight( $package );

	foreach ( $rates as $rate ) {
		$profile_key = kramo_classify_shipping_rate( $rate );
		if ( null === $profile_key ) {
			continue;
		}

		$cost = kramo_profile_cost( $config['profiles'][ $profile_key ], $weight );
		$rate->set_cost( $cost );

		// No shipping tax is configured for the demo; keep taxes consistent with the new cost.
		$taxes = array();
		foreach ( (array) $rate->get_taxes() as $key => $value ) {
			$taxes[ $key ] = 0.0;
		}
		$rate->set_taxes( $taxes );
	}

	// Above the threshold the native free_shipping rate appears; drop the paid
	// parcel methods so the customer is offered free delivery unambiguously.
	$subtotal = isset( $package['contents_cost'] ) ? (float) $package['contents_cost'] : 0.0;
	if ( $subtotal >= $config['free_threshold'] ) {
		$has_free = false;
		foreach ( $rates as $rate ) {
			if ( 'free_shipping' === $rate->get_method_id() ) {
				$has_free = true;
				break;
			}
		}

		if ( $has_free ) {
			foreach ( $rates as $key => $rate ) {
				if ( null !== kramo_classify_shipping_rate( $rate ) ) {
					unset( $rates[ $key ] );
				}
			}
		}
	}

	return $rates;
}
add_filter( 'woocommerce_package_rates', 'kramo_adjust_package_rates', 20, 2 );

/**
 * Meta keys that may hold the chosen pickup point across supported plugins.
 *
 * The client maps their exact key here after installing InPost / Orlen; the
 * defaults cover the common variants so the point shows up without config.
 *
 * @return array<int,string>
 */
function kramo_pickup_point_meta_keys() {
	return apply_filters(
		'kramo_pickup_point_meta_keys',
		array(
			'_kramo_shipping_point',      // Our normalized key.
			'_ws_shipping_point',         // Legacy woostarter key.
			'_inpost_point_id',        // InPost variants.
			'_inpost_paczkomat_id',
			'_paczkomat',
			'_orlen_point_id',         // Orlen Paczka variants.
			'_orlen_paczka_point',
		)
	);
}

/**
 * Return a human-readable pickup point for an order, if any was selected.
 *
 * @param WC_Order $order Order object.
 * @return string
 */
function kramo_get_order_pickup_point( $order ) {
	foreach ( kramo_pickup_point_meta_keys() as $key ) {
		$value = $order->get_meta( $key );
		if ( '' !== (string) $value ) {
			return sanitize_text_field( (string) $value );
		}
	}

	return '';
}

/**
 * Persist the chosen pickup point under a normalized key at checkout.
 *
 * Reads whatever the active parcel plugin posted (or our own field) so later
 * rendering is uniform. No-op when nothing was selected.
 *
 * @param WC_Order $order Order being created.
 */
function kramo_store_pickup_point( $order ) {
	if ( '' !== kramo_get_order_pickup_point( $order ) ) {
		return;
	}

	foreach ( array( 'kramo_shipping_point', 'ws_shipping_point', 'inpost_point', 'orlen_point' ) as $field ) {
		if ( ! empty( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$order->update_meta_data(
				'_kramo_shipping_point',
				sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);
			return;
		}
	}
}
add_action( 'woocommerce_checkout_create_order', 'kramo_store_pickup_point', 20 );

/**
 * Show the pickup point in the admin order screen.
 *
 * @param WC_Order $order Order object.
 */
function kramo_admin_pickup_point( $order ) {
	$point = kramo_get_order_pickup_point( $order );
	if ( '' === $point ) {
		return;
	}

	printf(
		'<p><strong>%s</strong> %s</p>',
		esc_html__( 'Punkt odbioru:', 'kramo' ),
		esc_html( $point )
	);
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'kramo_admin_pickup_point' );

/**
 * Show the pickup point in customer and admin e-mails and on the order page.
 *
 * @param WC_Order $order Order object.
 */
function kramo_email_pickup_point( $order ) {
	$point = kramo_get_order_pickup_point( $order );
	if ( '' === $point ) {
		return;
	}

	printf(
		'<p><strong>%s</strong> %s</p>',
		esc_html__( 'Punkt odbioru:', 'kramo' ),
		esc_html( $point )
	);
}
add_action( 'woocommerce_email_after_order_table', 'kramo_email_pickup_point', 15 );
add_action( 'woocommerce_order_details_after_order_table', 'kramo_email_pickup_point', 15 );
