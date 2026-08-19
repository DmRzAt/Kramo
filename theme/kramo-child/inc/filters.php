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

	$category  = kramo_source_value( $source, 'kramo_category' );
	$min_price = kramo_source_value( $source, 'kramo_min_price' );
	$max_price = kramo_source_value( $source, 'kramo_max_price' );
	$color     = kramo_source_value( $source, 'kramo_color' );
	$size      = kramo_source_value( $source, 'kramo_size' );

	return array(
		'search'    => isset( $source['s'] ) ? sanitize_text_field( wp_unslash( $source['s'] ) ) : '',
		'category'  => null !== $category ? sanitize_title( wp_unslash( $category ) ) : '',
		'min_price' => null !== $min_price ? wc_format_decimal( wp_unslash( $min_price ) ) : '',
		'max_price' => null !== $max_price ? wc_format_decimal( wp_unslash( $max_price ) ) : '',
		'color'     => null !== $color ? sanitize_title( wp_unslash( $color ) ) : '',
		'size'      => null !== $size ? sanitize_title( wp_unslash( $size ) ) : '',
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

	if ( ! empty( $filters['search'] ) ) {
		$args['s'] = $filters['search'];
	}

	return $args;
}

/**
 * Whether the query renders a product listing the filters apply to.
 *
 * Product search results are a listing too, so filtering there keeps the search
 * term and the filter panel working together instead of cancelling each other.
 *
 * @param WP_Query $query Query being prepared.
 * @return bool
 */
function kramo_is_filterable_query( $query ) {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	if ( is_shop() || is_product_taxonomy() ) {
		return true;
	}

	return $query->is_search() && 'product' === $query->get( 'post_type' );
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
		|| ! kramo_is_filterable_query( $query )
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
 * The visible label is the empty option itself (letterspaced caps in CSS). A
 * second visible label next to the control would fight the single-line toolbar.
 *
 * @param string             $name        Request key.
 * @param string             $label       Accessible label and empty option.
 * @param array<int,WP_Term> $terms       Available terms.
 * @param string             $active      Active slug.
 */
function kramo_render_filter_select( $name, $label, $terms, $active ) {
	?>
	<label class="kramo-filter-field">
		<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value=""><?php echo esc_html( $label ); ?></option>
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
 * Return the current catalog result count for the toolbar.
 *
 * @return int
 */
function kramo_catalog_result_count() {
	global $wp_query;

	if ( ! $wp_query instanceof WP_Query ) {
		return 0;
	}

	return (int) $wp_query->found_posts;
}

/**
 * Render the catalog toolbar: search, text filters, count and density.
 *
 * The boxed panel is gone. Controls sit on a single hairline row so the
 * photography below them stays the loudest thing on the page.
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
	$count      = kramo_catalog_result_count();

	// Filtering from a search results page must stay on that page, otherwise the
	// term is dropped and the visitor lands on the unfiltered shop.
	$searching = '' !== $filters['search'];
	$action    = $searching ? home_url( '/' ) : wc_get_page_permalink( 'shop' );
	$reset_url = $searching
		? add_query_arg(
			array(
				's'         => $filters['search'],
				'post_type' => 'product',
			),
			home_url( '/' )
		)
		: wc_get_page_permalink( 'shop' );
	?>
	<div class="kramo-catalog-toolbar">
		<?php kramo_render_search_form(); ?>
		<form class="kramo-filters" method="get" action="<?php echo esc_url( $action ); ?>">
			<?php if ( $searching ) : ?>
				<input type="hidden" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>">
				<input type="hidden" name="post_type" value="product">
			<?php endif; ?>
			<div class="kramo-filters__fields">
				<?php
				kramo_render_filter_select(
					'kramo_category',
					__( 'Kategoria', 'kramo' ),
					$categories,
					$filters['category']
				);
				kramo_render_filter_select(
					'kramo_color',
					__( 'Kolor', 'kramo' ),
					kramo_get_filter_terms( 'pa_kolor' ),
					$filters['color']
				);
				kramo_render_filter_select(
					'kramo_size',
					__( 'Rozmiar', 'kramo' ),
					kramo_get_filter_terms( 'pa_rozmiar' ),
					$filters['size']
				);
				?>
				<fieldset class="kramo-filter-price">
					<legend><?php echo esc_html__( 'Cena', 'kramo' ); ?></legend>
					<label>
						<span class="screen-reader-text"><?php echo esc_html__( 'Cena minimalna', 'kramo' ); ?></span>
						<input
							type="number"
							name="kramo_min_price"
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
							name="kramo_max_price"
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
				<a class="kramo-filters__reset" href="<?php echo esc_url( $reset_url ); ?>">
					<?php echo esc_html__( 'Wyczyść', 'kramo' ); ?>
				</a>
			</div>
		</form>
		<div class="kramo-catalog-view">
			<p class="kramo-catalog-view__count" data-kramo-catalog-count>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of products matching the current filters. */
						_n( 'Liczba produktów: %d', 'Liczba produktów: %d', $count, 'kramo' ),
						$count
					)
				);
				?>
			</p>
			<div class="kramo-catalog-density" role="group" aria-label="<?php echo esc_attr__( 'Widok siatki', 'kramo' ); ?>">
				<span><?php echo esc_html__( 'Widok:', 'kramo' ); ?></span>
				<button type="button" class="kramo-catalog-density__option" data-kramo-density="2" aria-pressed="false">2</button>
				<button type="button" class="kramo-catalog-density__option" data-kramo-density="4" aria-pressed="true">4</button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'kramo_catalog_filters', 5 );
add_action( 'woocommerce_no_products_found', 'kramo_catalog_filters', 5 );

/**
 * Open the replaceable AJAX results region.
 */
function kramo_catalog_region_open() {
	echo '<div class="kramo-catalog-results" data-columns="4" aria-busy="false">';
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
		do_action( 'woocommerce_no_products_found' );
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
	$base    = '' !== $filters['search'] ? home_url( '/' ) : wc_get_page_permalink( 'shop' );
	$url     = add_query_arg(
		array_filter(
			array(
				's'            => $filters['search'],
				'post_type'    => '' !== $filters['search'] ? 'product' : '',
				'kramo_category'  => $filters['category'],
				'kramo_min_price' => $filters['min_price'],
				'kramo_max_price' => $filters['max_price'],
				'kramo_color'     => $filters['color'],
				'kramo_size'      => $filters['size'],
			),
			static function ( $value ) {
				return '' !== $value;
			}
		),
		$base
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
