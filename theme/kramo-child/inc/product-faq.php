<?php
/**
 * Product FAQ field and front-end block.
 *
 * Feeds the FAQPage schema in inc/schema.php and renders an accordion tab on the
 * product page when questions are filled in.
 *
 * @package Kramo
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
function kramo_add_faq_product_tab( $tabs ) {
	$tabs['kramo_faq'] = array(
		'label'    => __( 'FAQ', 'kramo' ),
		'target'   => 'kramo_faq_product_data',
		'class'    => array(),
		'priority' => 80,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'kramo_add_faq_product_tab' );

/**
 * Render the FAQ product-data panel.
 */
function kramo_faq_product_panel() {
	?>
	<div id="kramo_faq_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_textarea_input(
				array(
					'id'          => '_kramo_faq',
					'label'       => __( 'Pytania i odpowiedzi', 'kramo' ),
					'description' => __( 'Jedna para na linię w formacie: Pytanie :: Odpowiedź', 'kramo' ),
					'desc_tip'    => true,
					'rows'        => 8,
					'value'       => kramo_post_meta_value( get_the_ID(), '_kramo_faq' ),
					'placeholder' => "Czy produkt jest wodoodporny? :: Tak, materiał jest impregnowany.\nJaki jest czas dostawy? :: Zwykle 1–2 dni robocze.",
				)
			);
			?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'kramo_faq_product_panel' );

/**
 * Save the FAQ field.
 *
 * @param WC_Product $product Product being saved.
 */
function kramo_save_faq_field( $product ) {
	if ( ! isset( $_POST['_kramo_faq'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( '_kramo_faq', '' );
		return;
	}

	$raw   = sanitize_textarea_field( wp_unslash( $_POST['_kramo_faq'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$lines = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line && false !== strpos( $line, '::' ) ) {
			$lines[] = $line;
		}
	}

	$product->update_meta_data( '_kramo_faq', implode( "\n", $lines ) );
}
add_action( 'woocommerce_admin_process_product_object', 'kramo_save_faq_field' );

/**
 * Register a product tab that shows the FAQ accordion when filled.
 *
 * @param array<string,array<string,mixed>> $tabs Front-end product tabs.
 * @return array<string,array<string,mixed>>
 */
function kramo_faq_frontend_tab( $tabs ) {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return $tabs;
	}

	if ( empty( kramo_parse_faq( kramo_product_meta( $product, '_kramo_faq' ) ) ) ) {
		return $tabs;
	}

	$tabs['kramo_faq'] = array(
		'title'    => __( 'FAQ', 'kramo' ),
		'priority' => 25,
		'callback' => 'kramo_faq_tab_content',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'kramo_faq_frontend_tab' );

/**
 * Render the FAQ accordion tab content.
 */
function kramo_faq_tab_content() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$pairs = kramo_parse_faq( kramo_product_meta( $product, '_kramo_faq' ) );
	if ( empty( $pairs ) ) {
		return;
	}

	echo '<div class="kramo-faq">';
	foreach ( $pairs as $pair ) {
		echo '<details class="kramo-faq__item">';
		printf( '<summary class="kramo-faq__question">%s</summary>', esc_html( $pair['question'] ) );
		printf( '<div class="kramo-faq__answer">%s</div>', esc_html( $pair['answer'] ) );
		echo '</details>';
	}
	echo '</div>';
}
