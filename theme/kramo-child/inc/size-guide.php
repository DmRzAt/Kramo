<?php
/**
 * Product size-guide field and front-end table.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Split a size-guide value into header and body rows.
 *
 * @param mixed $value Stored meta value.
 * @return array{head:array<int,string>,rows:array<int,array<int,string>>}
 */
function kramo_parse_size_guide( $value ) {
	$empty = array(
		'head' => array(),
		'rows' => array(),
	);

	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return $empty;
	}

	$table = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $value ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}

		$cells = array_values(
			array_filter(
				array_map( 'trim', explode( '::', $line ) ),
				static function ( $cell ) {
					return '' !== $cell;
				}
			)
		);

		if ( count( $cells ) < 2 ) {
			continue;
		}

		$table[] = $cells;
	}

	if ( count( $table ) < 2 ) {
		return $empty;
	}

	$head = array_shift( $table );

	return array(
		'head' => $head,
		'rows' => $table,
	);
}

/**
 * Add the size-guide product-data tab.
 *
 * @param array<string,array<string,mixed>> $tabs Product data tabs.
 * @return array<string,array<string,mixed>>
 */
function kramo_add_size_guide_product_tab( $tabs ) {
	$tabs['kramo_size_guide'] = array(
		'label'    => __( 'Tabela rozmiarów', 'kramo' ),
		'target'   => 'kramo_size_guide_product_data',
		'class'    => array(),
		'priority' => 81,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'kramo_add_size_guide_product_tab' );

/**
 * Render the size-guide product-data panel.
 */
function kramo_size_guide_product_panel() {
	?>
	<div id="kramo_size_guide_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_textarea_input(
				array(
					'id'          => '_ws_size_guide',
					'label'       => __( 'Tabela rozmiarów', 'kramo' ),
					'description' => __( 'Jeden wiersz na linię, komórki rozdzielone ::. Pierwszy wiersz to nagłówek.', 'kramo' ),
					'desc_tip'    => true,
					'rows'        => 8,
					'placeholder' => "Rozmiar :: Obwód klatki (cm) :: Długość (cm)\nS :: 92-96 :: 68\nM :: 98-102 :: 70\nL :: 104-108 :: 72",
				)
			);
			?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'kramo_size_guide_product_panel' );

/**
 * Save the size-guide field.
 *
 * @param WC_Product $product Product being saved.
 */
function kramo_save_size_guide_field( $product ) {
	if ( ! isset( $_POST['_ws_size_guide'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( '_ws_size_guide', '' );
		return;
	}

	$raw   = sanitize_textarea_field( wp_unslash( $_POST['_ws_size_guide'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$lines = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line && false !== strpos( $line, '::' ) ) {
			$lines[] = $line;
		}
	}

	$product->update_meta_data( '_ws_size_guide', implode( "\n", $lines ) );
}
add_action( 'woocommerce_admin_process_product_object', 'kramo_save_size_guide_field' );

/**
 * Register the front-end size-guide tab when the table is filled.
 *
 * @param array<string,array<string,mixed>> $tabs Front-end product tabs.
 * @return array<string,array<string,mixed>>
 */
function kramo_size_guide_frontend_tab( $tabs ) {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return $tabs;
	}

	$table = kramo_parse_size_guide( $product->get_meta( '_ws_size_guide' ) );
	if ( empty( $table['rows'] ) ) {
		return $tabs;
	}

	$tabs['kramo_size_guide'] = array(
		'title'    => __( 'Tabela rozmiarów', 'kramo' ),
		'priority' => 24,
		'callback' => 'kramo_size_guide_tab_content',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'kramo_size_guide_frontend_tab' );

/**
 * Render the size-guide table.
 */
function kramo_size_guide_tab_content() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$table = kramo_parse_size_guide( $product->get_meta( '_ws_size_guide' ) );
	if ( empty( $table['rows'] ) ) {
		return;
	}

	$columns = count( $table['head'] );

	echo '<div class="kramo-size-guide"><table class="kramo-size-guide__table"><thead><tr>';
	foreach ( $table['head'] as $heading ) {
		printf( '<th scope="col">%s</th>', esc_html( $heading ) );
	}
	echo '</tr></thead><tbody>';

	foreach ( $table['rows'] as $row ) {
		echo '<tr>';
		for ( $index = 0; $index < $columns; $index++ ) {
			$cell = isset( $row[ $index ] ) ? $row[ $index ] : '';
			if ( 0 === $index ) {
				printf( '<th scope="row">%s</th>', esc_html( $cell ) );
				continue;
			}
			printf( '<td>%s</td>', esc_html( $cell ) );
		}
		echo '</tr>';
	}

	echo '</tbody></table></div>';
}
