<?php
/**
 * Structured data (JSON-LD) and meta tags.
 *
 * Rank Math, when active, already emits Product, Organization, breadcrumb,
 * Open Graph, Twitter and canonical markup. To avoid duplicate output this
 * module only *adds* what Rank Math does not generate automatically —
 * LocalBusiness with areaServed for the local-service template and FAQPage from
 * product / local-page questions — and provides Organization, Open Graph and
 * canonical as a fallback for installs without Rank Math. The hreflang stub is
 * always available for multilingual rollouts.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether Rank Math is active and owning the overlapping SEO output.
 *
 * @return bool
 */
function woostarter_rank_math_active() {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}

/**
 * Print a JSON-LD block.
 *
 * @param array<string,mixed> $data Schema.org graph node.
 */
function woostarter_print_jsonld( array $data ) {
	if ( empty( $data ) ) {
		return;
	}

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}

/**
 * Site social profile URLs used for Organization sameAs.
 *
 * @return array<int,string>
 */
function woostarter_social_profiles() {
	$profiles = get_option( 'woostarter_social_profiles', array() );
	$profiles = is_array( $profiles ) ? $profiles : array();

	return array_values(
		array_filter(
			array_map( 'esc_url_raw', apply_filters( 'woostarter_social_profiles', $profiles ) )
		)
	);
}

/**
 * Site logo URL, if configured.
 *
 * @return string
 */
function woostarter_logo_url() {
	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$src = wp_get_attachment_image_src( $logo_id, 'full' );
		if ( $src ) {
			return $src[0];
		}
	}

	return get_site_icon_url();
}

/**
 * Organization node (fallback only — Rank Math owns this when active).
 */
function woostarter_organization_schema() {
	if ( woostarter_rank_math_active() ) {
		return;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Organization',
		'name'     => get_bloginfo( 'name' ),
		'url'      => home_url( '/' ),
	);

	$logo = woostarter_logo_url();
	if ( $logo ) {
		$data['logo'] = $logo;
	}

	$social = woostarter_social_profiles();
	if ( $social ) {
		$data['sameAs'] = $social;
	}

	woostarter_print_jsonld( $data );
}
add_action( 'wp_head', 'woostarter_organization_schema', 20 );

/**
 * FAQ pairs parsed from a "Question :: Answer" per-line text field.
 *
 * @param string $raw Raw field value.
 * @return array<int,array{question:string,answer:string}>
 */
function woostarter_parse_faq( $raw ) {
	$pairs = array();

	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || false === strpos( $line, '::' ) ) {
			continue;
		}

		list( $question, $answer ) = array_map( 'trim', explode( '::', $line, 2 ) );
		if ( '' !== $question && '' !== $answer ) {
			$pairs[] = array(
				'question' => $question,
				'answer'   => $answer,
			);
		}
	}

	return $pairs;
}

/**
 * Build a FAQPage node from FAQ pairs.
 *
 * @param array<int,array{question:string,answer:string}> $pairs FAQ pairs.
 * @return array<string,mixed>
 */
function woostarter_faq_schema( array $pairs ) {
	if ( empty( $pairs ) ) {
		return array();
	}

	$entities = array();
	foreach ( $pairs as $pair ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $pair['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pair['answer'],
			),
		);
	}

	return array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}

/**
 * Output FAQPage on product pages that have questions filled in.
 */
function woostarter_product_faq_schema() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$pairs = woostarter_parse_faq( $product->get_meta( '_ws_faq' ) );
	woostarter_print_jsonld( woostarter_faq_schema( $pairs ) );
}
add_action( 'wp_head', 'woostarter_product_faq_schema', 20 );

/**
 * Output LocalBusiness (+ FAQPage) on the local-service template.
 */
function woostarter_local_service_schema() {
	if ( ! is_page_template( 'page-local-service.php' ) ) {
		return;
	}

	if ( ! function_exists( 'woostarter_get_local_service_data' ) ) {
		return;
	}

	$data = woostarter_get_local_service_data( get_the_ID() );
	if ( empty( $data['service'] ) && empty( $data['city'] ) ) {
		return;
	}

	$name = trim( $data['service'] . ' ' . $data['city'] );

	$business = array(
		'@context' => 'https://schema.org',
		'@type'    => 'LocalBusiness',
		'name'     => '' !== $name ? $name : get_bloginfo( 'name' ),
		'url'      => get_permalink(),
	);

	$logo = woostarter_logo_url();
	if ( $logo ) {
		$business['image'] = $logo;
	}
	if ( '' !== $data['phone'] ) {
		$business['telephone'] = $data['phone'];
	}
	if ( '' !== $data['price_range'] ) {
		$business['priceRange'] = $data['price_range'];
	}
	if ( ! empty( $data['area_served'] ) ) {
		$business['areaServed'] = array_map(
			static function ( $area ) {
				return array(
					'@type' => 'City',
					'name'  => $area,
				);
			},
			$data['area_served']
		);
	}

	woostarter_print_jsonld( $business );
	woostarter_print_jsonld( woostarter_faq_schema( woostarter_parse_faq( $data['faq_raw'] ) ) );
}
add_action( 'wp_head', 'woostarter_local_service_schema', 20 );

/**
 * Alternate-language links for future multilingual rollouts.
 *
 * Empty by default; the client (or a translation plugin) fills the map through
 * the filter, e.g. array( 'en' => 'https://example.com/en/...' ).
 */
function woostarter_hreflang_links() {
	$alternates = apply_filters( 'woostarter_hreflang_alternates', array() );
	if ( ! is_array( $alternates ) || empty( $alternates ) ) {
		return;
	}

	foreach ( $alternates as $lang => $url ) {
		printf(
			'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
			esc_attr( $lang ),
			esc_url( $url )
		);
	}
}
add_action( 'wp_head', 'woostarter_hreflang_links', 21 );

/**
 * Open Graph, Twitter Card and canonical tags (fallback only).
 *
 * Rank Math emits richer versions of these when active, so this fallback keeps
 * shares looking correct on installs without an SEO plugin.
 */
function woostarter_social_meta() {
	if ( woostarter_rank_math_active() || is_admin() ) {
		return;
	}

	$title       = wp_get_document_title();
	$url         = home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) );
	$description = get_bloginfo( 'description' );
	$image       = woostarter_logo_url();

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$description = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
			$image_id    = $product->get_image_id();
			if ( $image_id ) {
				$src = wp_get_attachment_image_src( $image_id, 'large' );
				if ( $src ) {
					$image = $src[0];
				}
			}
		}
	}

	if ( is_singular() ) {
		$url = get_permalink();
	}

	printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( is_singular() ? 'article' : 'website' ) );
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );

	if ( '' !== $description ) {
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	}
	if ( $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	} else {
		echo '<meta name="twitter:card" content="summary" />' . "\n";
	}
}
add_action( 'wp_head', 'woostarter_social_meta', 5 );
