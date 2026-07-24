<?php
/**
 * Product FAQ field and front-end block.
 *
 * Feeds the FAQPage schema in inc/schema.php and renders an accordion tab on the
 * product page when questions are filled in.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the FAQ product-data tab.
 *
 * @param array<string,array<string,mixed>> $tabs Product data tabs.
 * @return array<string,array<string,mixed>>
 */
function woostarter_add_faq_product_tab( $tabs ) {
	$tabs['woostarter_faq'] = array(
		'label'    => __( 'FAQ', 'woostarter' ),
		'target'   => 'woostarter_faq_product_data',
		'class'    => array(),
		'priority' => 80,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'woostarter_add_faq_product_tab' );

/**
 * Render the FAQ product-data panel.
 */
function woostarter_faq_product_panel() {
	?>
	<div id="woostarter_faq_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_textarea_input(
				array(
					'id'          => '_ws_faq',
					'label'       => __( 'Pytania i odpowiedzi', 'woostarter' ),
					'description' => __( 'Jedna para na linię w formacie: Pytanie :: Odpowiedź', 'woostarter' ),
					'desc_tip'    => true,
					'rows'        => 8,
					'placeholder' => "Czy produkt jest wodoodporny? :: Tak, materiał jest impregnowany.\nJaki jest czas dostawy? :: Zwykle 1–2 dni robocze.",
				)
			);
			?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'woostarter_faq_product_panel' );

/**
 * Save the FAQ field.
 *
 * @param WC_Product $product Product being saved.
 */
function woostarter_save_faq_field( $product ) {
	if ( ! isset( $_POST['_ws_faq'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( '_ws_faq', '' );
		return;
	}

	$raw   = sanitize_textarea_field( wp_unslash( $_POST['_ws_faq'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$lines = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line && false !== strpos( $line, '::' ) ) {
			$lines[] = $line;
		}
	}

	$product->update_meta_data( '_ws_faq', implode( "\n", $lines ) );
}
add_action( 'woocommerce_admin_process_product_object', 'woostarter_save_faq_field' );

/**
 * Register a product tab that shows the FAQ accordion when filled.
 *
 * @param array<string,array<string,mixed>> $tabs Front-end product tabs.
 * @return array<string,array<string,mixed>>
 */
function woostarter_faq_frontend_tab( $tabs ) {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return $tabs;
	}

	if ( empty( woostarter_parse_faq( $product->get_meta( '_ws_faq' ) ) ) ) {
		return $tabs;
	}

	$tabs['woostarter_faq'] = array(
		'title'    => __( 'FAQ', 'woostarter' ),
		'priority' => 25,
		'callback' => 'woostarter_faq_tab_content',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'woostarter_faq_frontend_tab' );

/**
 * Render the FAQ accordion tab content.
 */
function woostarter_faq_tab_content() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$pairs = woostarter_parse_faq( $product->get_meta( '_ws_faq' ) );
	if ( empty( $pairs ) ) {
		return;
	}

	echo '<div class="woostarter-faq">';
	foreach ( $pairs as $pair ) {
		echo '<details class="woostarter-faq__item">';
		printf( '<summary class="woostarter-faq__question">%s</summary>', esc_html( $pair['question'] ) );
		printf( '<div class="woostarter-faq__answer">%s</div>', esc_html( $pair['answer'] ) );
		echo '</details>';
	}
	echo '</div>';
}
