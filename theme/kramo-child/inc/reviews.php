<?php
/**
 * Verified, rating-required WooCommerce reviews.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require a star rating on product reviews.
 *
 * @return string
 */
function kramo_require_review_rating() {
	return 'yes';
}
add_filter( 'pre_option_woocommerce_review_rating_required', 'kramo_require_review_rating' );

/**
 * Require reviews to come from verified buyers.
 *
 * @return string
 */
function kramo_require_verified_reviews_option() {
	return 'yes';
}
add_filter( 'pre_option_woocommerce_review_rating_verification_required', 'kramo_require_verified_reviews_option' );

/**
 * Require verification in WooCommerce review validation.
 *
 * @return bool
 */
function kramo_require_verified_reviews() {
	return true;
}
add_filter( 'woocommerce_review_verification_required', 'kramo_require_verified_reviews' );

/**
 * Replace WooCommerce's verified-owner label with the Polish storefront copy.
 *
 * @param string $translation Translated text.
 * @param string $text        Original text.
 * @param string $domain      Text domain.
 * @return string
 */
function kramo_verified_review_label( $translation, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'verified owner' === $text ) {
		return __( 'Zweryfikowany zakup', 'kramo' );
	}

	return $translation;
}
add_filter( 'gettext', 'kramo_verified_review_label', 10, 3 );
