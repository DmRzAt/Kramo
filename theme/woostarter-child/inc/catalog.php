<?php
/**
 * Catalog cards and single-product interactions.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return options for a product attribute.
 *
 * @param WC_Product_Attribute $attribute Product attribute.
 * @return array<int, string>
 */
function woostarter_get_product_attribute_options( $attribute ) {
	if ( $attribute->is_taxonomy() ) {
		$terms = wc_get_product_terms(
			get_the_ID(),
			$attribute->get_name(),
			array( 'fields' => 'names' )
		);

		return is_wp_error( $terms ) ? array() : $terms;
	}

	return array_map( 'strval', $attribute->get_options() );
}

/**
 * Render the catalog thumbnail with an optional gallery hover image.
 */
function woostarter_loop_product_thumbnail() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	static $position = 0;
	$is_priority     = $position < 4;
	$position++;

	$image_attributes = array(
		'class' => 'woostarter-product-image woostarter-product-image--primary',
	);

	if ( $is_priority ) {
		$image_attributes['loading'] = 'eager';
	} else {
		$image_attributes['loading'] = 'lazy';
	}

	$size       = apply_filters( 'single_product_archive_thumbnail_size', 'woocommerce_thumbnail' );
	$primary    = $product->get_image( $size, $image_attributes );

	// WordPress recomputes loading and priority attributes, so the LCP hint is
	// written onto the finished markup instead of passed as an attribute. Only
	// the first tile gets it; spreading the hint over several images cancels it.
	$is_catalog_screen = function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );

	if ( 1 === $position && $is_catalog_screen && false === strpos( $primary, 'fetchpriority' ) ) {
		$primary = preg_replace( '/<img /', '<img fetchpriority="high" ', $primary, 1 );
	}
	$gallery    = $product->get_gallery_image_ids();
	$secondary  = '';
	$second_id  = $gallery ? (int) reset( $gallery ) : 0;

	if ( $second_id ) {
		$secondary = wp_get_attachment_image(
			$second_id,
			$size,
			false,
			array(
				'class'   => 'woostarter-product-image woostarter-product-image--secondary',
				'loading' => 'lazy',
			)
		);
	}

	printf(
		'<span class="woostarter-product-media">%1$s%2$s</span>',
		wp_kses_post( $primary ),
		wp_kses_post( $secondary )
	);
}

/**
 * Render color swatches below a catalog image.
 */
function woostarter_loop_product_swatches() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	foreach ( $product->get_attributes() as $attribute ) {
		$name = wc_attribute_label( $attribute->get_name() );
		if (
			false === stripos( $name, 'kolor' )
			&& false === stripos( $name, 'color' )
		) {
			continue;
		}

		$options = woostarter_get_product_attribute_options( $attribute );
		if ( ! $options ) {
			return;
		}

		echo '<span class="woostarter-card-swatches" aria-label="' . esc_attr__( 'Dostępne kolory', 'woostarter' ) . '">';
		foreach ( $options as $option ) {
			printf(
				'<span class="woostarter-card-swatch woostarter-card-swatch--%1$s" title="%2$s"><span class="screen-reader-text">%2$s</span></span>',
				esc_attr( sanitize_title( $option ) ),
				esc_attr( $option )
			);
		}
		echo '</span>';

		return;
	}
}

/**
 * Replace the default sale badge copy.
 *
 * @return string
 */
function woostarter_sale_flash() {
	return '<span class="onsale">' . esc_html__( 'Promocja', 'woostarter' ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'woostarter_sale_flash' );

/**
 * Use the same responsive column count for archives and related products.
 *
 * @return int
 */
function woostarter_catalog_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'woostarter_catalog_columns' );

/**
 * Fill the related-products row when a category has fewer than four siblings.
 *
 * @param array $related_ids Related product IDs selected by WooCommerce.
 * @param int   $product_id  Current product ID.
 * @param array $args        Related-products arguments.
 * @return array<int, int>
 */
function woostarter_fill_related_products( $related_ids, $product_id, $args ) {
	$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 4;
	if ( count( $related_ids ) >= $limit ) {
		return array_slice( $related_ids, 0, $limit );
	}

	$exclude = array_merge( array( $product_id ), array_map( 'absint', $related_ids ) );
	$extra   = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => $limit - count( $related_ids ),
			'exclude' => $exclude,
			'return'  => 'ids',
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);

	return array_slice(
		array_values( array_unique( array_merge( $related_ids, $extra ) ) ),
		0,
		$limit
	);
}
add_filter( 'woocommerce_related_products', 'woostarter_fill_related_products', 20, 3 );

