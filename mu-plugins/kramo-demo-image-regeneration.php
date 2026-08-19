<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( getenv( 'KRAMO_DEMO' ) ) {
	add_filter( 'woocommerce_background_image_regeneration', '__return_false' );
}
