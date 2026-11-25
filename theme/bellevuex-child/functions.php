<?php
/**
 * Bellevue child theme (Neatbnb).
 *
 * The production child theme carried 240 lines of AJAX handlers, a
 * hardcoded property map, and an inline login form. All of it now
 * lives in the Neatbnb Operations plugin (plugin/neatbnb-ops), which
 * is where contributors should look. This file only enqueues the
 * parent + child stylesheets and defines theme supports.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_enqueue_scripts', 'bellevuex_child_enqueue_styles', 20 );
function bellevuex_child_enqueue_styles() {
	wp_enqueue_style( 'bellevuex-parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'bellevuex-child-style', get_stylesheet_directory_uri() . '/style.css', array( 'bellevuex-parent-style' ), wp_get_theme()->get( 'Version' ) );
}
