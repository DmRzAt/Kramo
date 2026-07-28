<?php
/**
 * Storefront footer.
 *
 * Four columns — brand, shop, help, contact — on a dark floor under the warm
 * paper storefront. The structure mirrors the Polish portfolio footers clients
 * recognise; the copy and links stay Kramo.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact details shown in the footer, overridable per install.
 *
 * @return array{email:string,phone:string,hours:string,city:string}
 */
function kramo_footer_contact() {
	$city = '';

	if ( function_exists( 'WC' ) && WC()->countries ) {
		$base = WC()->countries->get_base_city();
		$city = is_string( $base ) ? $base : '';
	}

	if ( '' === $city ) {
		$city = __( 'Polska', 'kramo' );
	} else {
		$city = sprintf(
			/* translators: %s: store city. */
			__( '%s, Polska', 'kramo' ),
			$city
		);
	}

	$defaults = array(
		'email' => (string) get_option( 'admin_email' ),
		'phone' => '',
		'hours' => __( 'Pon.–Pt. 9:00–17:00', 'kramo' ),
		'city'  => $city,
	);

	if ( function_exists( 'kramo_is_demo' ) && kramo_is_demo() ) {
		$defaults['phone'] = '+48 500 600 700';
		$defaults['city']  = __( 'Kraków, Polska', 'kramo' );
	}

	$contact = apply_filters( 'kramo_footer_contact', $defaults );

	return array(
		'email' => isset( $contact['email'] ) ? sanitize_email( $contact['email'] ) : '',
		'phone' => isset( $contact['phone'] ) ? sanitize_text_field( $contact['phone'] ) : '',
		'hours' => isset( $contact['hours'] ) ? sanitize_text_field( $contact['hours'] ) : '',
		'city'  => isset( $contact['city'] ) ? sanitize_text_field( $contact['city'] ) : '',
	);
}

/**
 * Short brand sentence under the footer logo.
 *
 * @return string
 */
function kramo_footer_tagline() {
	$description = get_bloginfo( 'description', 'display' );

	if ( is_string( $description ) && '' !== trim( $description ) ) {
		return $description;
	}

	return __( 'Polskie rzemiosło na co dzień — naturalne materiały, prosty krój i wysyłka bez ryzyka.', 'kramo' );
}

/**
 * Permalink for a page by slug, empty when the page is missing.
 *
 * @param string $slug Page slug.
 * @return string
 */
function kramo_footer_page_url( $slug ) {
	$page = get_page_by_path( $slug );

	if ( ! $page instanceof WP_Post ) {
		return '';
	}

	$permalink = get_permalink( $page );

	return is_string( $permalink ) ? $permalink : '';
}

/**
 * Shop column links: shop page, product categories, wishlist.
 *
 * @return array<int, array{label:string,url:string}>
 */
function kramo_footer_shop_links() {
	$links = array();

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( is_string( $shop ) && '' !== $shop ) {
			$links[] = array(
				'label' => __( 'Sklep', 'kramo' ),
				'url'   => $shop,
			);
		}
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			'number'     => 6,
		)
	);

	if ( ! is_wp_error( $categories ) ) {
		foreach ( $categories as $term ) {
			$url = get_term_link( $term );
			if ( is_wp_error( $url ) ) {
				continue;
			}

			$links[] = array(
				'label' => $term->name,
				'url'   => $url,
			);
		}
	}

	$wishlist = kramo_footer_page_url( 'ulubione' );
	if ( '' !== $wishlist ) {
		$links[] = array(
			'label' => __( 'Ulubione', 'kramo' ),
			'url'   => $wishlist,
		);
	}

	return apply_filters( 'kramo_footer_shop_links', $links );
}

/**
 * Help column links: shipping, returns, legal pages.
 *
 * @return array<int, array{label:string,url:string}>
 */
function kramo_footer_help_links() {
	$spec = array(
		'dostawa-i-platnosci'            => __( 'Dostawa i płatności', 'kramo' ),
		'zwroty-i-reklamacje'            => __( 'Zwroty i reklamacje', 'kramo' ),
		'regulamin'                      => __( 'Regulamin', 'kramo' ),
		'polityka-prywatnosci'           => __( 'Polityka prywatności', 'kramo' ),
		'polityka-cookies'               => __( 'Polityka cookies', 'kramo' ),
	);

	$links = array();

	foreach ( $spec as $slug => $label ) {
		$url = kramo_footer_page_url( $slug );
		if ( '' === $url ) {
			continue;
		}

		$links[] = array(
			'label' => $label,
			'url'   => $url,
		);
	}

	if ( function_exists( 'kramo_is_demo' ) && kramo_is_demo() ) {
		$service = kramo_footer_page_url( 'mycie-kostki-brukowej-katowice' );
		if ( '' !== $service ) {
			$links[] = array(
				'label' => __( 'Przykład strony usługowej', 'kramo' ),
				'url'   => $service,
			);
		}
	}

	return apply_filters( 'kramo_footer_help_links', $links );
}

