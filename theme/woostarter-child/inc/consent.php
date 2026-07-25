<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WOOSTARTER_CONSENT_COOKIE = 'ws_consent';

function woostarter_consent_settings() {
	return array(
		'ga4'   => (string) get_option( 'woostarter_ga4_id', '' ),
		'pixel' => (string) get_option( 'woostarter_pixel_id', '' ),
	);
}

function woostarter_register_consent_settings() {
	register_setting(
		'woostarter_settings',
		'woostarter_ga4_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'woostarter_sanitize_measurement_id',
			'default'           => '',
		)
	);

	register_setting(
		'woostarter_settings',
		'woostarter_pixel_id',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'woostarter_sanitize_measurement_id',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'woostarter_register_consent_settings' );

function woostarter_sanitize_measurement_id( $value ) {
	$value = trim( (string) $value );

	return preg_match( '/^[A-Za-z0-9\-_]{0,32}$/', $value ) ? $value : '';
}

function woostarter_consent_is_required() {
	$settings = woostarter_consent_settings();

	return '' !== $settings['ga4'] || '' !== $settings['pixel'];
}

function woostarter_enqueue_consent_assets() {
	if ( ! woostarter_consent_is_required() || is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'woostarter-consent',
		get_stylesheet_directory_uri() . '/assets/js/consent.js',
		array(),
		woostarter_asset_version( 'assets/js/consent.js' ),
		true
	);
	wp_script_add_data( 'woostarter-consent', 'strategy', 'defer' );

	wp_localize_script(
		'woostarter-consent',
		'wooStarterConsent',
		array(
			'cookieName' => WOOSTARTER_CONSENT_COOKIE,
			'cookieDays' => 180,
			'trackers'   => woostarter_consent_settings(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'woostarter_enqueue_consent_assets', 30 );

function woostarter_render_consent_banner() {
	if ( ! woostarter_consent_is_required() ) {
		return;
	}

	$privacy_url = get_privacy_policy_url();
	?>
	<div
		class="woostarter-consent"
		data-woostarter-consent
		role="dialog"
		aria-live="polite"
		aria-label="<?php echo esc_attr__( 'Zgoda na pliki cookie', 'woostarter' ); ?>"
		hidden
	>
		<p class="woostarter-consent__text">
			<?php echo esc_html__( 'Używamy plików cookie do celów statystycznych i marketingowych. Możesz je zaakceptować lub odrzucić — sklep działa tak samo.', 'woostarter' ); ?>
			<?php if ( $privacy_url ) : ?>
				<a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html__( 'Polityka prywatności', 'woostarter' ); ?></a>
			<?php endif; ?>
		</p>
		<div class="woostarter-consent__actions">
			<button type="button" class="button woostarter-consent__accept" data-consent-accept>
				<?php echo esc_html__( 'Akceptuję', 'woostarter' ); ?>
			</button>
			<button type="button" class="button woostarter-consent__reject" data-consent-reject>
				<?php echo esc_html__( 'Odrzucam', 'woostarter' ); ?>
			</button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'woostarter_render_consent_banner', 5 );
