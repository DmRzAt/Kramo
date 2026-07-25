<?php
/**
 * Shareable catalog filters and AJAX results.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a sanitized catalog-filter state.
 *
 * @param array $source Request data.
 * @return array<string, mixed>
 */
function kramo_get_catalog_filters( $source = array() ) {
	$source = $source ?: $_GET;

	return array(
		'category'  => isset( $source['ws_category'] ) ? sanitize_title( wp_unslash( $source['ws_category'] ) ) : '',
		'min_price' => isset( $source['ws_min_price'] ) ? wc_format_decimal( wp_unslash( $source['ws_min_price'] ) ) : '',
		'max_price' => isset( $source['ws_max_price'] ) ? wc_format_decimal( wp_unslash( $source['ws_max_price'] ) ) : '',
		'color'     => isset( $source['ws_color'] ) ? sanitize_title( wp_unslash( $source['ws_color'] ) ) : '',
		'size'      => isset( $source['ws_size'] ) ? sanitize_title( wp_unslash( $source['ws_size'] ) ) : '',
		'paged'     => isset( $source['paged'] ) ? max( 1, absint( $source['paged'] ) ) : 1,
	);
}

/**
 * Add the active filters to query arguments.
 *
 * @param array $args    WP_Query arguments.
 * @param array $filters Sanitized filter state.
 * @return array
 */
function kramo_apply_catalog_filters_to_args( $args, $filters ) {
	$tax_query  = isset( $args['tax_query'] ) && is_array( $args['tax_query'] ) ? $args['tax_query'] : array();
	$meta_query = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();

	if ( $filters['category'] ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $filters['category'],
		);
	}

	foreach (
		array(
			'color' => 'pa_kolor',
			'size'  => 'pa_rozmiar',
		) as $filter_key => $taxonomy
	) {
		if ( $filters[ $filter_key ] && taxonomy_exists( $taxonomy ) ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $filters[ $filter_key ],
			);
		}
	}

	if ( '' !== $filters['min_price'] || '' !== $filters['max_price'] ) {
		$minimum = '' !== $filters['min_price'] ? (float) $filters['min_price'] : 0;
		$maximum = '' !== $filters['max_price'] ? (float) $filters['max_price'] : PHP_FLOAT_MAX;

		$meta_query[] = array(
			'key'     => '_price',
			'value'   => array( $minimum, $maximum ),
			'compare' => 'BETWEEN',
			'type'    => 'DECIMAL(10,2)',
		);
	}

	if ( $tax_query ) {
		$args['tax_query'] = $tax_query;
	}

	if ( $meta_query ) {
		$args['meta_query'] = $meta_query;
	}

	return $args;
}

/**
 * Apply catalog filters to shareable archive URLs.
 *
 * @param WP_Query $query Main query.
 */
function kramo_filter_catalog_query( $query ) {
	if (
		is_admin()
		|| ! $query->is_main_query()
		|| ( ! is_shop() && ! is_product_taxonomy() )
	) {
		return;
	}

	$args = kramo_apply_catalog_filters_to_args(
		array(
			'tax_query'  => $query->get( 'tax_query', array() ),
			'meta_query' => $query->get( 'meta_query', array() ),
		),
		kramo_get_catalog_filters()
	);

	if ( isset( $args['tax_query'] ) ) {
		$query->set( 'tax_query', $args['tax_query'] );
	}

	if ( isset( $args['meta_query'] ) ) {
		$query->set( 'meta_query', $args['meta_query'] );
	}
}
add_action( 'pre_get_posts', 'kramo_filter_catalog_query', 30 );

/**
 * Return filter options for a product attribute taxonomy.
 *
 * @param string $taxonomy Attribute taxonomy.
 * @return array<int, WP_Term>
 */
