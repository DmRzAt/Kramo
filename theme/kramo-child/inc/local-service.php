<?php
/**
 * Local-service landing page: template registration, editor fields and data.
 *
 * Built for "service + city" orders (e.g. "Mycie kostki brukowej Katowice").
 * Duplicating a page and changing the Usługa and Miasto fields produces a new
 * city page — the H1, copy and LocalBusiness schema follow automatically.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KRAMO_LOCAL_SERVICE_TEMPLATE = 'page-local-service.php';

/**
 * Register the page template so it appears in the Page Attributes dropdown.
 *
 * @param array<string,string> $templates Registered templates.
 * @return array<string,string>
 */
function kramo_register_local_service_template( $templates ) {
	$templates[ KRAMO_LOCAL_SERVICE_TEMPLATE ] = __( 'Usługa lokalna (SEO)', 'kramo' );

	return $templates;
}
add_filter( 'theme_page_templates', 'kramo_register_local_service_template' );

/**
 * Load the template file from the child theme when the page selects it.
 *
 * @param string $template Resolved template path.
 * @return string
 */
function kramo_load_local_service_template( $template ) {
	if ( is_page() && get_page_template_slug() === KRAMO_LOCAL_SERVICE_TEMPLATE ) {
		$candidate = get_stylesheet_directory() . '/' . KRAMO_LOCAL_SERVICE_TEMPLATE;
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}

	return $template;
}
add_filter( 'template_include', 'kramo_load_local_service_template' );

/**
 * Fields stored for a local-service page.
 *
 * @return array<string,string>
 */
function kramo_local_service_fields() {
	return array(
		'_ws_ls_service'  => __( 'Usługa', 'kramo' ),
		'_ws_ls_city'     => __( 'Miasto', 'kramo' ),
		'_ws_ls_area'     => __( 'Obszar obsługi (miasta po przecinku)', 'kramo' ),
		'_ws_ls_price'    => __( 'Cena / przedział (np. 20–40 zł/m²)', 'kramo' ),
		'_ws_ls_phone'    => __( 'Telefon', 'kramo' ),
		'_ws_ls_cta_text' => __( 'Tekst przycisku CTA', 'kramo' ),
		'_ws_ls_cta_url'  => __( 'Link przycisku CTA', 'kramo' ),
	);
}

/**
 * Register the local-service meta box on pages.
 */
function kramo_add_local_service_metabox() {
	add_meta_box(
		'kramo_local_service',
		__( 'Usługa lokalna (SEO)', 'kramo' ),
		'kramo_render_local_service_metabox',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'kramo_add_local_service_metabox' );

/**
 * Render the local-service meta box.
 *
 * @param WP_Post $post Current page.
 */
function kramo_render_local_service_metabox( $post ) {
	wp_nonce_field( 'kramo_local_service', 'kramo_local_service_nonce' );

	echo '<p class="description">'
		. esc_html__( 'Pola używane, gdy szablon strony to „Usługa lokalna (SEO)”. Kopiując stronę do nowego miasta, zmień Usługę i Miasto.', 'kramo' )
		. '</p>';

	foreach ( kramo_local_service_fields() as $key => $label ) {
		$value = (string) get_post_meta( $post->ID, $key, true );
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="text" id="%1$s" name="%1$s" value="%3$s" class="widefat"></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $value )
		);
	}

	$faq = (string) get_post_meta( $post->ID, '_ws_ls_faq', true );
	printf(
		'<p><label for="_ws_ls_faq"><strong>%1$s</strong></label><br><span class="description">%2$s</span><br><textarea id="_ws_ls_faq" name="_ws_ls_faq" rows="6" class="widefat">%3$s</textarea></p>',
		esc_html__( 'FAQ', 'kramo' ),
		esc_html__( 'Jedna para na linię: Pytanie :: Odpowiedź', 'kramo' ),
		esc_textarea( $faq )
	);
}

/**
 * Save the local-service fields.
 *
 * @param int $post_id Page ID.
 */
function kramo_save_local_service_metabox( $post_id ) {
	if (
		! isset( $_POST['kramo_local_service_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_key( $_POST['kramo_local_service_nonce'] ),
			'kramo_local_service'
		)
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	foreach ( array_keys( kramo_local_service_fields() ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '_ws_ls_cta_url' === $key ) {
			$value = esc_url_raw( $value );
		}
		update_post_meta( $post_id, $key, $value );
	}

	$faq = isset( $_POST['_ws_ls_faq'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_ws_ls_faq'] ) ) : '';
	update_post_meta( $post_id, '_ws_ls_faq', $faq );
}
add_action( 'save_post_page', 'kramo_save_local_service_metabox' );

/**
 * Normalized local-service data for a page.
 *
 * @param int $post_id Page ID.
 * @return array<string,mixed>
 */
function kramo_get_local_service_data( $post_id ) {
	$area_raw = (string) get_post_meta( $post_id, '_ws_ls_area', true );
	$area     = array_values(
		array_filter( array_map( 'trim', explode( ',', $area_raw ) ) )
	);

	return array(
		'service'     => (string) get_post_meta( $post_id, '_ws_ls_service', true ),
		'city'        => (string) get_post_meta( $post_id, '_ws_ls_city', true ),
		'area_served' => $area,
		'price_range' => (string) get_post_meta( $post_id, '_ws_ls_price', true ),
		'phone'       => (string) get_post_meta( $post_id, '_ws_ls_phone', true ),
		'faq_raw'     => (string) get_post_meta( $post_id, '_ws_ls_faq', true ),
		'cta_text'    => (string) get_post_meta( $post_id, '_ws_ls_cta_text', true ),
		'cta_url'     => (string) get_post_meta( $post_id, '_ws_ls_cta_url', true ),
	);
}
