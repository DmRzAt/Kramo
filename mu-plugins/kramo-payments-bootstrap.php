<?php
/**
 * Load payment environment overrides before regular plugins.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kramo_payments_module = WP_CONTENT_DIR
	. '/themes/kramo-child/inc/payments.php';

if ( file_exists( $kramo_payments_module ) ) {
	require_once $kramo_payments_module;
}
