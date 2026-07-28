<?php
/**
 * Lightweight guest and customer wishlists.
 *
 * @package Kramo
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
function kramo_validate_wishlist_ids( $ids ) {
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
function kramo_get_customer_wishlist( $user_id ) {
	return kramo_validate_wishlist_ids(
		(array) get_user_meta( $user_id, '_kramo_wishlist', true )
	);
}

/**
 * Render an accessible wishlist toggle.
 *
 * @param int    $product_id Product ID.
 * @param string $context    Card or single context.
 */
function kramo_wishlist_button( $product_id, $context = 'card' ) {
	$is_saved = is_user_logged_in()
		&& in_array( $product_id, kramo_get_customer_wishlist( get_current_user_id() ), true );
	$label    = $is_saved
		? __( 'Usuń z ulubionych', 'kramo' )
		: __( 'Dodaj do ulubionych', 'kramo' );
	?>
	<button
		type="button"
		class="kramo-wishlist-toggle kramo-wishlist-toggle--<?php echo esc_attr( $context ); ?>"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
	>
		<span class="kramo-wishlist-toggle__icon" aria-hidden="true"></span>
		<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
	</button>
	<?php
}

/**
 * Add wishlist control to catalog cards.
 */
function kramo_loop_wishlist_button() {
	global $product;

	if ( $product instanceof WC_Product ) {
		kramo_wishlist_button( $product->get_id(), 'card' );
	}
}
add_action( 'woocommerce_before_shop_loop_item', 'kramo_loop_wishlist_button', 5 );

/**
 * Add wishlist control to the product summary.
 */
function kramo_single_wishlist_button() {
	global $product;

	if ( $product instanceof WC_Product ) {
		kramo_wishlist_button( $product->get_id(), 'single' );
	}
}
add_action( 'woocommerce_single_product_summary', 'kramo_single_wishlist_button', 35 );

/**
 * Return the wishlist page ID, or 0 when the page is missing.
 *
 * @return int
 */
function kramo_wishlist_page_id() {
	$page = get_page_by_path( 'ulubione' );

	return $page instanceof WP_Post ? (int) $page->ID : 0;
}

/**
 * Append a live counter to menu links pointing at the wishlist page.
 *
 * @param string $title Menu item title.
 * @param object $item  Menu item.
 * @return string
 */
function kramo_wishlist_menu_counter( $title, $item ) {
	$page_id = kramo_wishlist_page_id();

	if ( ! $page_id || ! isset( $item->object_id ) || (int) $item->object_id !== $page_id ) {
		return $title;
	}

	if ( 'post_type' !== $item->type || 'page' !== $item->object ) {
		return $title;
	}

	return $title . ' <span class="kramo-wishlist-count" data-wishlist-count hidden></span>';
}
add_filter( 'nav_menu_item_title', 'kramo_wishlist_menu_counter', 10, 2 );

/**
 * Persist a logged-in customer's merged wishlist.
 */
function kramo_ajax_update_wishlist() {
	check_ajax_referer( 'kramo_wishlist', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Zaloguj się, aby zapisać listę.', 'kramo' ) ), 401 );
	}

	$ids = isset( $_POST['product_ids'] )
		? kramo_validate_wishlist_ids( wp_unslash( $_POST['product_ids'] ) )
		: array();

	update_user_meta( get_current_user_id(), '_kramo_wishlist', $ids );
	wp_send_json_success( array( 'productIds' => $ids ) );
}
add_action( 'wp_ajax_kramo_update_wishlist', 'kramo_ajax_update_wishlist' );

/**
 * Render wishlist products for localStorage-based guests.
 */
function kramo_ajax_render_wishlist() {
	check_ajax_referer( 'kramo_wishlist', 'nonce' );

	$ids = isset( $_REQUEST['product_ids'] )
		? kramo_validate_wishlist_ids( wp_unslash( $_REQUEST['product_ids'] ) )
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

	wp_send_json_success( array( 'html' => kramo_render_product_query( $query ) ) );
}
add_action( 'wp_ajax_kramo_render_wishlist', 'kramo_ajax_render_wishlist' );
add_action( 'wp_ajax_nopriv_kramo_render_wishlist', 'kramo_ajax_render_wishlist' );

/**
 * Render the Ulubione page shell.
 *
 * @return string
 */
function kramo_wishlist_shortcode() {
	ob_start();
	?>
	<section class="woocommerce kramo-wishlist" data-wishlist-page>
		<h1><?php echo esc_html__( 'Ulubione', 'kramo' ); ?></h1>
		<p class="kramo-wishlist__empty" data-wishlist-empty>
			<?php echo esc_html__( 'Nie masz jeszcze ulubionych produktów.', 'kramo' ); ?>
		</p>
		<p class="screen-reader-text" data-wishlist-status role="status" aria-live="polite"></p>
		<div class="kramo-wishlist__products" data-wishlist-products></div>
	</section>
	<?php

	return (string) ob_get_clean();
}
add_shortcode( 'kramo_wishlist', 'kramo_wishlist_shortcode' );

/**
 * Create the wishlist page on theme activation when it does not exist.
 */
function kramo_create_wishlist_page() {
	$page = get_page_by_path( 'ulubione' );
	if ( $page instanceof WP_Post ) {
		return;
	}

	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Ulubione', 'kramo' ),
			'post_name'    => 'ulubione',
			'post_content' => '[kramo_wishlist]',
		)
	);
}
add_action( 'after_switch_theme', 'kramo_create_wishlist_page' );
