<?php
/**
 * CSV export for submissions.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exports submissions to CSV.
 */
class KMFB_Export {

	/**
	 * @var KMFB_Submissions
	 */
	private $submissions;

	/**
	 * @var KMFB_Form_CPT
	 */
	private $forms;

	/**
	 * Constructor.
	 */
	public function __construct( KMFB_Submissions $submissions, KMFB_Form_CPT $forms ) {
		$this->submissions = $submissions;
		$this->forms       = $forms;

		add_action( 'admin_post_kmfb_export_csv', array( $this, 'export_csv' ) );
	}

	/**
	 * Export handler.
	 */
	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'kamboj-form-builder' ) );
		}

		check_admin_referer( 'kmfb_export_csv' );

		$form_id = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
		$rows    = $this->submissions->query(
			array(
				'form_id' => $form_id,
				'limit'   => 5000,
				'offset'  => 0,
			)
		);

		$filename = 'kamboj-form-builder-' . gmdate( 'Y-m-d' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Form ID', 'Status', 'Created At', 'IP', 'Data JSON' ) );

		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				array(
					$row->id,
					$row->form_id,
					$row->status,
					$row->created_at,
					$row->ip,
					$row->data,
				)
			);
		}

		exit;
	}
}
