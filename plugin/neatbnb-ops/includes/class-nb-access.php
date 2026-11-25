<?php
/**
 * Role-based page access, per-role home redirects, and the "Go Back" link.
 *
 * Generalized from WPCode snippet "Link by role" (hardcoded
 * absolute URLs → page slugs from settings).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Access {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'restrict_page_access' ), 5 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_user_home' ), 5 );
		add_shortcode( 'go_back_link', array( __CLASS__, 'go_back_link' ) );
	}

	/** Roles that may access every restricted dashboard. */
	private static function unrestricted_roles() {
		return apply_filters( 'nb_unrestricted_roles', array( 'administrator', 'manager' ) );
	}

	/**
	 * Map of page slug => roles denied (everyone else, incl. guests,
	 * is allowed unless denied). Guests hitting a restricted page go
	 * to wp-login.php first.
	 */
	private static function restricted_pages() {
		$opts = NB_Settings::get();
		return apply_filters( 'nb_restricted_pages', array(
			$opts['host_page']        => array( 'cleaning_operations', 'maintanace_operations' ),
			$opts['cleaning_page']    => array( 'host' ),
			$opts['maintenance_page'] => array( 'host' ),
		) );
	}

	/** slug => redirect target slug for each role's homepage. */
	private static function role_home_map() {
		$opts = NB_Settings::get();
		return apply_filters( 'nb_role_home_map', array(
			'cleaning_operations' => $opts['cleaning_page'],
			'maintanace_operations' => $opts['maintenance_page'],
			'host'                => $opts['host_page'],
		) );
	}

	public static function restrict_page_access() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$restricted = self::restricted_pages();
		$queried    = get_queried_object();
		if ( ! $queried || 'page' !== $queried->post_type ) {
			return;
		}
		$slug = $queried->post_name;
		if ( ! isset( $restricted[ $slug ] ) ) {
			return;
		}

		$user = wp_get_current_user();
		$roles = (array) $user->roles;

		if ( array_intersect( self::unrestricted_roles(), $roles ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( get_permalink() ) );
			exit;
		}

		if ( array_intersect( (array) $restricted[ $slug ], $roles ) ) {
			wp_die(
				esc_html__( 'Sorry, you are unauthorized to access this page.', 'neatbnb-ops' ),
				esc_html__( 'Unauthorized Access', 'neatbnb-ops' ),
				array( 'response' => 403 )
			);
		}
	}

	/** Send logged-in non-admin users to their own dashboard when they hit the front page. */
	public static function redirect_user_home() {
		if ( ! is_front_page() || ! is_user_logged_in() ) {
			return;
		}
		$user  = wp_get_current_user();
		$roles = (array) $user->roles;

		if ( array_intersect( self::unrestricted_roles(), $roles ) ) {
			return;
		}

		foreach ( self::role_home_map() as $role => $slug ) {
			if ( in_array( $role, $roles, true ) && $slug ) {
				wp_safe_redirect( home_url( '/' . trim( $slug, '/' ) . '/' ) );
				exit;
			}
		}
	}

	/** [go_back_link] — link back to the current user's dashboard (front page for admins). */
	public static function go_back_link() {
		$target = home_url( '/' );
		if ( is_user_logged_in() ) {
			$user  = wp_get_current_user();
			$roles = (array) $user->roles;
			if ( ! array_intersect( self::unrestricted_roles(), $roles ) ) {
				foreach ( self::role_home_map() as $role => $slug ) {
					if ( in_array( $role, $roles, true ) && $slug ) {
						$target = home_url( '/' . trim( $slug, '/' ) . '/' );
						break;
					}
				}
			}
		}
		return sprintf(
			'<a href="%s" class="custom-back-button">%s</a>',
			esc_url( $target ),
			esc_html__( 'Go Back', 'neatbnb-ops' )
		);
	}
}
