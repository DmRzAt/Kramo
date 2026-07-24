<?php
/**
 * Lightweight guest and customer wishlists.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return validated product IDs from arbitrary input.
 *
 * @param mixed $ids Candidate IDs.
 * @return array<int, int>
 */
function woostarter_validate_wishlist_ids( $ids ) {
	if ( ! is_array( $ids ) ) {
		return array();
	}

	$ids = array_slice( array_unique( array_filter( array_map( 'absint', $ids ) ) ), 0, 100 );

	return array_values(
		array_filter(
			$ids,
			static function ( $id ) {
				return 'product' === get_post_type( $id ) && 'publish' === get_post_status( $id );
			}
		)
	);
}

/**
 * Return a customer's stored wishlist.
 *
 * @param int $user_id Customer ID.
 * @return array<int, int>
 */
function woostarter_get_customer_wishlist( $user_id ) {
	return woostarter_validate_wishlist_ids(
		(array) get_user_meta( $user_id, '_woostarter_wishlist', true )
	);
}

/**
 * Render an accessible wishlist toggle.
 *
 * @param int    $product_id Product ID.
 * @param string $context    Card or single context.
 */
function woostarter_wishlist_button( $product_id, $context = 'card' ) {
	$is_saved = is_user_logged_in()
		&& in_array( $product_id, woostarter_get_customer_wishlist( get_current_user_id() ), true );
	$label    = $is_saved
		? __( 'Usuń z ulubionych', 'woostarter' )
		: __( 'Dodaj do ulubionych', 'woostarter' );
	?>
	<button
		type="button"
		class="woostarter-wishlist-toggle woostarter-wishlist-toggle--<?php echo esc_attr( $context ); ?>"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
	>
		<span aria-hidden="true">♡</span>
		<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
	</button>
	<?php
}

/**
 * Add wishlist control to catalog cards.
 */
function woostarter_loop_wishlist_button() {
	global $product;

	if ( $product instanceof WC_Product ) {
		woostarter_wishlist_button( $product->get_id(), 'card' );
	}
}
add_action( 'woocommerce_before_shop_loop_item', 'woostarter_loop_wishlist_button', 5 );

/**
 * Add wishlist control to the product summary.
 */
function woostarter_single_wishlist_button() {
	global $product;

	if ( $product instanceof WC_Product ) {
		woostarter_wishlist_button( $product->get_id(), 'single' );
	}
}
add_action( 'woocommerce_single_product_summary', 'woostarter_single_wishlist_button', 35 );

/**
 * Persist a logged-in customer's merged wishlist.
 */
function woostarter_ajax_update_wishlist() {
	check_ajax_referer( 'woostarter_wishlist', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Zaloguj się, aby zapisać listę.', 'woostarter' ) ), 401 );
	}

	$ids = isset( $_POST['product_ids'] )
		? woostarter_validate_wishlist_ids( wp_unslash( $_POST['product_ids'] ) )
		: array();

	update_user_meta( get_current_user_id(), '_woostarter_wishlist', $ids );
	wp_send_json_success( array( 'productIds' => $ids ) );
}
add_action( 'wp_ajax_woostarter_update_wishlist', 'woostarter_ajax_update_wishlist' );

/**
 * Render wishlist products for localStorage-based guests.
 */
function woostarter_ajax_render_wishlist() {
	check_ajax_referer( 'woostarter_wishlist', 'nonce' );

	$ids = isset( $_REQUEST['product_ids'] )
		? woostarter_validate_wishlist_ids( wp_unslash( $_REQUEST['product_ids'] ) )
		: array();

	if ( ! $ids ) {
		wp_send_json_success( array( 'html' => '' ) );
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'post__in'            => $ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => count( $ids ),
			'ignore_sticky_posts' => true,
		)
	);

	wp_send_json_success( array( 'html' => woostarter_render_product_query( $query ) ) );
}
add_action( 'wp_ajax_woostarter_render_wishlist', 'woostarter_ajax_render_wishlist' );
add_action( 'wp_ajax_nopriv_woostarter_render_wishlist', 'woostarter_ajax_render_wishlist' );

/**
 * Render the Ulubione page shell.
 *
 * @return string
 */
function woostarter_wishlist_shortcode() {
	ob_start();
	?>
	<section class="woocommerce woostarter-wishlist" data-wishlist-page>
		<h1><?php echo esc_html__( 'Ulubione', 'woostarter' ); ?></h1>
		<p class="woostarter-wishlist__empty" data-wishlist-empty>
			<?php echo esc_html__( 'Nie masz jeszcze ulubionych produktów.', 'woostarter' ); ?>
		</p>
		<div class="woostarter-wishlist__products" data-wishlist-products aria-live="polite"></div>
	</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'woostarter_wishlist', 'woostarter_wishlist_shortcode' );

/**
 * Create the wishlist page on theme activation when it does not exist.
 */
function woostarter_create_wishlist_page() {
	$page = get_page_by_path( 'ulubione' );
	if ( $page instanceof WP_Post ) {
		return;
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Ulubione', 'woostarter' ),
			'post_name'    => 'ulubione',
			'post_content' => '[woostarter_wishlist]',
		)
	);
}
add_action( 'after_switch_theme', 'woostarter_create_wishlist_page' );
