<?php
/**
 * Home lifestyle category tiles.
 *
 * Three editorial photo tiles under the product rails — the Rzeczy Same pattern
 * Polish craft shoppers recognise, without the stock WooCommerce category grid.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preferred home tile order and optional display labels.
 *
 * @return array<string, string> slug => label
 */
function kramo_home_tile_definitions() {
	return array(
		'akcesoria' => __( 'Akcesoria', 'kramo' ),
		'dom'       => __( 'Dom i wnętrze', 'kramo' ),
		'odziez'    => __( 'Odzież', 'kramo' ),
	);
}

/**
 * Resolve up to three product categories for the home tiles.
 *
 * @return array<int, array{term:WP_Term,label:string,url:string,image_id:int}>
 */
function kramo_home_tile_items() {
	$items = array();

	foreach ( kramo_home_tile_definitions() as $slug => $label ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );

		if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
			continue;
		}

		$url = get_term_link( $term );

		if ( ! is_string( $url ) || '' === $url ) {
			continue;
		}

		$items[] = array(
			'term'     => $term,
			'label'    => $label,
			'url'      => $url,
			'image_id' => kramo_home_tile_image_id( $term ),
		);
	}

	/**
	 * Filter the lifestyle tiles shown on the storefront home page.
	 *
	 * @param array<int, array{term:WP_Term,label:string,url:string,image_id:int}> $items Tiles.
	 */
	return apply_filters( 'kramo_home_tile_items', $items );
}

/**
 * Category thumbnail, or the first product image in that category as fallback.
 *
 * @param WP_Term $term Product category.
 * @return int Attachment ID or 0.
 */
function kramo_home_tile_image_id( WP_Term $term ) {
	$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );

	if ( $thumbnail_id > 0 ) {
		return $thumbnail_id;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => array( (int) $term->term_id ),
				),
			),
		)
	);

	if ( ! $query->posts ) {
		return 0;
	}

	$image_id = (int) get_post_thumbnail_id( (int) $query->posts[0] );

	return $image_id > 0 ? $image_id : 0;
}

/**
 * Render the home lifestyle tiles shortcode.
 *
 * @return string
 */
function kramo_home_tiles_shortcode() {
	$items = kramo_home_tile_items();

	if ( ! $items ) {
		return '';
	}

	ob_start();
	?>
	<section class="kramo-home-tiles" aria-label="<?php echo esc_attr__( 'Kategorie', 'kramo' ); ?>">
		<ul class="kramo-home-tiles__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="kramo-home-tiles__item">
					<a class="kramo-home-tiles__tile" href="<?php echo esc_url( $item['url'] ); ?>">
						<span class="kramo-home-tiles__media" aria-hidden="true">
							<?php
							if ( $item['image_id'] > 0 ) {
								echo wp_get_attachment_image(
									$item['image_id'],
									'large',
									false,
									array(
										'class'    => 'kramo-home-tiles__image',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'alt'      => '',
									)
								);
							}
							?>
						</span>
						<span class="kramo-home-tiles__label"><?php echo esc_html( $item['label'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'kramo_home_tiles', 'kramo_home_tiles_shortcode' );
