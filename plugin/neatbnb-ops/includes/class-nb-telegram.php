<?php
/**
 * Telegram channel provisioning.
 *
 * From WPCode snippet "Telegram Channel Creation". The original hardcoded
 * a live bot token and seed chat ID in source; both now come from
 * Settings → Neatbnb Ops and are never stored in this repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Telegram {

	public static function init() {
		add_shortcode( 'telegram_channel_button', array( __CLASS__, 'button_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'handle_creation' ) );
	}

	/** [telegram_channel_button] */
	public static function button_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'You need to be logged in to create a Telegram channel.', 'neatbnb-ops' ) . '</p>';
		}

		$user_id = get_current_user_id();
		$url     = get_user_meta( $user_id, 'Telegram_Channel_link', true );
		if ( empty( $url ) ) {
			$url = get_user_meta( $user_id, 'telegram_channel_link', true );
		}

		if ( $url ) {
			return '<p>' . sprintf(
				/* translators: %s: invite URL */
				esc_html__( 'Your Telegram channel is already created: %s', 'neatbnb-ops' ),
				'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a>'
			) . '</p>';
		}

		ob_start();
		?>
		<form method="post">
			<?php wp_nonce_field( 'nb_create_channel', 'nb_channel_nonce' ); ?>
			<input type="hidden" name="create_telegram_channel" value="1">
			<button type="submit"><?php esc_html_e( 'Create Telegram Channel', 'neatbnb-ops' ); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	public static function handle_creation() {
		if ( empty( $_POST['create_telegram_channel'] ) || ! is_user_logged_in() ) {
			return;
		}
		if ( ! isset( $_POST['nb_channel_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nb_channel_nonce'] ), 'nb_create_channel' ) ) {
			return;
		}

		$token = trim( NB_Settings::get( 'telegram_bot_token' ) );
		$chat  = trim( NB_Settings::get( 'telegram_chat_id' ) );
		if ( '' === $token || '' === $chat ) {
			return; // Not configured — button silently does nothing; admin sets this up first.
		}

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$channel_info = self::seed_channel( $token, $chat, $user->user_login, implode( ', ', (array) $user->roles ) );
		if ( $channel_info ) {
			self::activate_bots( $token, $channel_info['chat_id'] );
			update_user_meta( $user_id, 'Telegram_Channel_link', $channel_info['invite_link'] );
			wp_safe_redirect( add_query_arg( 'channel_created', '1', remove_query_arg( 'create_telegram_channel' ) ) );
			exit;
		}
	}

	/**
	 * Post a "group created" message into the seed chat and read back
	 * its invite link. (Kept byte-for-byte compatible with the original
	 * behaviour, which reused the seed chat as the per-user channel.)
	 */
	private static function seed_channel( $token, $chat_id, $username, $role ) {
		$api    = 'https://api.telegram.org/bot' . $token . '/';
		$title  = sprintf( 'Group for %s (%s)', $username, $role );

		$response = wp_remote_post( $api . 'sendMessage', array(
			'body'     => wp_json_encode( array(
				'chat_id' => $chat_id,
				'text'    => 'Group Created: ' . $title,
			) ),
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'timeout'  => 20,
		) );

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! $data || empty( $data['ok'] ) ) {
			return null;
		}

		$chat = isset( $data['result']['chat']['id'] ) ? $data['result']['chat']['id'] : null;
		if ( ! $chat ) {
			return null;
		}

		$invite = wp_remote_get( $api . 'exportChatInviteLink?chat_id=' . rawurlencode( $chat ), array( 'timeout' => 20 ) );
		$invite_data = json_decode( wp_remote_retrieve_body( $invite ), true );
		if ( ! $invite_data || empty( $invite_data['result'] ) ) {
			return null;
		}

		return array(
			'chat_id'     => $chat,
			'invite_link' => $invite_data['result'],
		);
	}

	private static function activate_bots( $token, $chat_id ) {
		$api  = 'https://api.telegram.org/bot' . $token . '/';
		$bots = array_filter( array_map( 'trim', explode( "\n", NB_Settings::get( 'telegram_bot_usernames' ) ) ) );

		foreach ( $bots as $bot ) {
			wp_remote_post( $api . 'forwardMessage', array(
				'body'    => wp_json_encode( array(
					'chat_id'       => $chat_id,
					'from_chat_id'  => $bot,
					'message_id'    => 1, // Forward the bot's pinned message so it joins the chat.
				) ),
				'headers' => array( 'Content-Type' => 'application/json' ),
				'timeout' => 20,
			) );
		}
	}
}
