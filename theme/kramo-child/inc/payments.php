<?php
/**
 * Environment-backed payment gateway configuration.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'KRAMO_PAYMENT_MODE' ) ) {
	$kramo_environment_payment_mode = strtolower(
		trim( (string) getenv( 'KRAMO_PAYMENT_MODE' ) )
	);

	define(
		'KRAMO_PAYMENT_MODE',
		in_array( $kramo_environment_payment_mode, array( 'sandbox', 'live' ), true )
			? $kramo_environment_payment_mode
			: 'sandbox'
	);
}

/**
 * Return a validated payment mode.
 *
 * Invalid wp-config.php values fail closed to sandbox.
 *
 * @return string
 */
function kramo_payment_mode() {
	$mode = strtolower( (string) KRAMO_PAYMENT_MODE );

	return in_array( $mode, array( 'sandbox', 'live' ), true ) ? $mode : 'sandbox';
}

/**
 * Read a mode-specific payment value from wp-config.php or the environment.
 *
 * Example: KRAMO_P24_SANDBOX_CRC_KEY.
 *
 * @param string $provider Provider name.
 * @param string $key      Configuration key.
 * @return string
 */
function kramo_payment_config_value( $provider, $key ) {
	$name = sprintf(
		'KRAMO_%s_%s_%s',
		strtoupper( sanitize_key( $provider ) ),
		strtoupper( kramo_payment_mode() ),
		strtoupper( sanitize_key( $key ) )
	);

	if ( defined( $name ) ) {
		return trim( (string) constant( $name ) );
	}

	$value = getenv( $name );

	return false === $value ? '' : trim( (string) $value );
}

/**
 * Return whether all required provider values are configured.
 *
 * @param string $provider Provider name.
 * @return bool
 */
