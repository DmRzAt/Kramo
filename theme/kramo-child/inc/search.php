<?php
/**
 * Header product search with AJAX suggestions.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maximum suggestions returned for one query.
 *
 * @return int
 */
function kramo_search_suggestion_limit() {
	return (int) apply_filters( 'kramo_search_suggestion_limit', 6 );
}

/**
 * Whether the product search can be rendered at all.
 *
 * @return bool
 */
function kramo_has_search() {
	return function_exists( 'wc_get_page_permalink' );
}

/**
 * Render the product search field.
 *
 * The catalog toolbar is its only home: one field above the category control,
 * where searching and narrowing happen in the same place.
 */
function kramo_render_search_form() {
	if ( ! kramo_has_search() ) {
		return;
	}

	// Searching from a filtered catalog keeps the filters, so the two controls in
	// the toolbar narrow the same result set instead of resetting each other.
	$filters = kramo_get_catalog_filters();
	$carried = array(
		'kramo_category'  => $filters['category'],
		'kramo_color'     => $filters['color'],
		'kramo_size'      => $filters['size'],
		'kramo_min_price' => $filters['min_price'],
		'kramo_max_price' => $filters['max_price'],
	);
	?>
	<div class="kramo-search" data-kramo-search>
		<form class="kramo-search__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="product">
			<?php foreach ( $carried as $carried_name => $carried_value ) : ?>
				<?php if ( '' !== $carried_value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $carried_name ); ?>" value="<?php echo esc_attr( $carried_value ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<label class="screen-reader-text" for="kramo-search-input">
				<?php echo esc_html__( 'Szukaj produktów', 'kramo' ); ?>
			</label>
			<input
				type="search"
				id="kramo-search-input"
				class="kramo-search__input"
				name="s"
				value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="<?php echo esc_attr__( 'Szukaj produktów…', 'kramo' ); ?>"
				autocomplete="off"
				role="combobox"
				aria-expanded="false"
				aria-controls="kramo-search-panel"
				aria-autocomplete="list"
			>
			<button type="submit"><?php echo esc_html__( 'Szukaj', 'kramo' ); ?></button>
		</form>
		<ul class="kramo-search__panel" id="kramo-search-panel" role="listbox" data-kramo-search-panel hidden></ul>
	</div>
	<?php
}

/**
 * Return product suggestions for a search term.
 */
function kramo_ajax_search_suggest() {
	check_ajax_referer( 'kramo_search', 'nonce' );

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
	$term = trim( mb_substr( $term, 0, 60 ) );

	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success( array( 'items' => array() ) );
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			's'                   => $term,
			'posts_per_page'      => kramo_search_suggestion_limit(),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'tax_query'           => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-search' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);

	$items = array();

	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post->ID );

		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$items[] = array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'url'       => get_permalink( $product->get_id() ),
			'price'     => kramo_plain_price( $product ),
			'thumbnail' => get_the_post_thumbnail_url( $product->get_id(), 'woocommerce_gallery_thumbnail' ),
		);
	}

	wp_reset_postdata();

	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_kramo_search_suggest', 'kramo_ajax_search_suggest' );
add_action( 'wp_ajax_nopriv_kramo_search_suggest', 'kramo_ajax_search_suggest' );