/**
 * Add the secondary checkout action to product forms.
 */
function woostarter_buy_now_button() {
	global $product;

	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
		return;
	}
	?>
	<button
		type="submit"
		class="button woostarter-buy-now"
		name="woostarter_buy_now"
		value="<?php echo esc_attr( $product->get_id() ); ?>"
	>
		<?php echo esc_html__( 'Kup teraz', 'woostarter' ); ?>
	</button>
	<?php
}
add_action( 'woocommerce_after_add_to_cart_button', 'woostarter_buy_now_button' );

/**
 * Redirect a successful Buy now submission to checkout.
 *
 * @param string $url Default redirect URL.
 * @return string
 */
function woostarter_buy_now_redirect( $url ) {
	if ( empty( $_REQUEST['woostarter_buy_now'] ) ) {
		return $url;
	}

	return wc_get_checkout_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'woostarter_buy_now_redirect' );

/**
 * Add CSS hooks to variation selects without removing their native behavior.
 *
 * @param array $args Dropdown arguments.
 * @return array
 */
function woostarter_variation_dropdown_args( $args ) {
	$args['class'] = trim( ( $args['class'] ?? '' ) . ' woostarter-variation-select' );

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'woostarter_variation_dropdown_args' );

/**
 * Return the Polish single-product cart label.
 *
 * @return string
 */
function woostarter_single_add_to_cart_text() {
	return __( 'Dodaj do koszyka', 'woostarter' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'woostarter_single_add_to_cart_text' );

/**
 * Return a Polish catalog action label.
 *
 * @param string     $text    Existing label.
 * @param WC_Product $product Catalog product.
 * @return string
 */
function woostarter_loop_add_to_cart_text( $text, $product ) {
	return $product instanceof WC_Product && $product->is_type( 'variable' )
		? __( 'Wybierz opcje', 'woostarter' )
		: __( 'Dodaj do koszyka', 'woostarter' );
}
add_filter( 'woocommerce_product_add_to_cart_text', 'woostarter_loop_add_to_cart_text', 10, 2 );

/**
 * Use a Polish placeholder in the retained native variation select.
 *
 * @param array $args Dropdown arguments.
 * @return array
 */
function woostarter_variation_placeholder( $args ) {
	$args['show_option_none'] = __( 'Wybierz opcję', 'woostarter' );

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'woostarter_variation_placeholder', 20 );

/**
 * Translate the variation reset link managed by WooCommerce.
 *
 * @param string $html Reset-link HTML.
 * @return string
 */
function woostarter_variation_reset_link( $html ) {
	return sprintf(
		'<a class="reset_variations" href="#" aria-label="%1$s">%2$s</a>',
		esc_attr__( 'Wyczyść wybrane warianty', 'woostarter' ),
		esc_html__( 'Wyczyść', 'woostarter' )
	);
}
add_filter( 'woocommerce_reset_variations_link', 'woostarter_variation_reset_link' );

/**
 * Return Polish stock availability copy.
 *
 * @param string     $text    Existing availability text.
 * @param WC_Product $product Product or variation.
 * @return string
 */
function woostarter_availability_text( $text, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $text;
	}

	if ( ! $product->is_in_stock() ) {
		return __( 'Brak w magazynie', 'woostarter' );
	}

	if ( $product->managing_stock() && null !== $product->get_stock_quantity() ) {
		return sprintf(
			__( '%s szt. w magazynie', 'woostarter' ),
			wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product )
		);
	}

	return __( 'Dostępny', 'woostarter' );
}
add_filter( 'woocommerce_get_availability_text', 'woostarter_availability_text', 10, 2 );

/**
 * Use Polish breadcrumb home copy.
 *
 * @param array $defaults Breadcrumb settings.
 * @return array
 */
function woostarter_breadcrumb_defaults( $defaults ) {
	$defaults['home'] = __( 'Strona główna', 'woostarter' );

	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'woostarter_breadcrumb_defaults' );

remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
add_action( 'woocommerce_before_shop_loop_item_title', 'woostarter_loop_product_thumbnail', 10 );
add_action( 'woocommerce_before_shop_loop_item_title', 'woostarter_loop_product_swatches', 12 );
