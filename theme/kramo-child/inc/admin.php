<?php
/**
 * Client-focused administration customizations.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove dashboard widgets that are not useful to store editors.
 */
function kramo_remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}
add_action( 'wp_dashboard_setup', 'kramo_remove_dashboard_widgets', 100 );

/**
 * Register the Polish product guide dashboard widget.
 */
function kramo_register_product_guide_widget() {
	wp_add_dashboard_widget(
		'kramo_product_guide',
		__( 'Jak dodać produkt', 'kramo' ),
		'kramo_render_product_guide_widget'
	);
}
add_action( 'wp_dashboard_setup', 'kramo_register_product_guide_widget' );

/**
 * Render the product guide dashboard widget.
 */
function kramo_render_product_guide_widget() {
	$steps = array(
		__( 'Otwórz Produkty i wybierz „Dodaj nowy”.', 'kramo' ),
		__( 'Wpisz nazwę, opis oraz cenę produktu.', 'kramo' ),
		__( 'Dodaj zdjęcie główne, galerię i właściwą kategorię.', 'kramo' ),
		__( 'Sprawdź stan magazynowy, a następnie kliknij „Opublikuj”.', 'kramo' ),
	);
	?>
	<ol>
		<?php foreach ( $steps as $step ) : ?>
			<li><?php echo esc_html( $step ); ?></li>
		<?php endforeach; ?>
	</ol>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>">
			<?php echo esc_html__( 'Dodaj produkt', 'kramo' ); ?>
		</a>
	</p>
	<?php
}

/**
 * Hide technical menus from shop managers.
 */
function kramo_limit_shop_manager_menu() {
	$user = wp_get_current_user();
	if ( ! in_array( 'shop_manager', (array) $user->roles, true ) ) {
		return;
	}

	$menu_pages = array(
		'edit.php',
		'themes.php',
		'plugins.php',
		'tools.php',
		'options-general.php',
		'rank-math',
		'updraftplus',
	);

	foreach ( $menu_pages as $menu_page ) {
		remove_menu_page( $menu_page );
	}
}
add_action( 'admin_menu', 'kramo_limit_shop_manager_menu', 999 );

/**
 * Replace the administration footer with configurable support details.
 *
 * Set the kramo_support_email option to add a clickable address.
 *
 * @return string
 */
function kramo_admin_footer_text() {
	$support_name  = __( 'Dmytro', 'kramo' );
	$support_email = sanitize_email( get_option( 'kramo_support_email', '' ) );

	if ( ! $support_email ) {
		return sprintf(
			esc_html__( 'Wsparcie techniczne: %s', 'kramo' ),
			esc_html( $support_name )
		);
	}

	return sprintf(
		__( 'Wsparcie techniczne: %1$s — %2$s', 'kramo' ),
		esc_html( $support_name ),
		sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( 'mailto:' . antispambot( $support_email ) ),
			esc_html( antispambot( $support_email ) )
		)
	);
}
add_filter( 'admin_footer_text', 'kramo_admin_footer_text' );
