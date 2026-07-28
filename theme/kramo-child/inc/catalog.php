<?php
/**
 * Catalog cards and single-product interactions.
 *
 * @package Kramo
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
function kramo_get_product_attribute_options( $attribute ) {
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
function kramo_loop_product_thumbnail() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	static $position = 0;
	$is_priority     = $position < 4;
	$position++;

	$image_attributes = array(
		'class' => 'kramo-product-image kramo-product-image--primary',
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
				'class'   => 'kramo-product-image kramo-product-image--secondary',
				'loading' => 'lazy',
			)
		);
	}

	printf(
		'<span class="kramo-product-media">%1$s%2$s%3$s</span>',
		wp_kses_post( $primary ),
		wp_kses_post( $secondary ),
		wp_kses_post( kramo_loop_stock_badge( $product ) )
	);
}

/**
 * Return a product price as plain text for JSON responses.
 *
 * get_price_html() carries markup plus a screen-reader duplicate of the range,
 * and its entities would show literally once the client writes the string with
 * textContent, so both are removed here.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function kramo_plain_price( $product ) {
	$html = preg_replace(
		'#<span class="screen-reader-text">.*?</span>#is',
		'',
		(string) $product->get_price_html()
	);

	$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );

	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Return a catalog stock badge for the states worth acting on.
 *
 * Only scarcity and unavailability are shown; a plain "in stock" badge on
 * every tile would be noise rather than a signal.
 *
 * @param WC_Product $product Catalog product.
 * @return string
 */
function kramo_loop_stock_badge( $product ) {
	if ( ! function_exists( 'kramo_availability_status' ) ) {
		return '';
	}

	$status = kramo_availability_status( $product );

	if ( ! in_array( $status['state'], array( 'low', 'out' ), true ) ) {
		return '';
	}

	return sprintf(
		'<span class="kramo-stock-badge kramo-stock-badge--%1$s">%2$s</span>',
		esc_attr( $status['state'] ),
		esc_html( $status['label'] )
	);
}

/**
 * Render color swatches below a catalog image.
 */
function kramo_loop_product_swatches() {
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

		$options = kramo_get_product_attribute_options( $attribute );
		if ( ! $options ) {
			return;
		}

		echo '<span class="kramo-card-swatches" aria-label="' . esc_attr__( 'Dostępne kolory', 'kramo' ) . '">';
		foreach ( $options as $option ) {
			printf(
				'<span class="kramo-card-swatch kramo-card-swatch--%1$s" title="%2$s"><span class="screen-reader-text">%2$s</span></span>',
				esc_attr( sanitize_title( $option ) ),
				esc_attr( $option )
			);
		}
		echo '</span>';

		return;
	}
}

/**
 * Open the media frame that carries the photo and everything layered on it.
 *
 * The frame runs from here to kramo_card_frame_close(), which also closes the
 * product link, so the buying actions can sit over the bottom of the photo
 * without nesting a button inside an anchor.
 */
function kramo_card_frame_open() {
	echo '<div class="kramo-card__frame">';
}
add_action( 'woocommerce_before_shop_loop_item', 'kramo_card_frame_open', 4 );

/**
 * Open the product link around the photo only.
 *
 * WooCommerce wraps the image, the title and the price in one anchor. Once the
 * anchor ends at the photo the accessible name would fall back to the image
 * alt text, which a shop is free to leave empty, so the product name is set
 * explicitly instead.
 */
function kramo_card_media_open() {
	printf(
		'<a href="%1$s" class="woocommerce-loop-product__link kramo-card__media-link" aria-label="%2$s">',
		esc_url( get_permalink() ),
		esc_attr( get_the_title() )
	);
}
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
add_action( 'woocommerce_before_shop_loop_item', 'kramo_card_media_open', 10 );

/**
 * Close the product link, print the hover actions and close the frame.
 */
