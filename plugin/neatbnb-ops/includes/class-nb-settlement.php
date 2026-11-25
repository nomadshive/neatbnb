<?php
/**
 * Auto-create a financial_settlement page for each new host.
 *
 * From WPCode snippet "For each new host a financial settlement page
 * is created". Relies on the financial_settlement CPT registered by
 * the main plugin file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Settlement {

	public static function init() {
		add_action( 'user_register', array( __CLASS__, 'create_for_new_host' ) );
	}

	public static function create_for_new_host( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! in_array( 'host', (array) $user->roles, true ) ) {
			return;
		}

		$existing = get_posts( array(
			'post_type'   => 'financial_settlement',
			'post_status' => 'any',
			'meta_key'    => 'host_id',
			'meta_value'  => $user_id,
			'numberposts' => 1,
			'fields'      => 'ids',
		) );
		if ( $existing ) {
			return; // Idempotent: never create a duplicate for the same host.
		}

		$settlement_id = wp_insert_post( array(
			'post_title'  => sprintf( 'Financial Settlement - %s', $user->user_login ),
			'post_type'   => 'financial_settlement',
			'post_status' => 'publish',
			'post_author' => $user_id,
		) );

		if ( $settlement_id && ! is_wp_error( $settlement_id ) ) {
			update_post_meta( $settlement_id, 'host_id', $user_id );
		}
	}
}
