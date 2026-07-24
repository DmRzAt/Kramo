<?php
/**
 * Load payment environment overrides before regular plugins.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$woostarter_payments_module = WP_CONTENT_DIR
	. '/themes/woostarter-child/inc/payments.php';

if ( file_exists( $woostarter_payments_module ) ) {
	require_once $woostarter_payments_module;
}