function kramo_get_filter_terms( $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Render a select control for a filter taxonomy.
 *
 * @param string             $name        Request key.
 * @param string             $label       Visible label.
 * @param array<int,WP_Term> $terms       Available terms.
 * @param string             $active      Active slug.
 * @param string             $placeholder Empty option copy.
 */
function kramo_render_filter_select( $name, $label, $terms, $active, $placeholder ) {
	?>
	<label class="kramo-filter-field">
		<span><?php echo esc_html( $label ); ?></span>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value=""><?php echo esc_html( $placeholder ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $active, $term->slug ); ?>>
					<?php echo esc_html( $term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
}

/**
 * Render the no-plugin catalog filter form.
 */
function kramo_catalog_filters() {
	if ( wp_doing_ajax() ) {
		return;
	}

	$filters    = kramo_get_catalog_filters();
	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => array( get_option( 'default_product_cat' ) ),
		)
	);
	$categories = is_wp_error( $categories ) ? array() : $categories;
	?>
	<form class="kramo-filters" method="get" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
		<div class="kramo-filters__fields">
			<?php
			kramo_render_filter_select(
				'ws_category',
				__( 'Kategoria', 'kramo' ),
				$categories,
				$filters['category'],
				__( 'Wszystkie kategorie', 'kramo' )
			);
			kramo_render_filter_select(
				'ws_color',
				__( 'Kolor', 'kramo' ),
				kramo_get_filter_terms( 'pa_kolor' ),
				$filters['color'],
				__( 'Wszystkie kolory', 'kramo' )
			);
			kramo_render_filter_select(
				'ws_size',
				__( 'Rozmiar', 'kramo' ),
				kramo_get_filter_terms( 'pa_rozmiar' ),
				$filters['size'],
				__( 'Wszystkie rozmiary', 'kramo' )
			);
			?>
			<fieldset class="kramo-filter-price">
				<legend><?php echo esc_html__( 'Cena', 'kramo' ); ?></legend>
				<label>
					<span class="screen-reader-text"><?php echo esc_html__( 'Cena minimalna', 'kramo' ); ?></span>
					<input
						type="number"
						name="ws_min_price"
						min="0"
						step="1"
						inputmode="decimal"
						placeholder="<?php echo esc_attr__( 'Od', 'kramo' ); ?>"
						value="<?php echo esc_attr( $filters['min_price'] ); ?>"
					>
				</label>
				<label>
					<span class="screen-reader-text"><?php echo esc_html__( 'Cena maksymalna', 'kramo' ); ?></span>
					<input
						type="number"
						name="ws_max_price"
						min="0"
						step="1"
						inputmode="decimal"
						placeholder="<?php echo esc_attr__( 'Do', 'kramo' ); ?>"
						value="<?php echo esc_attr( $filters['max_price'] ); ?>"
					>
				</label>
			</fieldset>
		</div>
		<div class="kramo-filters__actions">
			<button type="submit"><?php echo esc_html__( 'Filtruj', 'kramo' ); ?></button>
			<a class="kramo-filters__reset" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php echo esc_html__( 'Wyczyść filtry', 'kramo' ); ?>
			</a>
		</div>
	</form>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'kramo_catalog_filters', 5 );
add_action( 'woocommerce_no_products_found', 'kramo_catalog_filters', 5 );

/**
 * Open the replaceable AJAX results region.
 */
function kramo_catalog_region_open() {
	echo '<div class="kramo-catalog-results" aria-live="polite" aria-busy="false">';
}
add_action( 'woocommerce_before_shop_loop', 'kramo_catalog_region_open', 15 );

/**
 * Close the replaceable AJAX results region.
 */
function kramo_catalog_region_close() {
	echo '</div>';
}
add_action( 'woocommerce_after_shop_loop', 'kramo_catalog_region_close', 99 );

/**
 * Keep the replaceable region available on a full no-results page.
 */
function kramo_no_products_region_open() {
	if ( ! wp_doing_ajax() ) {
		kramo_catalog_region_open();
	}
}
add_action( 'woocommerce_no_products_found', 'kramo_no_products_region_open', 9 );