/**
 * Print a footer link list.
 *
 * @param array<int, array{label:string,url:string}> $links Column links.
 */
function kramo_render_footer_links( $links ) {
	if ( ! $links ) {
		return;
	}

	echo '<ul class="kramo-site-footer__list">';

	foreach ( $links as $link ) {
		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( $link['url'] ),
			esc_html( $link['label'] )
		);
	}

	echo '</ul>';
}

/**
 * Replace GeneratePress's default footer with the Kramo columns.
 */
function kramo_replace_default_footer() {
	remove_action( 'generate_footer', 'generate_construct_footer' );
}
add_action( 'wp', 'kramo_replace_default_footer' );

/**
 * Render the storefront footer.
 */
function kramo_render_site_footer() {
	$contact   = kramo_footer_contact();
	$shop      = kramo_footer_shop_links();
	$help      = kramo_footer_help_links();
	$brand_svg = function_exists( 'kramo_logo_markup' ) ? kramo_logo_markup() : '';
	$name      = get_bloginfo( 'name' );
	?>
	<div class="kramo-site-footer">
		<div class="kramo-site-footer__inner">
			<div class="kramo-site-footer__grid">
				<div class="kramo-site-footer__brand">
					<?php if ( '' !== $brand_svg ) : ?>
						<a class="kramo-site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( $name ); ?>">
							<?php
							echo wp_kses(
								$brand_svg,
								function_exists( 'kramo_logo_kses_allowed_html' )
									? kramo_logo_kses_allowed_html()
									: array()
							);
							?>
						</a>
					<?php else : ?>
						<a class="kramo-site-footer__name" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php echo esc_html( $name ); ?>
						</a>
					<?php endif; ?>
					<p class="kramo-site-footer__tagline"><?php echo esc_html( kramo_footer_tagline() ); ?></p>
				</div>

				<?php if ( $shop ) : ?>
					<nav class="kramo-site-footer__col" aria-label="<?php echo esc_attr__( 'Sklep', 'kramo' ); ?>">
						<h2 class="kramo-site-footer__heading"><?php echo esc_html__( 'Sklep', 'kramo' ); ?></h2>
						<?php kramo_render_footer_links( $shop ); ?>
					</nav>
				<?php endif; ?>

				<?php if ( $help ) : ?>
					<nav class="kramo-site-footer__col" aria-label="<?php echo esc_attr__( 'Pomoc', 'kramo' ); ?>">
						<h2 class="kramo-site-footer__heading"><?php echo esc_html__( 'Pomoc', 'kramo' ); ?></h2>
						<?php kramo_render_footer_links( $help ); ?>
					</nav>
				<?php endif; ?>

				<div class="kramo-site-footer__col">
					<h2 class="kramo-site-footer__heading"><?php echo esc_html__( 'Kontakt', 'kramo' ); ?></h2>
					<ul class="kramo-site-footer__list kramo-site-footer__list--contact">
						<?php if ( '' !== $contact['email'] ) : ?>
							<li>
								<a href="<?php echo esc_url( 'mailto:' . $contact['email'] ); ?>">
									<?php echo esc_html( $contact['email'] ); ?>
								</a>
							</li>
						<?php endif; ?>
						<?php if ( '' !== $contact['phone'] ) : ?>
							<li>
								<a href="<?php echo esc_url( 'tel:' . preg_replace( '/\s+/', '', $contact['phone'] ) ); ?>">
									<?php echo esc_html( $contact['phone'] ); ?>
								</a>
							</li>
						<?php endif; ?>
						<?php if ( '' !== $contact['hours'] ) : ?>
							<li><?php echo esc_html( $contact['hours'] ); ?></li>
						<?php endif; ?>
						<?php if ( '' !== $contact['city'] ) : ?>
							<li><?php echo esc_html( $contact['city'] ); ?></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>

			<div class="kramo-site-footer__bar">
				<p class="kramo-site-footer__copy">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: current year, 2: site name. */
							__( '© %1$s %2$s.', 'kramo' ),
							gmdate( 'Y' ),
							$name
						)
					);
					?>
				</p>
				<?php if ( function_exists( 'kramo_is_demo' ) && kramo_is_demo() ) : ?>
					<p class="kramo-site-footer__note">
						<?php echo esc_html__( 'Kramo jest projektem demonstracyjnym przygotowanym do portfolio.', 'kramo' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'generate_footer', 'kramo_render_site_footer', 10 );
