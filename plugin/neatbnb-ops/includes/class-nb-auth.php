<?php
/**
 * Authentication helpers: /logout endpoint, front-end password change,
 * login redirect to the account page, and the /account popup login.
 *
 * Replaces WPCode snippets "Logout", "Change Password" and
 * "Popup Login on /account/ if Not Logged In" (the latter shipped
 * corrupted — a broken `login_redadd_action` string — and is
 * rewritten cleanly here).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Auth {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'logout_endpoint' ), 1 );
		add_shortcode( 'password_change_form', array( __CLASS__, 'password_change_shortcode' ) );
		add_action( 'template_redirect', array( __CLASS__, 'account_popup' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
	}

	/** Visiting /logout logs out and returns to the front page. */
	public static function logout_endpoint() {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( rtrim( $request, '/' ) !== '/logout' ) {
			return;
		}
		wp_logout();
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	/** [password_change_form] */
	public static function password_change_shortcode() {
		ob_start();
		self::render_password_form();
		self::handle_password_change();
		return ob_get_clean();
	}

	private static function render_password_form() {
		if ( ! is_user_logged_in() ) {
			echo '<p class="error">' . esc_html__( 'You must be logged in to change your password.', 'neatbnb-ops' ) . '</p>';
			return;
		}
		?>
		<style>
			.password-change-form { max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; background-color: #f9f9f9; }
			.password-change-form label { display: block; margin-bottom: 8px; font-weight: bold; }
			.password-change-form input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
			.password-change-form input[type="submit"] { width: 100%; padding: 10px; background-color: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
			.password-change-form input[type="submit"]:hover { background-color: #005f8d; }
			.password-change-form .error, .password-change-form .nb-ok { margin-bottom: 15px; }
			.password-change-form .error { color: red; }
			.password-change-form .nb-ok { color: green; }
		</style>
		<form method="post" action="" class="password-change-form">
			<?php wp_nonce_field( 'nb_change_password', 'nb_password_nonce' ); ?>
			<label for="current_password"><?php esc_html_e( 'Current Password', 'neatbnb-ops' ); ?></label>
			<input type="password" id="current_password" name="current_password" required />

			<label for="new_password"><?php esc_html_e( 'New Password', 'neatbnb-ops' ); ?></label>
			<input type="password" id="new_password" name="new_password" required />

			<label for="confirm_password"><?php esc_html_e( 'Confirm New Password', 'neatbnb-ops' ); ?></label>
			<input type="password" id="confirm_password" name="confirm_password" required />

			<input type="submit" name="change_password" value="<?php esc_attr_e( 'Change Password', 'neatbnb-ops' ); ?>" />
		</form>
		<?php
	}

	private static function handle_password_change() {
		if ( empty( $_POST['change_password'] ) || ! is_user_logged_in() ) {
			return;
		}
		if ( ! isset( $_POST['nb_password_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nb_password_nonce'] ), 'nb_change_password' ) ) {
			echo '<p class="error">' . esc_html__( 'Security check failed, please retry.', 'neatbnb-ops' ) . '</p>';
			return;
		}

		$user_id = get_current_user_id();
		$current = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : '';
		$new     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
		$confirm = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		if ( ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
			echo '<p class="error">' . esc_html__( 'The current password is incorrect.', 'neatbnb-ops' ) . '</p>';
			return;
		}
		if ( $new !== $confirm ) {
			echo '<p class="error">' . esc_html__( 'The new password and confirm password do not match.', 'neatbnb-ops' ) . '</p>';
			return;
		}
		if ( strlen( $new ) < 8 ) {
			echo '<p class="error">' . esc_html__( 'The new password must be at least 8 characters.', 'neatbnb-ops' ) . '</p>';
			return;
		}

		wp_set_password( $new, $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		echo '<p class="nb-ok">' . esc_html__( 'Password changed successfully!', 'neatbnb-ops' ) . '</p>';
	}

	/** On the account page, logged-out visitors get a login modal instead of content. */
	public static function account_popup() {
		$opts = NB_Settings::get();
		if ( ! is_page( $opts['account_page'] ) || is_user_logged_in() ) {
			return;
		}
		add_filter( 'the_content', '__return_empty_string' );
		add_action( 'wp_footer', array( __CLASS__, 'render_account_modal' ) );
	}

	public static function render_account_modal() {
		?>
		<div id="neatbnb-login-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:9999;">
			<div style="max-width:400px; margin:10% auto; background:#fff; padding:25px; border-radius:10px; position:relative; box-shadow:0 0 15px rgba(0,0,0,0.3);">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Login to Continue', 'neatbnb-ops' ); ?></h2>
				<?php
				wp_login_form( array(
					'redirect' => home_url( '/' . trim( NB_Settings::get( 'account_page' ), '/' ) . '/' ),
				) );
				?>
				<button id="close-login-modal" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:18px; cursor:pointer;" aria-label="<?php esc_attr_e( 'Close', 'neatbnb-ops' ); ?>">&times;</button>
			</div>
		</div>
		<script>
			document.addEventListener('DOMContentLoaded', function () {
				var modal = document.getElementById('neatbnb-login-modal');
				if (!modal) return;
				modal.style.display = 'block';
				document.getElementById('close-login-modal').addEventListener('click', function () {
					modal.style.display = 'none';
				});
			});
		</script>
		<?php
	}

	/** All logins land on the account page unless the flow specified another target. */
	public static function login_redirect( $redirect_to, $request, $user ) {
		if ( is_a( $user, 'WP_User' ) && $user->has_cap( 'read' ) ) {
			return home_url( '/' . trim( NB_Settings::get( 'account_page' ), '/' ) . '/' );
		}
		return $redirect_to;
	}
}
