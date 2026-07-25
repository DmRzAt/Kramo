<?php
/**
 * Lazy product-gallery video.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the product video URL field.
 */
function kramo_product_video_field() {
	woocommerce_wp_text_input(
		array(
			'id'          => '_kramo_product_video',
			'label'       => __( 'Wideo produktu', 'kramo' ),
			'description' => __( 'Wklej adres YouTube, Vimeo albo wybierz plik MP4 z biblioteki mediów.', 'kramo' ),
			'desc_tip'    => true,
			'type'        => 'url',
		)
	);
	?>
	<p class="form-field">
		<button type="button" class="button kramo-select-video">
			<?php echo esc_html__( 'Wybierz MP4', 'kramo' ); ?>
		</button>
	</p>
	<?php
}
add_action( 'woocommerce_product_options_general_product_data', 'kramo_product_video_field' );

/**
 * Validate a supported product video URL.
 *
 * @param string $url Submitted URL.
 * @return string
 */
function kramo_sanitize_product_video_url( $url ) {
	$url  = esc_url_raw( $url );
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );

	if (
		false !== strpos( $host, 'youtube.com' )
		|| false !== strpos( $host, 'youtu.be' )
		|| false !== strpos( $host, 'vimeo.com' )
		|| str_ends_with( $path, '.mp4' )
	) {
		return $url;
	}

	return '';
}

/**
 * Save the product video URL.
 *
 * @param WC_Product $product Product being saved.
 */
function kramo_save_product_video( $product ) {
	if ( ! isset( $_POST['_kramo_product_video'] ) ) {
		return;
	}

	$url = kramo_sanitize_product_video_url(
		wp_unslash( $_POST['_kramo_product_video'] )
	);

	if ( $url ) {
		$product->update_meta_data( '_kramo_product_video', $url );
	} else {
		$product->delete_meta_data( '_kramo_product_video' );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'kramo_save_product_video' );

/**
 * Convert a supported provider URL to a privacy-conscious embed URL.
 *
 * @param string $url Video URL.
 * @return array{type:string,src:string}
 */
function kramo_get_product_video_source( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );

	if ( str_ends_with( strtolower( $path ), '.mp4' ) ) {
		return array(
			'type' => 'mp4',
			'src'  => $url,
		);
	}

	if ( false !== strpos( $host, 'youtu.be' ) ) {
		$video_id = trim( $path, '/' );
	} elseif ( false !== strpos( $host, 'youtube.com' ) ) {
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$path_parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		$video_id   = isset( $query['v'] )
			? sanitize_key( $query['v'] )
			: sanitize_key( (string) end( $path_parts ) );
	} else {
		$path_parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		$video_id   = sanitize_key( (string) end( $path_parts ) );

		return array(
			'type' => 'embed',
			'src'  => 'https://player.vimeo.com/video/' . rawurlencode( $video_id ),
		);
	}

	return array(
		'type' => 'embed',
		'src'  => 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id ),
	);
}

/**
 * Add a lazy video slide to the native WooCommerce gallery.
 */
function kramo_product_video_slide() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$url = kramo_sanitize_product_video_url(
		(string) $product->get_meta( '_kramo_product_video' )
	);
	if ( ! $url ) {
		return;
	}

	$source    = kramo_get_product_video_source( $url );
	if ( ! $source['src'] ) {
		return;
	}

	$label     = __( 'Odtwórz wideo produktu', 'kramo' );
	$thumbnail = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 125%22%3E%3Cpath d=%22M39 42l31 21-31 20z%22/%3E%3C/svg%3E';
	?>
	<div
		class="woocommerce-product-gallery__image kramo-video-slide"
		data-thumb="<?php echo esc_attr( $thumbnail ); ?>"
	>
		<button
			type="button"
			class="kramo-video-trigger"
			data-video-type="<?php echo esc_attr( $source['type'] ); ?>"
			data-video-src="<?php echo esc_url( $source['src'] ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
		>
			<span class="kramo-video-trigger__icon" aria-hidden="true">▶</span>
			<span><?php echo esc_html( $label ); ?></span>
		</button>
	</div>
	<?php
}
add_action( 'woocommerce_product_thumbnails', 'kramo_product_video_slide', 30 );

/**
 * Load the media picker only on product edit screens.
 *
 * @param string $hook_suffix Current admin screen.
 */
function kramo_product_video_admin_assets( $hook_suffix ) {
	$screen = get_current_screen();
	if (
		! $screen
		|| 'product' !== $screen->post_type
		|| ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true )
	) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'kramo-product-video-admin',
		get_stylesheet_directory_uri() . '/assets/js/admin-product-video.js',
		array(),
		kramo_asset_version( 'assets/js/admin-product-video.js' ),
		true
	);
	wp_localize_script(
		'kramo-product-video-admin',
		'kramoVideoAdmin',
		array(
			'title'  => __( 'Wybierz plik MP4', 'kramo' ),
			'button' => __( 'Użyj tego wideo', 'kramo' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'kramo_product_video_admin_assets' );
