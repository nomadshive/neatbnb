<?php
/**
 * Plugin Name:       Neatbnb Operations
 * Plugin URI:        https://github.com/neatbnb/neatbnb
 * Description:       Operations platform for short-term rental management: property managers (hosts), platform administration, and cleaning company workflows. Consolidates the custom logic formerly kept in WPCode snippets and a child theme.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Neatbnb Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       neatbnb-ops
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEATBNB_OPS_VERSION', '1.0.0' );
define( 'NEATBNB_OPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'NEATBNB_OPS_URL', plugin_dir_url( __FILE__ ) );

require_once NEATBNB_OPS_DIR . 'includes/class-nb-settings.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-access.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-auth.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-shortcodes.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-telegram.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-tasks.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-login-gate.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-frontend.php';
require_once NEATBNB_OPS_DIR . 'includes/class-nb-settlement.php';

/**
 * Register the financial_settlement custom post type.
 *
 * The production site uses this post type (created per host by
 * NB_Settlement) but it was never registered in code — posts existed
 * only as orphaned rows. Registering it here makes them manageable
 * in wp-admin and queryable via WP_Query.
 */
function nb_register_post_types() {
	register_post_type( 'financial_settlement', array(
		'labels' => array(
			'name'          => __( 'Financial Settlements', 'neatbnb-ops' ),
			'singular_name' => __( 'Financial Settlement', 'neatbnb-ops' ),
		),
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-money',
		'supports'     => array( 'title', 'author' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	) );
}
add_action( 'init', 'nb_register_post_types' );

/**
 * Bootstrap all modules.
 */
function nb_bootstrap() {
	NB_Settings::init();
	NB_Access::init();
	NB_Auth::init();
	NB_Shortcodes::init();
	NB_Telegram::init();
	NB_Tasks::init();
	NB_Login_Gate::init();
	NB_Frontend::init();
	NB_Settlement::init();
}
add_action( 'plugins_loaded', 'nb_bootstrap' );
