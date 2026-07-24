<?php

foreach ( glob( get_stylesheet_directory() . '/inc/*.php' ) as $file ) {
	require_once $file;
}