/**
 * Close the full-page no-results region.
 */
function kramo_no_products_region_close() {
	if ( ! wp_doing_ajax() ) {
		kramo_catalog_region_close();
	}
}
add_action( 'woocommerce_no_products_found', 'kramo_no_products_region_close', 99 );

/**
 * Render a product loop for AJAX and wishlist responses.
 *
 * @param WP_Query $query          Product query.
 * @param string   $pagination_url Public URL for AJAX pagination.
 * @return string
 */
function kramo_render_product_query( $query, $pagination_url = '' ) {
	ob_start();

	if ( $query->have_posts() ) {
		wc_set_loop_prop( 'total', $query->found_posts );
		wc_set_loop_prop( 'per_page', $query->get( 'posts_per_page' ) );
		wc_set_loop_prop( 'current_page', max( 1, $query->get( 'paged' ) ) );
		wc_set_loop_prop( 'total_pages', $query->max_num_pages );
		wc_set_loop_prop( 'columns', 4 );

		woocommerce_product_loop_start();
		while ( $query->have_posts() ) {
			$query->the_post();
			wc_get_template_part( 'content', 'product' );
		}
		woocommerce_product_loop_end();

		if ( $query->max_num_pages > 1 ) {
			if ( $pagination_url ) {
				$base  = str_replace(
					'999999999',
					'%#%',
					esc_url_raw( add_query_arg( 'paged', 999999999, $pagination_url ) )
				);
				$links = paginate_links(
					array(
						'base'      => $base,
						'format'    => '',
						'current'   => max( 1, $query->get( 'paged' ) ),
						'total'     => $query->max_num_pages,
						'type'      => 'list',
						'prev_text' => __( 'Poprzednia', 'kramo' ),
						'next_text' => __( 'Następna', 'kramo' ),
					)
				);

				if ( $links ) {
					printf(
						'<nav class="woocommerce-pagination" aria-label="%1$s">%2$s</nav>',
						esc_attr__( 'Nawigacja stron produktów', 'kramo' ),
						wp_kses_post( $links )
					);
				}
			} else {
				$previous_query      = $GLOBALS['wp_query'];
				$GLOBALS['wp_query'] = $query;
				woocommerce_pagination();
				$GLOBALS['wp_query'] = $previous_query;
			}
		}
	} else {
		wc_no_products_found();
	}

	wp_reset_postdata();
	wc_reset_loop();

	return (string) ob_get_clean();
}

/**
 * Return filtered catalog markup to anonymous and authenticated visitors.
 */
function kramo_ajax_filter_products() {
	check_ajax_referer( 'kramo_catalog', 'nonce' );

	$filters = kramo_get_catalog_filters( $_GET );
	$args    = kramo_apply_catalog_filters_to_args(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => kramo_products_per_page(),
			'paged'          => $filters['paged'],
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'tax_query'      => WC()->query->get_tax_query(),
			'meta_query'     => WC()->query->get_meta_query(),
		),
		$filters
	);
	$query   = new WP_Query( $args );
	$url     = add_query_arg(
		array_filter(
			array(
				'ws_category'  => $filters['category'],
				'ws_min_price' => $filters['min_price'],
				'ws_max_price' => $filters['max_price'],
				'ws_color'     => $filters['color'],
				'ws_size'      => $filters['size'],
			),
			static function ( $value ) {
				return '' !== $value;
			}
		),
		wc_get_page_permalink( 'shop' )
	);

	wp_send_json_success(
		array(
			'html'  => kramo_render_product_query( $query, $url ),
			'count' => (int) $query->found_posts,
		)
	);
}
add_action( 'wp_ajax_kramo_filter_products', 'kramo_ajax_filter_products' );
add_action( 'wp_ajax_nopriv_kramo_filter_products', 'kramo_ajax_filter_products' );