function kramo_card_frame_close() {
	echo '</a><div class="kramo-card__actions">';
	woocommerce_template_loop_add_to_cart();

	/**
	 * Fires inside the catalog action bar, after the add-to-cart control.
	 */
	do_action( 'kramo_card_actions' );

	echo '</div></div>';
}
add_action( 'woocommerce_before_shop_loop_item_title', 'kramo_card_frame_close', 20 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Print the catalog product name as its own link.
 *
 * The card no longer has an anchor wrapped around the caption, so the name
 * carries the link that used to come from that wrapper.
 */
function kramo_loop_product_title() {
	printf(
		'<h2 class="woocommerce-loop-product__title"><a href="%1$s">%2$s</a></h2>',
		esc_url( get_permalink() ),
		esc_html( get_the_title() )
	);
}
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
add_action( 'woocommerce_shop_loop_item_title', 'kramo_loop_product_title', 10 );

/**
 * Replace the default sale badge copy.
 *
 * @return string
 */
function kramo_sale_flash() {
	return '<span class="onsale">' . esc_html__( 'Promocja', 'kramo' ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'kramo_sale_flash' );

/**
 * Use the same responsive column count for archives and related products.
 *
 * @return int
 */
function kramo_catalog_columns() {
	return 4;
}
add_filter( 'loop_shop_columns', 'kramo_catalog_columns' );

/**
 * Fill the related-products row when a category has fewer than four siblings.
 *
 * @param array $related_ids Related product IDs selected by WooCommerce.
 * @param int   $product_id  Current product ID.
 * @param array $args        Related-products arguments.
 * @return array<int, int>
 */
function kramo_fill_related_products( $related_ids, $product_id, $args ) {
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
add_filter( 'woocommerce_related_products', 'kramo_fill_related_products', 20, 3 );

/**
 * Add the secondary checkout action to product forms.
 */
function kramo_buy_now_button() {
	global $product;

	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
		return;
	}
	?>
	<button
		type="submit"
		class="button kramo-buy-now"
		name="kramo_buy_now"
		value="<?php echo esc_attr( $product->get_id() ); ?>"
	>
		<?php echo esc_html__( 'Kup teraz', 'kramo' ); ?>
	</button>
	<?php
}
add_action( 'woocommerce_after_add_to_cart_button', 'kramo_buy_now_button' );

/**
 * Redirect a successful Buy now submission to checkout.
 *
 * @param string $url Default redirect URL.
 * @return string
 */
function kramo_buy_now_redirect( $url ) {
	if ( empty( $_REQUEST['kramo_buy_now'] ) ) {
		return $url;
	}

	return wc_get_checkout_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'kramo_buy_now_redirect' );

/**
 * Add CSS hooks to variation selects without removing their native behavior.
 *
 * @param array $args Dropdown arguments.
 * @return array
 */
function kramo_variation_dropdown_args( $args ) {
	$args['class'] = trim( ( $args['class'] ?? '' ) . ' kramo-variation-select' );

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'kramo_variation_dropdown_args' );

/**
 * Return the Polish single-product cart label.
 *
 * @return string
 */
function kramo_single_add_to_cart_text() {
	return __( 'Dodaj do koszyka', 'kramo' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'kramo_single_add_to_cart_text' );

/**
 * Return a Polish catalog action label.
 *
 * @param string     $text    Existing label.
 * @param WC_Product $product Catalog product.
 * @return string
 */
function kramo_loop_add_to_cart_text( $text, $product ) {
	return $product instanceof WC_Product && $product->is_type( 'variable' )
		? __( 'Wybierz opcje', 'kramo' )
		: __( 'Dodaj do koszyka', 'kramo' );
}
add_filter( 'woocommerce_product_add_to_cart_text', 'kramo_loop_add_to_cart_text', 10, 2 );

/**
 * Use a Polish placeholder in the retained native variation select.
 *
 * @param array $args Dropdown arguments.
 * @return array
 */
function kramo_variation_placeholder( $args ) {
	$args['show_option_none'] = __( 'Wybierz opcję', 'kramo' );

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'kramo_variation_placeholder', 20 );

/**
 * Translate the variation reset link managed by WooCommerce.
 *
 * @param string $html Reset-link HTML.
 * @return string
 */
function kramo_variation_reset_link( $html ) {
	return sprintf(
		'<a class="reset_variations" href="#" aria-label="%1$s">%2$s</a>',
		esc_attr__( 'Wyczyść wybrane warianty', 'kramo' ),
		esc_html__( 'Wyczyść', 'kramo' )
	);
}
add_filter( 'woocommerce_reset_variations_link', 'kramo_variation_reset_link' );

/**
 * Return Polish stock availability copy.
 *
 * @param string     $text    Existing availability text.
 * @param WC_Product $product Product or variation.
 * @return string
 */
function kramo_availability_text( $text, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $text;
	}

	if ( ! $product->is_in_stock() ) {
		return __( 'Brak w magazynie', 'kramo' );
	}

	if ( $product->managing_stock() && null !== $product->get_stock_quantity() ) {
		return sprintf(
			__( '%s szt. w magazynie', 'kramo' ),
			wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product )
		);
	}

	return __( 'Dostępny', 'kramo' );
}
add_filter( 'woocommerce_get_availability_text', 'kramo_availability_text', 10, 2 );

/**
 * Use Polish breadcrumb home copy.
 *
 * @param array $defaults Breadcrumb settings.
 * @return array
 */
function kramo_breadcrumb_defaults( $defaults ) {
	$defaults['home'] = __( 'Strona główna', 'kramo' );

	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'kramo_breadcrumb_defaults' );

remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
add_action( 'woocommerce_before_shop_loop_item_title', 'kramo_loop_product_thumbnail', 10 );

// Swatches belong to the caption, under the name and price, not to the photo.
add_action( 'woocommerce_after_shop_loop_item_title', 'kramo_loop_product_swatches', 20 );

/**
 * Mark the screens whose grid runs to the edges of the viewport.
 *
 * @param array<int, string> $classes Body classes.
 * @return array<int, string>
 */
function kramo_body_classes( $classes ) {
	if ( kramo_is_catalog_screen() || is_front_page() ) {
		$classes[] = 'kramo-bleed';
	}

	return $classes;
}
add_filter( 'body_class', 'kramo_body_classes' );
