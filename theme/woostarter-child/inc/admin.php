<?php
/**
 * Client-focused administration customizations.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove dashboard widgets that are not useful to store editors.
 */
function woostarter_remove_dashboard_widgets() {
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}
add_action( 'wp_dashboard_setup', 'woostarter_remove_dashboard_widgets', 100 );

/**
 * Register the Polish product guide dashboard widget.
 */
function woostarter_register_product_guide_widget() {
	wp_add_dashboard_widget(
		'woostarter_product_guide',
		__( 'Jak dodać produkt', 'woostarter' ),
		'woostarter_render_product_guide_widget'
	);
}
add_action( 'wp_dashboard_setup', 'woostarter_register_product_guide_widget' );

/**
 * Render the product guide dashboard widget.
 */
function woostarter_render_product_guide_widget() {
	$steps = array(
		__( 'Otwórz Produkty i wybierz „Dodaj nowy”.', 'woostarter' ),
		__( 'Wpisz nazwę, opis oraz cenę produktu.', 'woostarter' ),
		__( 'Dodaj zdjęcie główne, galerię i właściwą kategorię.', 'woostarter' ),
		__( 'Sprawdź stan magazynowy, a następnie kliknij „Opublikuj”.', 'woostarter' ),
	);
	?>
	<ol>
		<?php foreach ( $steps as $step ) : ?>
			<li><?php echo esc_html( $step ); ?></li>
		<?php endforeach; ?>
	</ol>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>">
			<?php echo esc_html__( 'Dodaj produkt', 'woostarter' ); ?>
		</a>
	</p>
	<?php
}

/**
 * Hide technical menus from shop managers.
 */
function woostarter_limit_shop_manager_menu() {
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
add_action( 'admin_menu', 'woostarter_limit_shop_manager_menu', 999 );

/**
 * Replace the administration footer with configurable support details.
 *
 * Set the woostarter_support_email option to add a clickable address.
 *
 * @return string
 */
function woostarter_admin_footer_text() {
	$support_name  = __( 'Dmytro', 'woostarter' );
	$support_email = sanitize_email( get_option( 'woostarter_support_email', '' ) );

	if ( ! $support_email ) {
		return sprintf(
			esc_html__( 'Wsparcie techniczne: %s', 'woostarter' ),
			esc_html( $support_name )
		);
	}

	return sprintf(
		__( 'Wsparcie techniczne: %1$s — %2$s', 'woostarter' ),
		esc_html( $support_name ),
		sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( 'mailto:' . antispambot( $support_email ) ),
			esc_html( antispambot( $support_email ) )
		)
	);
}
add_filter( 'admin_footer_text', 'woostarter_admin_footer_text' );
