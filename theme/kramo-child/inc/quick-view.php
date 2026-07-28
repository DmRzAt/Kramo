<?php
/**
 * Quick view modal for catalog cards.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the quick view trigger inside the catalog action bar.
 *
 * The product link now ends at the photo, so the bar that rises over the image
 * on hover is outside the anchor and can hold real controls. The trigger is the
 * second row there, under the add-to-cart action it must never outrank.
 */
function kramo_loop_quick_view_trigger() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	printf(
		'<button type="button" class="kramo-quick-view-trigger" data-kramo-quick-view="%1$d" aria-haspopup="dialog">%2$s</button>',
		(int) $product->get_id(),
		esc_html__( 'Szybki podgląd', 'kramo' )
	);
}
add_action( 'kramo_card_actions', 'kramo_loop_quick_view_trigger', 10 );

/**
 * Render the shared modal shell once per page.
 */
function kramo_render_quick_view_dialog() {
	if ( ! function_exists( 'is_woocommerce' ) || ! kramo_needs_woocommerce_assets() ) {
		return;
	}

	printf(
		'<dialog class="kramo-quick-view" data-kramo-quick-view-dialog aria-label="%1$s">'
		. '<button type="button" class="kramo-quick-view__close" data-kramo-quick-view-close aria-label="%2$s">&times;</button>'
		. '<div class="kramo-quick-view__content" data-kramo-quick-view-content></div>'
		. '</dialog>',
		esc_attr__( 'Szybki podgląd produktu', 'kramo' ),
		esc_attr__( 'Zamknij podgląd', 'kramo' )
	);
}
add_action( 'wp_footer', 'kramo_render_quick_view_dialog', 6 );

/**
 * Return the quick view body for one product.
 */
function kramo_ajax_quick_view() {
	check_ajax_referer( 'kramo_quick_view', 'nonce' );

	$product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : null;

	if (
		! $product instanceof WC_Product
		|| 'publish' !== get_post_status( $product_id )
		|| ! $product->is_visible()
	) {
		wp_send_json_error( array( 'message' => __( 'Nie znaleziono produktu.', 'kramo' ) ), 404 );
	}

	$previous_product   = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
	$previous_post      = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	$GLOBALS['product'] = $product;
	$GLOBALS['post']    = get_post( $product_id );

	setup_postdata( $GLOBALS['post'] );

	ob_start();
	?>
	<div class="woocommerce">
		<div <?php wc_product_class( 'kramo-quick-view__body', $product ); ?>>
			<div class="kramo-quick-view__media">
				<?php
				echo wp_kses_post(
					$product->get_image( 'woocommerce_single', array( 'class' => 'kramo-quick-view__image' ) )
				);
				?>
			</div>
			<div class="kramo-quick-view__summary">
				<h2 class="kramo-quick-view__title"><?php echo esc_html( $product->get_name() ); ?></h2>
				<p class="kramo-quick-view__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
				<div class="kramo-quick-view__excerpt">
					<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
				</div>
				<?php woocommerce_template_single_add_to_cart(); ?>
				<a class="kramo-quick-view__full" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
					<?php echo esc_html__( 'Zobacz pełny opis', 'kramo' ); ?>
				</a>
			</div>
		</div>
	</div>
	<?php
	$html = (string) ob_get_clean();

	wp_reset_postdata();
	$GLOBALS['product'] = $previous_product;
	$GLOBALS['post']    = $previous_post;

	wp_send_json_success(
		array(
			'html'  => $html,
			'title' => $product->get_name(),
		)
	);
}
add_action( 'wp_ajax_kramo_quick_view', 'kramo_ajax_quick_view' );
add_action( 'wp_ajax_nopriv_kramo_quick_view', 'kramo_ajax_quick_view' );
