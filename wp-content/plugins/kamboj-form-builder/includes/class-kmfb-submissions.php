<?php
/**
 * Submission storage and retrieval.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * CRUD for form submissions.
 */
class KMFB_Submissions {

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	/**
	 * Insert submission.
	 *
	 * @param int                  $form_id Form ID.
	 * @param array<string, mixed> $data    Submission data.
	 * @return int|false
	 */
	public function insert( $form_id, $data ) {
		global $wpdb;

		$table = KMFB_Installer::table_name();
		$now   = current_time( 'mysql' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'form_id'    => (int) $form_id,
				'data'       => wp_json_encode( $data ),
				'ip'         => $this->get_client_ip(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'status'     => 'new',
				'created_at' => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get submissions with optional filters.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<int, object>
	 */
	public function query( $args = array() ) {
		global $wpdb;

		$table   = KMFB_Installer::table_name();
		$form_id = isset( $args['form_id'] ) ? (int) $args['form_id'] : 0;
		$status  = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : '';
		$limit   = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 20;
		$offset  = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;

		$where  = array( '1=1' );
		$params = array();

		if ( $form_id > 0 ) {
			$where[]  = 'form_id = %d';
			$params[] = $form_id;
		}

		if ( $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		$sql      = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Count submissions.
	 *
	 * @param int $form_id Optional form filter.
	 * @return int
	 */
	public function count( $form_id = 0 ) {
		global $wpdb;
		$table = KMFB_Installer::table_name();

		if ( $form_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE form_id = %d", $form_id ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Get one submission.
	 *
	 * @param int $id Submission ID.
	 * @return object|null
	 */
	public function get( $id ) {
		global $wpdb;
		$table = KMFB_Installer::table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		return $row ?: null;
	}

	/**
	 * Update submission status.
	 *
	 * @param int    $id     Submission ID.
	 * @param string $status Status.
	 * @return bool
	 */
	public function update_status( $id, $status ) {
		global $wpdb;
		$allowed = array( 'new', 'read', 'spam', 'archived' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$table = KMFB_Installer::table_name();
		$updated = $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => (int) $id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Delete submission.
	 *
	 * @param int $id Submission ID.
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;
		$table = KMFB_Installer::table_name();
		$deleted = $wpdb->delete( $table, array( 'id' => (int) $id ), array( '%d' ) );
		return false !== $deleted;
	}

	/**
	 * Decode submission data JSON.
	 *
	 * @param object $row Submission row.
	 * @return array<string, mixed>
	 */
	public function decode_data( $row ) {
		$data = json_decode( $row->data, true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Get client IP.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}
			$value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			if ( false !== strpos( $value, ',' ) ) {
				$parts = explode( ',', $value );
				$value = trim( $parts[0] );
			}
			if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
				return $value;
			}
		}
		return '';
	}

	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}
