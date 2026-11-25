<?php
/**
 * Front-end shortcodes: finance link, verification status, Telegram
 * channel status, admin CSV upload form.
 *
 * From WPCode snippets "Financial Data on Google Sheets",
 * "Displaying verification status", "Displaying Telegram Channel
 * Status" and "Upload CSV for Finance (Admins)".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Shortcodes {

	public static function init() {
		add_shortcode( 'user_finance_link', array( __CLASS__, 'finance_link' ) );
		add_shortcode( 'user_meta', array( __CLASS__, 'user_meta_status' ) );
		add_shortcode( 'telegram_channel', array( __CLASS__, 'telegram_channel_status' ) );
		add_shortcode( 'admin_host_financial_upload', array( __CLASS__, 'csv_upload_form' ) );
	}

	/** [user_finance_link] — ACF field user_finance_link on the current user. */
	public static function finance_link() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return esc_html__( 'No financial data available.', 'neatbnb-ops' );
		}
		$link = function_exists( 'get_field' ) ? get_field( 'user_finance_link', 'user_' . $user_id ) : '';
		if ( $link ) {
			return sprintf(
				'<a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $link ),
				esc_html__( 'View My Financial Data', 'neatbnb-ops' )
			);
		}
		return esc_html__( 'No financial data available.', 'neatbnb-ops' );
	}

	/**
	 * [user_meta key="account_verification"] — styled status badge;
	 * when the value is "rejected", links to the verification page.
	 */
	public static function user_meta_status( $atts ) {
		$atts = shortcode_atts( array(
			'key'    => '',
			'user_id' => get_current_user_id(),
		), $atts, 'user_meta' );

		if ( '' === $atts['key'] ) {
			return esc_html__( 'Meta key missing!', 'neatbnb-ops' );
		}

		$value = get_user_meta( (int) $atts['user_id'], $atts['key'], true );

		if ( 'verified' === $value ) {
			$class = 'status-verified';
		} elseif ( 'rejected' === $value ) {
			$class = 'status-rejected';
		} else {
			$class = 'status-pending';
		}

		$html = '<span class="' . esc_attr( $class ) . '">' . esc_html( ucfirst( (string) $value ) ) . '</span>';

		if ( 'rejected' === $value ) {
			$verification_page = trim( NB_Settings::get( 'verification_page' ), '/' );
			$html .= '<br><a href="' . esc_url( home_url( '/' . $verification_page . '/' ) ) . '" target="_blank" rel="noopener">'
				. esc_html__( 'Click here to complete additional verification', 'neatbnb-ops' ) . '</a>';
		}
		return $html;
	}

	/** [telegram_channel] — invite link once verification completed. */
	public static function telegram_channel_status() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return esc_html__( 'You must be logged in to see this information.', 'neatbnb-ops' );
		}

		$status = get_user_meta( $user_id, 'account_verification', true );
		$url    = get_user_meta( $user_id, 'telegram_channel_link', true );

		// Both meta-key spellings appeared in production.
		if ( empty( $url ) ) {
			$url = get_user_meta( $user_id, 'Telegram_Channel_link', true );
		}

		if ( 'verified' === $status && ! empty( $url ) ) {
			return sprintf(
				'<a href="%s" target="_blank" rel="noopener" style="color:green; font-weight:bold;">%s</a>',
				esc_url( $url ),
				esc_html__( 'Join Telegram Channel', 'neatbnb-ops' )
			);
		}
		return '<span style="color:red; font-weight:bold;">'
			. esc_html__( 'Channel not available yet, once verification is complete the channel URL will appear here.', 'neatbnb-ops' )
			. '</span>';
	}

	/** [admin_host_financial_upload] — admin-only CSV upload form. */
	public static function csv_upload_form() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return esc_html__( "You don't have permission to upload financial data.", 'neatbnb-ops' );
		}

		$hosts = get_users( array( 'role' => 'host' ) );

		$output  = '<form method="post" enctype="multipart/form-data">';
		$output .= wp_nonce_field( 'nb_financial_csv', 'nb_csv_nonce', true, false );
		$output .= '<label for="host_id">' . esc_html__( 'Select Host:', 'neatbnb-ops' ) . '</label> ';
		$output .= '<select name="host_id" id="host_id" required>';
		foreach ( $hosts as $host ) {
			$output .= '<option value="' . esc_attr( $host->ID ) . '">' . esc_html( $host->display_name ) . '</option>';
		}
		$output .= '</select><br><br>';
		$output .= '<label for="property_name">' . esc_html__( 'Property Name:', 'neatbnb-ops' ) . '</label> ';
		$output .= '<input type="text" name="property_name" id="property_name" required><br><br>';
		$output .= '<input type="file" name="financial_csv" accept=".csv" required><br><br>';
		$output .= '<input type="submit" name="upload_csv" value="' . esc_attr__( 'Upload CSV', 'neatbnb-ops' ) . '">';
		$output .= '</form>';

		return $output;
	}
}
