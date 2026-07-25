<?php

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not loaded.' );
}

if ( ! function_exists( 'kramo_payment_mode' ) ) {
	WP_CLI::error( 'Kramo payment configuration is not loaded.' );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Print a check result and remember failures.
 *
 * @param bool          $condition Test condition.
 * @param string        $label     Test label.
 * @param array<string> $failures  Failure list.
 */
function kramo_payment_check( $condition, $label, &$failures ) {
	if ( $condition ) {
		WP_CLI::log( '[PASS] ' . $label );
		return;
	}

	$failures[] = $label;
	WP_CLI::log( '[FAIL] ' . $label );
}

/**
 * Read an option directly from the database, bypassing option filters.
 *
 * @param string $option_name Option name.
 * @return mixed
 */
function kramo_raw_option( $option_name ) {
	global $wpdb;

	$value = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
			$option_name
		)
	);

	return null === $value ? null : maybe_unserialize( $value );
}

$failures = array();
$mode     = kramo_payment_mode();

kramo_payment_check(
	in_array( $mode, array( 'sandbox', 'live' ), true ),
	'Payment mode is restricted to sandbox or live',
	$failures
);

kramo_payment_check(
	is_plugin_active( 'woo-przelewy24/woocommerce-p24-gateway.php' ),
	'Official Przelewy24 plugin is active',
	$failures
);

kramo_payment_check(
	is_plugin_active( 'woocommerce-paypal-payments/woocommerce-paypal-payments.php' ),
	'Official WooCommerce PayPal Payments plugin is active',
	$failures
);

kramo_payment_check(
	kramo_payment_provider_is_configured( 'p24' ),
	'Przelewy24 has a complete selected-environment configuration',
	$failures
);
kramo_payment_check(
	kramo_payment_provider_is_configured( 'paypal' ),
	'PayPal has a complete selected-environment configuration',
	$failures
);

$expected_p24_mode = 'live' === $mode ? 'production' : 'sandbox';
kramo_payment_check(
	$expected_p24_mode === get_option( 'p24_mode' ),
	'Przelewy24 mode follows KRAMO_PAYMENT_MODE',
	$failures
);

foreach ( array( 'merchant_id', 'crc_key', 'reports_key' ) as $key ) {
	$option_name = 'p24_' . $key;
	kramo_payment_check(
		kramo_payment_config_value( 'p24', $key ) === (string) get_option( $option_name ),
		'Przelewy24 ' . $key . ' is read from the selected environment',
		$failures
	);
}

$p24_config = \WC_P24\Config::get_instance();
kramo_payment_check(
	( 'live' === $mode ) === $p24_config->is_live()
		&& (int) kramo_payment_config_value( 'p24', 'merchant_id' ) === $p24_config->get_merchant_id()
		&& kramo_payment_config_value( 'p24', 'crc_key' ) === $p24_config->get_crc_key()
		&& kramo_payment_config_value( 'p24', 'reports_key' ) === $p24_config->get_reports_key(),
	'Przelewy24 runtime client uses the selected environment',
	$failures
);

$paypal = get_option( 'woocommerce-ppcp-data-common', array() );
kramo_payment_check(
	( 'sandbox' === $mode ) === (bool) ( $paypal['sandbox_merchant'] ?? false ),
	'PayPal environment follows KRAMO_PAYMENT_MODE',
	$failures
);
kramo_payment_check(
	kramo_payment_config_value( 'paypal', 'client_id' ) === ( $paypal['client_id'] ?? '' )
		&& kramo_payment_config_value( 'paypal', 'client_secret' ) === ( $paypal['client_secret'] ?? '' ),
	'PayPal client credentials are read from the selected environment',
	$failures
);

$paypal_general = \WooCommerce\PayPalCommerce\PPCP::container()->get(
	'settings.data.general'
);
$paypal_runtime = $paypal_general->get_merchant_data();
kramo_payment_check(
	( 'sandbox' === $mode ) === $paypal_runtime->is_sandbox
		&& kramo_payment_config_value( 'paypal', 'client_id' ) === $paypal_runtime->client_id
		&& kramo_payment_config_value( 'paypal', 'client_secret' ) === $paypal_runtime->client_secret,
	'PayPal runtime client uses the selected environment',
	$failures
);

$filtered_p24_write = apply_filters(
	'pre_update_option_p24_crc_key',
	'must-not-reach-database',
	'',
	'p24_crc_key'
);
kramo_payment_check(
	'' === $filtered_p24_write,
	'Przelewy24 secret writes are blocked',
	$failures
);

$filtered_paypal_write = apply_filters(
	'pre_update_option_woocommerce-ppcp-data-common',
	array(
		'client_id'     => 'must-not-reach-database',
		'client_secret' => 'must-not-reach-database',
	),
	array(),
	'woocommerce-ppcp-data-common'
);
kramo_payment_check(
	empty( $filtered_paypal_write['client_id'] )
		&& empty( $filtered_paypal_write['client_secret'] ),
	'PayPal secret writes are blocked',
	$failures
);

$raw_p24_crc = kramo_raw_option( 'p24_crc_key' );
$raw_paypal  = kramo_raw_option( 'woocommerce-ppcp-data-common' );
kramo_payment_check(
	empty( $raw_p24_crc ),
	'No Przelewy24 CRC key is stored in wp_options',
	$failures
);
kramo_payment_check(
	! is_array( $raw_paypal )
		|| (
			empty( $raw_paypal['client_id'] )
			&& empty( $raw_paypal['client_secret'] )
		),
	'No PayPal client credentials are stored in wp_options',
	$failures
);

$routes = rest_get_server()->get_routes();
kramo_payment_check(
	isset( $routes['/paypal/v1/incoming'] ),
	'PayPal incoming webhook route is registered',
	$failures
);

$callback_urls = kramo_payment_callback_urls();
kramo_payment_check(
	false !== strpos( $callback_urls['p24'], 'wc-api=przelewy24' ),
	'Przelewy24 callback URL is available',
	$failures
);

if ( $failures ) {
	WP_CLI::error( sprintf( '%d payment configuration check(s) failed.', count( $failures ) ) );
}

WP_CLI::success(
	'Payment configuration checks passed; external transactions still require real sandbox credentials.'
);
