<?php
/**
 * Privacy policy and personal data tools.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers privacy-related WordPress integrations.
 */
class KMFB_Privacy {

	/**
	 * @var KMFB_Submissions
	 */
	private $submissions;

	/**
	 * Constructor.
	 *
	 * @param KMFB_Submissions $submissions Submission service.
	 */
	public function __construct( KMFB_Submissions $submissions ) {
		$this->submissions = $submissions;

		add_action( 'admin_init', array( $this, 'register_privacy_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ) );
	}

	/**
	 * Add suggested privacy policy text.
	 */
	public function register_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			/* translators: %s: plugin name */
			__( '%s collects and stores information submitted through your forms.', 'kamboj-form-builder' ),
			'<strong>Kamboj Form Builder</strong>'
		);

		$content .= '<p>' . esc_html__( 'When a visitor submits a form, the plugin may store the submitted field values in your WordPress database if submission storage is enabled for that form. The plugin may also store the visitor IP address and browser user agent for spam protection.', 'kamboj-form-builder' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Email notifications are sent using your WordPress site mail system. If you enable confirmation emails or configure multiple notification recipients, those addresses receive the submitted information.', 'kamboj-form-builder' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If you configure a webhook URL on a form, submitted data is sent to that external service.', 'kamboj-form-builder' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'Kamboj Form Builder', 'kamboj-form-builder' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Register personal data exporter.
	 *
	 * @param array<string, array<string, mixed>> $exporters Exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_exporters( $exporters ) {
		$exporters['kamboj-form-builder'] = array(
			'exporter_friendly_name' => __( 'Kamboj Form Builder Submissions', 'kamboj-form-builder' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Register personal data eraser.
	 *
	 * @param array<string, array<string, mixed>> $erasers Erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_erasers( $erasers ) {
		$erasers['kamboj-form-builder'] = array(
			'eraser_friendly_name' => __( 'Kamboj Form Builder Submissions', 'kamboj-form-builder' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Export submission data for an email address.
	 *
	 * @param string $email_address Email address.
	 * @param int    $page          Page number.
	 * @return array<string, mixed>
	 */
	public function export_personal_data( $email_address, $page = 1 ) {
		$email_address = sanitize_email( $email_address );
		$page          = max( 1, (int) $page );
		$limit         = 50;
		$offset        = ( $page - 1 ) * $limit;
		$export_items  = array();

		$rows = $this->submissions->query(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		);

		foreach ( $rows as $row ) {
			$data = $this->submissions->decode_data( $row );
			if ( ! $this->submission_matches_email( $data, $email_address ) ) {
				continue;
			}

			$item_data = array(
				array(
					'name'  => __( 'Submission ID', 'kamboj-form-builder' ),
					'value' => (string) $row->id,
				),
				array(
					'name'  => __( 'Submitted at', 'kamboj-form-builder' ),
					'value' => (string) $row->created_at,
				),
				array(
					'name'  => __( 'IP address', 'kamboj-form-builder' ),
					'value' => (string) $row->ip,
				),
			);

			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = implode( ', ', array_map( 'strval', $value ) );
				}
				$item_data[] = array(
					'name'  => ucwords( str_replace( '_', ' ', (string) $key ) ),
					'value' => (string) $value,
				);
			}

			$export_items[] = array(
				'group_id'    => 'kamboj-form-builder',
				'group_label' => __( 'Form Submissions', 'kamboj-form-builder' ),
				'item_id'     => 'kmfb-submission-' . $row->id,
				'data'        => $item_data,
			);
		}

		return array(
			'data' => $export_items,
			'done' => count( $rows ) < $limit,
		);
	}

	/**
	 * Erase submission data for an email address.
	 *
	 * @param string $email_address Email address.
	 * @param int    $page          Page number.
	 * @return array<string, mixed>
	 */
	public function erase_personal_data( $email_address, $page = 1 ) {
		$email_address = sanitize_email( $email_address );
		$page          = max( 1, (int) $page );
		$limit         = 50;
		$offset        = ( $page - 1 ) * $limit;
		$items_removed = false;

		$rows = $this->submissions->query(
			array(
				'limit'  => $limit,
				'offset' => $offset,
			)
		);

		foreach ( $rows as $row ) {
			$data = $this->submissions->decode_data( $row );
			if ( ! $this->submission_matches_email( $data, $email_address ) ) {
				continue;
			}

			if ( $this->submissions->delete( (int) $row->id ) ) {
				$items_removed = true;
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $rows ) < $limit,
		);
	}

	/**
	 * Check whether submission data contains the target email.
	 *
	 * @param array<string, mixed> $data  Submission data.
	 * @param string               $email Email address.
	 * @return bool
	 */
	private function submission_matches_email( $data, $email ) {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					if ( is_email( (string) $item ) && 0 === strcasecmp( sanitize_email( (string) $item ), $email ) ) {
						return true;
					}
				}
				continue;
			}

			if ( is_email( (string) $value ) && 0 === strcasecmp( sanitize_email( (string) $value ), $email ) ) {
				return true;
			}

			if ( is_string( $value ) && false !== stripos( (string) $key, 'email' ) && 0 === strcasecmp( sanitize_email( $value ), $email ) ) {
				return true;
			}
		}

		return false;
	}
}
