<?php
/**
 * Activation and database setup.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles install routines.
 */
class KMFB_Installer {

	/**
	 * Table version option key.
	 */
	const DB_VERSION_OPTION = 'kmfb_db_version';

	/**
	 * Current schema version.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Get submissions table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'kmfb_submissions';
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		self::create_tables();
		flush_rewrite_rules();
	}

	/**
	 * One-time migration from Mk Form Manager slugs and tables.
	 */
	public static function migrate_legacy_brand() {
		if ( get_option( 'kmfb_legacy_migrate_v1' ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$wpdb->posts} SET post_type = 'kmfb_form' WHERE post_type = 'mfm_form'" );

		$legacy_table = $wpdb->prefix . 'mfm_submissions';
		$new_table    = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$legacy_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );

		if ( $legacy_exists && ! $new_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->query( "RENAME TABLE {$legacy_table} TO {$new_table}" );
		}


		$legacy_options = array(
			'mfm_settings'             => 'kmfb_settings',
			'mfm_mail_log'              => 'kmfb_mail_log',
			'mfm_db_version'            => 'kmfb_db_version',
			'mfm_notification_fix_v1'   => 'kmfb_notification_fix_v1',
		);
		foreach ( $legacy_options as $legacy_key => $new_key ) {
			if ( false === get_option( $new_key ) && false !== get_option( $legacy_key ) ) {
				update_option( $new_key, get_option( $legacy_key ) );
			}
		}

		update_option( 'kmfb_legacy_migrate_v1', 1 );
	}

	public static function maybe_migrate() {
		self::migrate_legacy_brand();

		if ( get_option( 'kmfb_notification_fix_v1' ) ) {
			return;
		}

		if ( ! function_exists( 'kmfb_plugin' ) ) {
			return;
		}

		$mailer = kmfb_plugin()->mailer;
		$posts  = get_posts(
			array(
				'post_type'      => KMFB_Form_CPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			$raw = get_post_meta( $post_id, KMFB_Form_CPT::META_NOTIFICATION, true );
			if ( ! is_array( $raw ) || empty( $raw ) ) {
				$legacy_raw = get_post_meta( $post_id, '_mfm_notification', true );
				if ( is_array( $legacy_raw ) && ! empty( $legacy_raw ) ) {
					$raw = $legacy_raw;
				}
			}
			if ( ! is_array( $raw ) ) {
				$raw = array();
			}

			$normalized = $mailer->normalize_notification( $raw );

			if ( '0' === $normalized['enabled'] && ( '' !== $normalized['subject'] || '' !== $normalized['body'] || '' !== $normalized['to'] ) ) {
				$normalized['enabled'] = '1';
			}

			update_post_meta( $post_id, KMFB_Form_CPT::META_NOTIFICATION, $normalized );
		}

		update_option( 'kmfb_notification_fix_v1', 1 );
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Create or update custom tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			data longtext NOT NULL,
			ip varchar(45) DEFAULT '' NOT NULL,
			user_agent text NULL,
			status varchar(20) DEFAULT 'new' NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Drop custom plugin tables.
	 */
	public static function drop_tables() {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