function kramo_payment_provider_is_configured( $provider ) {
	$required = array(
		'p24'    => array( 'merchant_id', 'crc_key', 'reports_key' ),
		'paypal' => array( 'client_id', 'client_secret', 'merchant_id', 'merchant_email' ),
	);

	if ( empty( $required[ $provider ] ) ) {
		return false;
	}

	foreach ( $required[ $provider ] as $key ) {
		if ( '' === kramo_payment_config_value( $provider, $key ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Override Przelewy24 settings before the plugin reads the database.
 *
 * @param mixed  $pre_option Existing short-circuit value.
 * @param string $option     Option name.
 * @return mixed
 */
function kramo_override_p24_option( $pre_option, $option ) {
	unset( $pre_option );

	$values = array(
		'p24_mode'        => 'live' === kramo_payment_mode() ? 'production' : 'sandbox',
		'p24_merchant_id' => kramo_payment_config_value( 'p24', 'merchant_id' ),
		'p24_crc_key'     => kramo_payment_config_value( 'p24', 'crc_key' ),
		'p24_reports_key' => kramo_payment_config_value( 'p24', 'reports_key' ),
	);

	return $values[ $option ] ?? false;
}

foreach ( array( 'p24_mode', 'p24_merchant_id', 'p24_crc_key', 'p24_reports_key' ) as $option ) {
	add_filter( 'pre_option_' . $option, 'kramo_override_p24_option', 10, 2 );
}

/**
 * Prevent Przelewy24 credentials from being persisted in wp_options.
 *
 * @param mixed  $value     New option value.
 * @param mixed  $old_value Previous option value.
 * @param string $option    Option name.
 * @return mixed
 */
function kramo_prevent_p24_database_credentials( $value, $old_value, $option ) {
	unset( $value, $old_value );

	if ( 'p24_mode' === $option ) {
		return 'live' === kramo_payment_mode() ? 'production' : 'sandbox';
	}

	return '';
}

foreach ( array( 'p24_mode', 'p24_merchant_id', 'p24_crc_key', 'p24_reports_key' ) as $option ) {
	add_filter(
		'pre_update_option_' . $option,
		'kramo_prevent_p24_database_credentials',
		10,
		3
	);
}

/**
 * Inject environment-backed PayPal connection data into the current settings.
 *
 * @param mixed $settings Stored or default settings.
 * @return array<string,mixed>
 */
function kramo_override_paypal_connection( $settings ) {
	$settings      = is_array( $settings ) ? $settings : array();
	$is_sandbox    = 'sandbox' === kramo_payment_mode();
	$client_id     = kramo_payment_config_value( 'paypal', 'client_id' );
	$client_secret = kramo_payment_config_value( 'paypal', 'client_secret' );
	$merchant_id   = kramo_payment_config_value( 'paypal', 'merchant_id' );
	$merchant_email = kramo_payment_config_value( 'paypal', 'merchant_email' );
	$country       = kramo_payment_config_value( 'paypal', 'merchant_country' ) ?: 'PL';
	$seller_type   = kramo_payment_config_value( 'paypal', 'seller_type' ) ?: 'unknown';
	$is_connected  = '' !== $client_id
		&& '' !== $client_secret
		&& '' !== $merchant_id
		&& '' !== $merchant_email;

	return array_merge(
		$settings,
		array(
			'use_sandbox'           => $is_sandbox,
			'use_manual_connection' => $is_connected,
			'merchant_connected'    => $is_connected,
			'sandbox_merchant'      => $is_sandbox,
			'merchant_id'           => $merchant_id,
			'merchant_email'        => $merchant_email,
			'merchant_country'      => $country,
			'client_id'             => $client_id,
			'client_secret'         => $client_secret,
			'seller_type'           => $seller_type,
		)
	);
}
add_filter(
	'option_woocommerce-ppcp-data-common',
	'kramo_override_paypal_connection',
	PHP_INT_MAX
);
add_filter(
	'default_option_woocommerce-ppcp-data-common',
	'kramo_override_paypal_connection',
	PHP_INT_MAX
);

/**
 * Keep the legacy PayPal settings reader aligned during plugin migrations.
 *
 * @param mixed $settings Legacy settings.
 * @return array<string,mixed>
 */
function kramo_override_legacy_paypal_connection( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();

	return array_merge(
		$settings,
		array(
			'sandbox_on'    => 'sandbox' === kramo_payment_mode(),
			'client_id'     => kramo_payment_config_value( 'paypal', 'client_id' ),
			'client_secret' => kramo_payment_config_value( 'paypal', 'client_secret' ),
			'merchant_id'   => kramo_payment_config_value( 'paypal', 'merchant_id' ),
			'merchant_email' => kramo_payment_config_value( 'paypal', 'merchant_email' ),
		)
	);
}
add_filter(
	'option_woocommerce-ppcp-settings',
	'kramo_override_legacy_paypal_connection',
	PHP_INT_MAX
);
add_filter(
	'default_option_woocommerce-ppcp-settings',
	'kramo_override_legacy_paypal_connection',
	PHP_INT_MAX
);

/**
 * Strip PayPal connection data before settings are written to wp_options.
 *
 * @param mixed $settings New option value.
 * @return array<string,mixed>
 */
function kramo_strip_paypal_database_credentials( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();

	foreach (
		array(
			'merchant_connected',
			'sandbox_merchant',
			'merchant_id',
			'merchant_email',
			'merchant_country',
			'client_id',
			'client_secret',
			'seller_type',
		) as $key
	) {
		unset( $settings[ $key ] );
	}

	return $settings;
}
add_filter(
	'pre_update_option_woocommerce-ppcp-data-common',
	'kramo_strip_paypal_database_credentials'
);
add_filter(
	'pre_update_option_woocommerce-ppcp-settings',
	'kramo_strip_paypal_database_credentials'
);

/**
 * Return public callback endpoints used by the official plugins.
 *
 * @return array<string,string>
 */
function kramo_payment_callback_urls() {
	return array(
		'p24'    => home_url( '/?wc-api=przelewy24' ),
		'paypal' => rest_url( 'paypal/v1/incoming' ),
	);
}

/**
 * Warn administrators when the active payment mode lacks credentials.
 */
function kramo_payment_configuration_notice() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$missing = array();
	if ( ! kramo_payment_provider_is_configured( 'p24' ) ) {
		$missing[] = 'Przelewy24';
	}
	if ( ! kramo_payment_provider_is_configured( 'paypal' ) ) {
		$missing[] = 'PayPal';
	}

	if ( ! $missing ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: payment mode, 2: provider names. */
				__(
					'Kramo: tryb płatności „%1$s” nie ma pełnej konfiguracji dla: %2$s. Uzupełnij zmienne opisane w docs/platnosci.md.',
					'kramo'
				),
				kramo_payment_mode(),
				implode( ', ', $missing )
			)
		)
	);
}
add_action( 'admin_notices', 'kramo_payment_configuration_notice' );
