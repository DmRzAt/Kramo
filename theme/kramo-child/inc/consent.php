<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KRAMO_CONSENT_COOKIE = 'ws_consent';

function kramo_consent_settings() {
	return array(
		'ga4'   => (string) get_option( 'kramo_ga4_id', '' ),
		'pixel' => (string) get_option( 'kramo_pixel_id', '' ),
	);
}

function kramo_register_consent_settings() {
	register_setting(
		'kramo_settings',
		'kramo_ga4_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'kramo_sanitize_measurement_id',
			'default'           => '',
		)
	);

	register_setting(
		'kramo_settings',
		'kramo_pixel_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'kramo_sanitize_measurement_id',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'kramo_register_consent_settings' );

function kramo_sanitize_measurement_id( $value ) {
	$value = trim( (string) $value );

	return preg_match( '/^[A-Za-z0-9\-_]{0,32}$/', $value ) ? $value : '';
}

function kramo_consent_is_required() {
	$settings = kramo_consent_settings();

	return '' !== $settings['ga4'] || '' !== $settings['pixel'];
}

function kramo_enqueue_consent_assets() {
	if ( ! kramo_consent_is_required() || is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'kramo-consent',
		get_stylesheet_directory_uri() . '/assets/js/consent.js',
		array(),
		kramo_asset_version( 'assets/js/consent.js' ),
		true
	);
	wp_script_add_data( 'kramo-consent', 'strategy', 'defer' );

	wp_localize_script(
		'kramo-consent',
		'kramoConsent',
		array(
			'cookieName' => KRAMO_CONSENT_COOKIE,
			'cookieDays' => 180,
			'trackers'   => kramo_consent_settings(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'kramo_enqueue_consent_assets', 30 );

function kramo_render_consent_banner() {
	if ( ! kramo_consent_is_required() ) {
		return;
	}

	$privacy_url = get_privacy_policy_url();
	?>
	<div
		class="kramo-consent"
		data-kramo-consent
		role="dialog"
		aria-live="polite"
		aria-label="<?php echo esc_attr__( 'Zgoda na pliki cookie', 'kramo' ); ?>"
		hidden
	>
		<p class="kramo-consent__text">
			<?php echo esc_html__( 'Używamy plików cookie do celów statystycznych i marketingowych. Możesz je zaakceptować lub odrzucić — sklep działa tak samo.', 'kramo' ); ?>
			<?php if ( $privacy_url ) : ?>
				<a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html__( 'Polityka prywatności', 'kramo' ); ?></a>
			<?php endif; ?>
		</p>
		<div class="kramo-consent__actions">
			<button type="button" class="button kramo-consent__accept" data-consent-accept>
				<?php echo esc_html__( 'Akceptuję', 'kramo' ); ?>
			</button>
			<button type="button" class="button kramo-consent__reject" data-consent-reject>
				<?php echo esc_html__( 'Odrzucam', 'kramo' ); ?>
			</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'kramo_render_consent_banner', 5 );
