<?php
/**
 * Task-type / property AJAX lookups and conditional field toggling.
 *
 * Ported from the bellevuex-child theme functions.php. The hardcoded
 * property map and Formidable field IDs now come from settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Tasks {

	public static function init() {
		add_action( 'wp_ajax_get_task_type', array( __CLASS__, 'get_task_type' ) );
		add_action( 'wp_ajax_nopriv_get_task_type', array( __CLASS__, 'get_task_type' ) );
		add_action( 'wp_ajax_get_property_id', array( __CLASS__, 'get_property_id' ) );
		add_action( 'wp_ajax_nopriv_get_property_id', array( __CLASS__, 'get_property_id' ) );
		add_action( 'wp_footer', array( __CLASS__, 'print_scripts' ) );
	}

	private static function entry_meta( $entry_id, $field_id ) {
		if ( ! class_exists( 'FrmEntryMeta' ) || ! $entry_id || ! $field_id ) {
			return '';
		}
		return (string) FrmEntryMeta::get_entry_meta_by_field( (int) $entry_id, (int) $field_id );
	}

	/** AJAX: task type for a given task entry. */
	public static function get_task_type() {
		$task_id = isset( $_POST['task_id'] ) ? (int) $_POST['task_id'] : 0;
		if ( $task_id ) {
			echo esc_html( self::entry_meta( $task_id, (int) NB_Settings::get( 'field_task_type' ) ) );
		}
		wp_die();
	}

	/** AJAX: property display value for a given task entry (mapped through settings). */
	public static function get_property_id() {
		$task_id = isset( $_POST['task_id'] ) ? (int) $_POST['task_id'] : 0;
		if ( ! $task_id ) {
			wp_die();
		}

		$property_id = self::entry_meta( $task_id, (int) NB_Settings::get( 'field_order_property' ) );
		if ( ! $property_id ) {
			wp_die();
		}

		$map = NB_Settings::property_map();
		echo esc_html( isset( $map[ $property_id ] ) ? $map[ $property_id ] : $property_id );
		wp_die();
	}

	/**
	 * Footer script: when the task select changes, fetch task type and
	 * property into their hidden fields and show/hide the conditional
	 * cleaning vs maintenance fields.
	 */
	public static function print_scripts() {
		$o = NB_Settings::get();
		$cfg = array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'task'     => $o['field_task_select'],
			'typeOut'  => $o['field_out_task_type'],
			'propOut'  => $o['field_out_property'],
			'cleanA'   => $o['field_cond_cleaning_a'],
			'cleanB'   => $o['field_cond_cleaning_b'],
			'maint'    => $o['field_cond_maintenance'],
		);
		?>
		<script>
		jQuery(document).ready(function ($) {
			var cfg = <?php echo wp_json_encode( $cfg ); ?>;

			function metaSel(id) { return '[name="item_meta[' + id + ']"]'; }

			function toggleFields(taskType) {
				var showCleaning = (taskType === 'Cleaning');
				var showMaint    = (taskType === 'Maintenance');
				$(metaSel(cfg.cleanA)).closest('.frm_form_field').toggle(showCleaning);
				$(metaSel(cfg.cleanB)).closest('.frm_form_field').toggle(showCleaning);
				$(metaSel(cfg.maint)).closest('.frm_form_field').toggle(showMaint);
			}

			toggleFields('');

			$(metaSel(cfg.task) + ', [name="item_meta[' + cfg.task + ']"]').on('change', function () {
				var taskId = $(this).val();
				if (!taskId) return;

				$.post(cfg.ajaxUrl, { action: 'get_task_type', task_id: taskId }, function (response) {
					$(metaSel(cfg.typeOut)).val(response);
					toggleFields(String(response).trim());
				});

				$.post(cfg.ajaxUrl, { action: 'get_property_id', task_id: taskId }, function (response) {
					$(metaSel(cfg.propOut)).val(response);
				});
			});
		});
		</script>
		<?php
	}
}
