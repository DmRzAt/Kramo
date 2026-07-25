<?php
/**
 * Preset selection and settings page.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the available visual presets.
 *
 * @return array<string, string>
 */
function kramo_get_presets() {
	return array(
		'craft'   => __( 'Rzemiosło', 'kramo' ),
		'service' => __( 'Usługi', 'kramo' ),
		'premium' => __( 'Premium', 'kramo' ),
	);
}

/**
 * Validate a visual preset identifier.
 *
 * @param string $preset Preset identifier.
 * @return string
 */
function kramo_sanitize_preset( $preset ) {
	$preset  = sanitize_key( $preset );
	$presets = kramo_get_presets();

	return array_key_exists( $preset, $presets ) ? $preset : 'service';
}

/**
 * Return the active preset identifier.
 *
 * The wp-config.php constant takes precedence over the saved option.
 *
 * @return string
 */
function kramo_get_active_preset() {
	$preset = defined( 'KRAMO_PRESET' )
		? KRAMO_PRESET
		: get_option( 'KRAMO_PRESET', 'service' );
	$preset = apply_filters( 'kramo_active_preset', $preset );

	return kramo_sanitize_preset( $preset );
}

/**
 * Return the active preset stylesheet when it exists.
 *
 * @return string
 */
function kramo_get_active_preset_stylesheet() {
	$preset = kramo_get_active_preset();
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
function kramo_register_preset_setting() {
	register_setting(
		'kramo_settings',
		'KRAMO_PRESET',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'kramo_sanitize_preset',
			'default'           => 'service',
		)
	);
}
add_action( 'admin_init', 'kramo_register_preset_setting' );

/**
 * Register the theme settings page.
 */
function kramo_add_settings_page() {
	add_theme_page(
		__( 'Kramo', 'kramo' ),
		__( 'Kramo', 'kramo' ),
		'manage_options',
		'kramo-settings',
		'kramo_render_settings_page'
	);
}
add_action( 'admin_menu', 'kramo_add_settings_page' );

/**
 * Render the theme settings page.
 */
function kramo_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_locked = defined( 'KRAMO_PRESET' );
	$active    = kramo_get_active_preset();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Kramo', 'kramo' ); ?></h1>
		<?php if ( $is_locked ) : ?>
			<div class="notice notice-info inline">
				<p><?php echo esc_html__( 'Preset jest ustawiony przez stałą KRAMO_PRESET w pliku wp-config.php.', 'kramo' ); ?></p>
			</div>
		<?php endif; ?>
		<form action="options.php" method="post">
			<?php settings_fields( 'kramo_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="kramo-preset"><?php echo esc_html__( 'Preset wizualny', 'kramo' ); ?></label>
					</th>
					<td>
						<select
							id="kramo-preset"
							name="KRAMO_PRESET"
							<?php disabled( $is_locked ); ?>
						>
							<?php foreach ( kramo_get_presets() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $active, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php echo esc_html__( 'Wybierz wygląd sklepu. Zmiana nie modyfikuje treści ani produktów.', 'kramo' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="kramo-ga4"><?php echo esc_html__( 'Google Analytics 4', 'kramo' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="kramo-ga4"
							name="kramo_ga4_id"
							class="regular-text"
							placeholder="G-XXXXXXXXXX"
							value="<?php echo esc_attr( get_option( 'kramo_ga4_id', '' ) ); ?>"
						>
						<p class="description">
							<?php echo esc_html__( 'Zostaw puste, aby nie zbierać statystyk. Skrypt ładuje się dopiero po zgodzie na cookie.', 'kramo' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="kramo-pixel"><?php echo esc_html__( 'Meta Pixel', 'kramo' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="kramo-pixel"
							name="kramo_pixel_id"
							class="regular-text"
							value="<?php echo esc_attr( get_option( 'kramo_pixel_id', '' ) ); ?>"
						>
						<p class="description">
							<?php echo esc_html__( 'Identyfikator pikselu Meta. Również ładowany dopiero po zgodzie.', 'kramo' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<?php if ( ! $is_locked ) : ?>
				<?php submit_button( __( 'Zapisz ustawienia', 'kramo' ) ); ?>
			<?php else : ?>
				<?php submit_button( __( 'Zapisz ustawienia', 'kramo' ) ); ?>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
