<?php
/**
 * Login gate for forms marked "Require Login".
 *
 * Ported from the bellevuex-child theme: adds the requires_login
 * option to Formidable form settings and renders an inline AJAX
 * login form in front of the fields when a guest views such a form.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Login_Gate {

	public static function init() {
		add_action( 'frm_form_before_fields', array( __CLASS__, 'maybe_render' ), 10, 2 );
		add_action( 'frm_form_options_before', array( __CLASS__, 'options_ui' ), 10, 2 );
		add_filter( 'frm_form_options', array( __CLASS__, 'save_option' ) );
		// The inline form posts action=custom_login; the production site's
		// handler lived outside the exported code, so it is implemented here.
		add_action( 'wp_ajax_custom_login', array( __CLASS__, 'ajax_login' ) );
		add_action( 'wp_ajax_nopriv_custom_login', array( __CLASS__, 'ajax_login' ) );
	}

	/** AJAX login used by the inline gate form. */
	public static function ajax_login() {
		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

		if ( '' === $username || '' === $password ) {
			wp_send_json_error( array( 'message' => __( 'Please enter username and password.', 'neatbnb-ops' ) ) );
		}

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid username or password.', 'neatbnb-ops' ) ) );
		}
		wp_send_json_success( array( 'message' => __( 'Logged in.', 'neatbnb-ops' ) ) );
	}

	public static function maybe_render( $form ) {
		if ( is_user_logged_in() || empty( $form->requires_login ) ) {
			return;
		}
		?>
		<div id="custom-login-form" class="frm-login-form">
			<h3><?php esc_html_e( 'Please login to continue', 'neatbnb-ops' ); ?></h3>
			<div class="frm_form_field">
				<label for="login-username"><?php esc_html_e( 'Username', 'neatbnb-ops' ); ?></label>
				<input type="text" id="login-username" name="username" autocomplete="username" />
			</div>
			<div class="frm_form_field">
				<label for="login-password"><?php esc_html_e( 'Password', 'neatbnb-ops' ); ?></label>
				<input type="password" id="login-password" name="password" autocomplete="current-password" />
			</div>
			<button type="button" id="login-submit"><?php esc_html_e( 'Login', 'neatbnb-ops' ); ?></button>
			<div id="login-message" role="alert"></div>
		</div>
		<div id="form-fields" style="display: none;"></div>
		<script>
		jQuery(document).ready(function ($) {
			$('.frm_form_field:not(.frm-login-form .frm_form_field)').appendTo('#form-fields');

			$('#login-submit').on('click', function () {
				$.post(ajaxurl, {
					action: 'custom_login',
					username: $('#login-username').val(),
					password: $('#login-password').val()
				}, function (response) {
					if (response && response.success) {
						$('#custom-login-form').hide();
						$('#form-fields').show();
						$('#login-message').hide();
					} else {
						$('#login-message')
							.html((response && response.data && response.data.message) ? response.data.message : 'Login failed.')
							.show().css('color', 'red');
					}
				});
			});
		});
		</script>
		<style>
			.frm-login-form { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
			.frm-login-form .frm_form_field { margin-bottom: 15px; }
			.frm-login-form label { display: block; margin-bottom: 5px; }
			.frm-login-form input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
			#login-message { margin-top: 10px; display: none; }
		</style>
		<?php
	}

	/** Checkbox row in the form options screen. */
	public static function options_ui( $values, $form ) {
		?>
		<tr>
			<td>
				<label for="requires_login"><?php esc_html_e( 'Require Login', 'neatbnb-ops' ); ?></label>
			</td>
			<td>
				<input type="checkbox" name="options[requires_login]" id="requires_login" value="1" <?php checked( ! empty( $values['requires_login'] ) ); ?> />
			</td>
		</tr>
		<?php
	}

	public static function save_option( $options ) {
		// Only persist when the form options screen was actually submitted.
		if ( isset( $_POST['options'] ) && is_array( $_POST['options'] ) ) {
			$options['requires_login'] = ! empty( $_POST['options']['requires_login'] ) ? 1 : 0;
		}
		return $options;
	}
}
