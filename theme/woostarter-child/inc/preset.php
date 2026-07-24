<?php
/**
 * Preset selection and settings page.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the available visual presets.
 *
 * @return array<string, string>
 */
function woostarter_get_presets() {
	return array(
		'craft'   => __( 'Rzemiosło', 'woostarter' ),
		'service' => __( 'Usługi', 'woostarter' ),
		'premium' => __( 'Premium', 'woostarter' ),
	);
}

/**
 * Validate a visual preset identifier.
 *
 * @param string $preset Preset identifier.
 * @return string
 */
function woostarter_sanitize_preset( $preset ) {
	$preset  = sanitize_key( $preset );
	$presets = woostarter_get_presets();

	return array_key_exists( $preset, $presets ) ? $preset : 'service';
}

/**
 * Return the active preset identifier.
 *
 * The wp-config.php constant takes precedence over the saved option.
 *
 * @return string
 */
function woostarter_get_active_preset() {
	$preset = defined( 'WOOSTARTER_PRESET' )
		? WOOSTARTER_PRESET
		: get_option( 'WOOSTARTER_PRESET', 'service' );
	$preset = apply_filters( 'woostarter_active_preset', $preset );

	return woostarter_sanitize_preset( $preset );
}

/**
 * Return the active preset stylesheet when it exists.
 *
 * @return string
 */
function woostarter_get_active_preset_stylesheet() {
	$preset = woostarter_get_active_preset();
	if ( ! $preset ) {
		return '';
	}

	$relative_path = 'assets/css/presets/' . $preset . '.css';
	$absolute_path = get_stylesheet_directory() . '/' . $relative_path;

	return file_exists( $absolute_path ) ? $relative_path : '';
}

/**
 * Register the preset option.
 */
function woostarter_register_preset_setting() {
	register_setting(
		'woostarter_settings',
		'WOOSTARTER_PRESET',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'woostarter_sanitize_preset',
			'default'           => 'service',
		)
	);
}
add_action( 'admin_init', 'woostarter_register_preset_setting' );

/**
 * Register the theme settings page.
 */
function woostarter_add_settings_page() {
	add_theme_page(
		__( 'Woo Starter', 'woostarter' ),
		__( 'Woo Starter', 'woostarter' ),
		'manage_options',
		'woostarter-settings',
		'woostarter_render_settings_page'
	);
}
add_action( 'admin_menu', 'woostarter_add_settings_page' );

/**
 * Render the theme settings page.
 */
function woostarter_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_locked = defined( 'WOOSTARTER_PRESET' );
	$active    = woostarter_get_active_preset();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Woo Starter', 'woostarter' ); ?></h1>
		<?php if ( $is_locked ) : ?>
			<div class="notice notice-info inline">
				<p><?php echo esc_html__( 'Preset jest ustawiony przez stałą WOOSTARTER_PRESET w pliku wp-config.php.', 'woostarter' ); ?></p>
			</div>
		<?php endif; ?>
		<form action="options.php" method="post">
			<?php settings_fields( 'woostarter_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="woostarter-preset"><?php echo esc_html__( 'Preset wizualny', 'woostarter' ); ?></label>
					</th>
					<td>
						<select
							id="woostarter-preset"
							name="WOOSTARTER_PRESET"
							<?php disabled( $is_locked ); ?>
						>
							<?php foreach ( woostarter_get_presets() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $active, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php echo esc_html__( 'Wybierz wygląd sklepu. Zmiana nie modyfikuje treści ani produktów.', 'woostarter' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php if ( ! $is_locked ) : ?>
				<?php submit_button( __( 'Zapisz preset', 'woostarter' ) ); ?>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
