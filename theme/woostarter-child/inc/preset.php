<?php
/**
 * Preset loader foundation.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the sanitized active preset identifier.
 *
 * @return string
 */
function woostarter_get_active_preset() {
	$preset = get_theme_mod( 'woostarter_active_preset', '' );
	$preset = apply_filters( 'woostarter_active_preset', $preset );

	return sanitize_key( $preset );
}

/**
 * Return the active preset stylesheet when it exists.
 *
 * @return string
 */
function woostarter_get_active_preset_stylesheet() {
	$preset = woostarter_get_active_preset();
	if ( ! $preset ) {
		return '';
	}

	$relative_path = 'assets/css/presets/' . $preset . '.css';
	$absolute_path = get_stylesheet_directory() . '/' . $relative_path;

	return file_exists( $absolute_path ) ? $relative_path : '';
}
