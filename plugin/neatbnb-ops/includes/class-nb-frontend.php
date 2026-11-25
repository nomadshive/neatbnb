<?php
/**
 * Front-end behaviour: admin bar visibility, quiz anti-cheat,
 * GTranslate suppression, and the shared inline login widget
 * (extracted from Elementor HTML widgets across the site).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Frontend {

	public static function init() {
		// Snippet "Disable The WP Admin Bar": only administrators see it.
		add_filter( 'show_admin_bar', array( __CLASS__, 'show_admin_bar' ) );

		// Snippets "Anti-Cheat Script for Quiz Pages" + "Disable GTranslate":
		// both were conditional on the quiz page URL; now slug-driven.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		// Shared inline login widget (was copy-pasted Elementor HTML widgets).
		add_shortcode( 'nb_login_widget', array( __CLASS__, 'login_widget' ) );
		// Auto-inject on pages when requested via [nb_login_widget] only —
		// explicit placement keeps Elementor layouts in control.
	}

	public static function show_admin_bar( $show ) {
		return current_user_can( 'manage_options' );
	}

	public static function enqueue_assets() {
		$quiz_slug = trim( NB_Settings::get( 'quiz_page' ), '/' );
		$is_quiz   = $quiz_slug && is_page( $quiz_slug );

		if ( $is_quiz ) {
			wp_enqueue_style( 'nb-anticheat', NEATBNB_OPS_URL . 'assets/css/anticheat.css', array(), NEATBNB_OPS_VERSION );
			wp_enqueue_script( 'nb-anticheat', NEATBNB_OPS_URL . 'assets/js/anticheat.js', array(), NEATBNB_OPS_VERSION, true );
			wp_enqueue_style( 'nb-notranslate', NEATBNB_OPS_URL . 'assets/css/notranslate.css', array(), NEATBNB_OPS_VERSION );
			wp_enqueue_script( 'nb-notranslate', NEATBNB_OPS_URL . 'assets/js/notranslate.js', array(), NEATBNB_OPS_VERSION, true );
		}
	}

	/**
	 * [nb_login_widget redirect="/some-page/"] — inline login form that
	 * AJAX-posts to wp-login.php and hides itself when already logged in.
	 * Replaces the duplicated Elementor HTML widgets.
	 */
	public static function login_widget( $atts ) {
		$atts = shortcode_atts( array(
			'redirect' => '',
		), $atts, 'nb_login_widget' );

		$redirect = $atts['redirect'] ? $atts['redirect'] : home_url( add_query_arg( array() ) );
		$js_redirect = $redirect ? str_replace( '\\', '\\\\', $redirect ) : 'window.location.reload();';

		wp_enqueue_style( 'nb-login-widget', NEATBNB_OPS_URL . 'assets/css/login-widget.css', array(), NEATBNB_OPS_VERSION );
		wp_enqueue_script( 'nb-login-widget', NEATBNB_OPS_URL . 'assets/js/login-widget.js', array(), NEATBNB_OPS_VERSION, true );

		ob_start();
		?>
		<div id="loginContainer" data-nb-redirect="<?php echo esc_url( $redirect ); ?>">
			<form class="login-form" method="post" action="<?php echo esc_url( wp_login_url() ); ?>">
				<div class="form-group">
					<label for="user_login"><?php esc_html_e( 'Username', 'neatbnb-ops' ); ?></label>
					<input type="text" name="log" id="user_login" class="form-control" required autocomplete="username">
				</div>
				<div class="form-group">
					<label for="user_pass"><?php esc_html_e( 'Password', 'neatbnb-ops' ); ?></label>
					<input type="password" name="pwd" id="user_pass" class="form-control" required autocomplete="current-password">
				</div>
				<div class="form-group">
					<label>
						<input type="checkbox" name="rememberme" value="forever">
						<?php esc_html_e( 'Remember Me', 'neatbnb-ops' ); ?>
					</label>
				</div>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>">
				<button type="submit" name="wp-submit"><?php esc_html_e( 'Log In', 'neatbnb-ops' ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
