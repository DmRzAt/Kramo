<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kramo_trust_icon( $name ) {
	$icons = array(
		'card'   => '<rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20"/>',
		'locker' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 8h8M8 12h8M8 16h4"/>',
		'truck'  => '<path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
		'return' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>',
		'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6z"/>',
	);

	if ( empty( $icons[ $name ] ) ) {
		return '';
	}

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $icons[ $name ] . '</svg>';
}

function kramo_trust_items() {
	$threshold = 200;

	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'free_shipping' === $method->id && ! empty( $method->min_amount ) ) {
					$threshold = (float) $method->min_amount;
				}
			}
		}
	}

	return apply_filters(
		'kramo_trust_items',
		array(
			array(
				'icon'   => 'card',
				'strong' => __( 'BLIK', 'kramo' ),
				'text'   => __( 'i karta', 'kramo' ),
			),
			array(
				'icon'   => 'locker',
				'strong' => __( 'Paczkomat InPost', 'kramo' ),
				'text'   => __( 'i Orlen Paczka', 'kramo' ),
			),
			array(
				'icon'   => 'truck',
				'strong' => function_exists( 'wc_price' )
					? wp_strip_all_tags( wc_price( $threshold, array( 'decimals' => 0 ) ) )
					: $threshold . ' zł',
				'text'   => __( 'darmowa dostawa od', 'kramo' ),
				'before' => true,
			),
			array(
				'icon'   => 'return',
				'strong' => __( '14 dni', 'kramo' ),
				'text'   => __( 'na zwrot', 'kramo' ),
			),
			array(
				'icon'   => 'shield',
				'strong' => __( 'Polska', 'kramo' ),
				'text'   => __( 'pracownia', 'kramo' ),
			),
		)
	);
}

function kramo_render_trust_strip() {
	if ( is_admin() ) {
		return;
	}

	$items = kramo_trust_items();
	if ( empty( $items ) ) {
		return;
	}

	echo '<div class="kramo-trust"><ul class="kramo-trust__list">';

	foreach ( $items as $item ) {
		$icon   = kramo_trust_icon( $item['icon'] );
		$strong = '<strong>' . esc_html( $item['strong'] ) . '</strong>';
		$text   = esc_html( $item['text'] );
		$body   = empty( $item['before'] ) ? $strong . ' ' . $text : $text . ' ' . $strong;

		printf(
			'<li class="kramo-trust__item">%s<span>%s</span></li>',
			wp_kses( $icon, array( 'svg' => array( 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'aria-hidden' => true, 'focusable' => true ), 'path' => array( 'd' => true ), 'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ), 'circle' => array( 'cx' => true, 'cy' => true, 'r' => true ) ) ),
			wp_kses( $body, array( 'strong' => array() ) )
		);
	}

	echo '</ul></div>';
}
add_action( 'generate_after_header', 'kramo_render_trust_strip', 5 );
