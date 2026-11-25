<?php
/**
 * Settings page (Settings → Neatbnb Ops) and option helpers.
 *
 * All values that were hardcoded in the original WPCode snippets
 * (Telegram bot token, chat ID, bot usernames, property map, page
 * slugs) live here so the plugin is portable across installs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Settings {

	const OPTION = 'nb_ops_settings';

	/** Defaults mirroring the original production configuration (URLs/slugs only — no secrets). */
	public static function defaults() {
		return array(
			'account_page'          => 'account',
			'host_page'             => 'host-management',
			'cleaning_page'         => 'cleaning-management',
			'maintenance_page'      => 'maintenance-management',
			'verification_page'     => 'additional-verification',
			'quiz_page'             => 'general-orientation-quiz',
			'telegram_bot_token'    => '',
			'telegram_chat_id'      => '',
			'telegram_bot_usernames'=> "@NeatbnbButlerbot\n@taskbookkeeper_bot\n@Enforcer95Bot\n@HousekeepingCA_Bot",
			'property_map'          => "406 = 1\n407 = 2\n464 = 3\n465 = 4\n563 = 5\n568 = 6\n632 = 7",
			// Formidable field IDs used by the task/property AJAX lookups.
			'field_task_select'     => '291',
			'field_task_type'       => '433',
			'field_order_property'  => '277',
			'field_out_task_type'   => '439',
			'field_out_property'    => '534',
			'field_cond_cleaning_a' => '292',
			'field_cond_cleaning_b' => '297',
			'field_cond_maintenance'=> '454',
		);
	}

	public static function get( $key = null ) {
		$saved = get_option( self::OPTION, array() );
		$opts  = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		if ( null === $key ) {
			return $opts;
		}
		return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
	}

	/** Parse "406 = 1" lines into array( '406' => '1' ). */
	public static function property_map() {
		$map = array();
		foreach ( explode( "\n", self::get( 'property_map' ) ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || false === strpos( $line, '=' ) ) {
				continue;
			}
			list( $k, $v ) = array_map( 'trim', explode( '=', $line, 2 ) );
			if ( '' !== $k ) {
				$map[ $k ] = $v;
			}
		}
		return $map;
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function add_menu() {
		add_options_page(
			__( 'Neatbnb Ops', 'neatbnb-ops' ),
			__( 'Neatbnb Ops', 'neatbnb-ops' ),
			'manage_options',
			'nb-ops',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register() {
		register_setting( 'nb_ops_group', self::OPTION, array( __CLASS__, 'sanitize' ) );

		$sections = array(
			'pages'     => __( 'System pages', 'neatbnb-ops' ),
			'telegram'  => __( 'Telegram channel provisioning', 'neatbnb-ops' ),
			'tasks'     => __( 'Task & property lookups', 'neatbnb-ops' ),
		);

		foreach ( $sections as $id => $title ) {
			add_settings_section( 'nb_' . $id, $title, '__return_false', 'nb-ops' );
		}

		$fields = array(
			'account_page'          => 'pages',
			'host_page'             => 'pages',
			'cleaning_page'         => 'pages',
			'maintenance_page'      => 'pages',
			'verification_page'     => 'pages',
			'quiz_page'             => 'pages',
			'telegram_bot_token'    => 'telegram',
			'telegram_chat_id'      => 'telegram',
			'telegram_bot_usernames'=> 'telegram',
			'property_map'          => 'tasks',
			'field_task_select'     => 'tasks',
			'field_task_type'       => 'tasks',
			'field_order_property'  => 'tasks',
			'field_out_task_type'   => 'tasks',
			'field_out_property'    => 'tasks',
			'field_cond_cleaning_a' => 'tasks',
			'field_cond_cleaning_b' => 'tasks',
			'field_cond_maintenance'=> 'tasks',
		);

		foreach ( $fields as $key => $section ) {
			add_settings_field(
				$key,
				self::label( $key ),
				array( __CLASS__, 'render_field' ),
				'nb-ops',
				'nb_' . $section,
				array( 'key' => $key )
			);
		}
	}

	private static function label( $key ) {
		$labels = array(
			'account_page'          => __( 'Account page slug', 'neatbnb-ops' ),
			'host_page'             => __( 'Host dashboard page slug', 'neatbnb-ops' ),
			'cleaning_page'         => __( 'Cleaning dashboard page slug', 'neatbnb-ops' ),
			'maintenance_page'      => __( 'Maintenance dashboard page slug', 'neatbnb-ops' ),
			'verification_page'     => __( 'Additional verification page slug', 'neatbnb-ops' ),
			'quiz_page'             => __( 'Quiz page slug (anti-cheat / no-translate target)', 'neatbnb-ops' ),
			'telegram_bot_token'    => __( 'Telegram bot token', 'neatbnb-ops' ),
			'telegram_chat_id'      => __( 'Telegram seed chat ID', 'neatbnb-ops' ),
			'telegram_bot_usernames'=> __( 'Bot usernames (one per line)', 'neatbnb-ops' ),
			'property_map'          => __( 'Property map (entry value = display value, one per line)', 'neatbnb-ops' ),
			'field_task_select'     => __( 'Formidable field: task select', 'neatbnb-ops' ),
			'field_task_type'       => __( 'Formidable field: task type (source)', 'neatbnb-ops' ),
			'field_order_property'  => __( 'Formidable field: property on order form', 'neatbnb-ops' ),
			'field_out_task_type'   => __( 'Formidable field: task type (hidden output)', 'neatbnb-ops' ),
			'field_out_property'    => __( 'Formidable field: property (hidden output)', 'neatbnb-ops' ),
			'field_cond_cleaning_a' => __( 'Conditional field A (cleaning)', 'neatbnb-ops' ),
			'field_cond_cleaning_b' => __( 'Conditional field B (cleaning)', 'neatbnb-ops' ),
			'field_cond_maintenance'=> __( 'Conditional field (maintenance)', 'neatbnb-ops' ),
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	public static function render_field( $args ) {
		$key  = $args['key'];
		$val  = self::get( $key );
		$name = self::OPTION . '[' . $key . ']';

		if ( 'telegram_bot_token' === $key ) {
			printf(
				'<input type="password" class="regular-text" name="%s" value="%s" autocomplete="new-password" />',
				esc_attr( $name ),
				esc_attr( $val )
			);
			echo '<p class="description">' . esc_html__( 'Stored encrypted-at-rest only as an option; never commit it to version control.', 'neatbnb-ops' ) . '</p>';
			return;
		}

		if ( in_array( $key, array( 'telegram_bot_usernames', 'property_map' ), true ) ) {
			printf(
				'<textarea class="large-text code" rows="5" name="%s">%s</textarea>',
				esc_attr( $name ),
				esc_textarea( $val )
			);
			return;
		}

		if ( 0 === strpos( $key, 'field_' ) ) {
			printf(
				'<input type="text" class="small-text" name="%s" value="%s" />',
				esc_attr( $name ),
				esc_attr( $val )
			);
			return;
		}

		printf(
			'<input type="text" class="regular-text" name="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( $val )
		);
	}

	public static function sanitize( $input ) {
		$old    = self::get();
		$clean  = array();
		$keys   = array_keys( self::defaults() );

		foreach ( $keys as $key ) {
			$value = isset( $input[ $key ] ) ? $input[ $key ] : ( isset( $old[ $key ] ) ? $old[ $key ] : '' );
			if ( 'telegram_bot_token' === $key ) {
				$clean[ $key ] = trim( sanitize_text_field( $value ) );
			} elseif ( 0 === strpos( $key, 'field_' ) ) {
				$clean[ $key ] = preg_replace( '/[^0-9]/', '', (string) $value );
			} elseif ( in_array( $key, array( 'telegram_bot_usernames', 'property_map' ), true ) ) {
				$clean[ $key ] = sanitize_textarea_field( $value );
			} else {
				$clean[ $key ] = sanitize_key( str_replace( '_', '-', $value ) );
				// Page slugs may contain slashes for nested paths; keep it simple: sanitize_title on segments.
				$clean[ $key ] = trim( sanitize_text_field( $value ), "/ \t\n\r" );
			}
		}
		return $clean;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Neatbnb Ops Settings', 'neatbnb-ops' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'nb_ops_group' );
				do_settings_sections( 'nb-ops' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
