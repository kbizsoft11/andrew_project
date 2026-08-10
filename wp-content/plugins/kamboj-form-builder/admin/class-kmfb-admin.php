<?php
/**
 * Admin UI.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin menus and screens.
 */
class KMFB_Admin {

	/**
	 * @var KMFB_Form_CPT
	 */
	private $forms;

	/**
	 * @var KMFB_Submissions
	 */
	private $submissions;

	/**
	 * @var KMFB_Settings
	 */
	private $settings;

	/**
	 * @var KMFB_Export
	 */
	private $export;

	/**
	 * Constructor.
	 */
	public function __construct( KMFB_Form_CPT $forms, KMFB_Submissions $submissions, KMFB_Settings $settings, KMFB_Export $export ) {
		$this->forms       = $forms;
		$this->submissions = $submissions;
		$this->settings    = $settings;
		$this->export      = $export;

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_kmfb_save_form', array( $this, 'ajax_save_form' ) );
		add_action( 'wp_ajax_kmfb_delete_form', array( $this, 'ajax_delete_form' ) );
		add_action( 'wp_ajax_kmfb_duplicate_form', array( $this, 'ajax_duplicate_form' ) );
		add_action( 'wp_ajax_kmfb_update_submission_status', array( $this, 'ajax_update_submission_status' ) );
		add_action( 'wp_ajax_kmfb_send_test_email', array( $this, 'ajax_send_test_email' ) );
	}

	/**
	 * Register admin pages.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'Kamboj Form Builder', 'kamboj-form-builder' ),
			__( 'Kamboj Form Builder', 'kamboj-form-builder' ),
			'manage_options',
			'kmfb-forms',
			array( $this, 'render_forms_page' ),
			'dashicons-email-alt2',
			58
		);

		add_submenu_page(
			'kmfb-forms',
			__( 'All Forms', 'kamboj-form-builder' ),
			__( 'All Forms', 'kamboj-form-builder' ),
			'manage_options',
			'kmfb-forms',
			array( $this, 'render_forms_page' )
		);

		add_submenu_page(
			'kmfb-forms',
			__( 'Add New', 'kamboj-form-builder' ),
			__( 'Add New', 'kamboj-form-builder' ),
			'manage_options',
			'kmfb-form-editor',
			array( $this, 'render_editor_page' )
		);

		add_submenu_page(
			'kmfb-forms',
			__( 'Submissions', 'kamboj-form-builder' ),
			__( 'Submissions', 'kamboj-form-builder' ),
			'manage_options',
			'kmfb-submissions',
			array( $this, 'render_submissions_page' )
		);

		add_submenu_page(
			'kmfb-forms',
			__( 'Settings', 'kamboj-form-builder' ),
			__( 'Settings', 'kamboj-form-builder' ),
			'manage_options',
			'kmfb-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		$kmfb_pages = array( 'kmfb-forms', 'kmfb-form-editor', 'kmfb-submissions', 'kmfb-settings' );
		if ( ! in_array( $page, $kmfb_pages, true ) && false === strpos( $hook, 'kmfb-' ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		wp_enqueue_style(
			'kmfb-admin',
			KMFB_PLUGIN_URL . 'admin/css/admin.css',
			array( 'dashicons' ),
			KMFB_VERSION
		);

		if ( 'kmfb-forms' === $page ) {
			wp_enqueue_script(
				'kmfb-forms-list',
				KMFB_PLUGIN_URL . 'admin/js/forms-list.js',
				array(),
				KMFB_VERSION,
				true
			);

			wp_localize_script(
				'kmfb-forms-list',
				'kmfbFormsList',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'kmfb_admin' ),
					'i18n'    => array(
						'confirmDelete' => __( 'Delete this form permanently? Submissions will remain in the database.', 'kamboj-form-builder' ),
						'duplicating'   => __( 'Duplicating…', 'kamboj-form-builder' ),
						'deleting'      => __( 'Deleting…', 'kamboj-form-builder' ),
						'deleteError'   => __( 'Could not delete form.', 'kamboj-form-builder' ),
						'duplicateError'=> __( 'Could not duplicate form.', 'kamboj-form-builder' ),
						'emptyForms'    => __( 'No forms yet. Create your first form.', 'kamboj-form-builder' ),
					),
				)
			);
		}

		if ( 'kmfb-form-editor' === $page ) {
			$this->enqueue_form_builder_assets();
		}

		if ( 'kmfb-settings' === $page ) {
			wp_enqueue_script(
				'kmfb-admin-settings',
				KMFB_PLUGIN_URL . 'admin/js/admin-settings.js',
				array(),
				KMFB_VERSION,
				true
			);

			wp_localize_script(
				'kmfb-admin-settings',
				'kmfbAdminSettings',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'kmfb_admin' ),
					'i18n'    => array(
						'sending' => __( 'Sending…', 'kamboj-form-builder' ),
						'error'   => __( 'Could not send test email.', 'kamboj-form-builder' ),
					),
				)
			);
		}
	}

	/**
	 * Enqueue form builder scripts and config.
	 */
	private function enqueue_form_builder_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form_id = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$template = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : '';
		$form       = $form_id ? $this->forms->get_form( $form_id ) : null;

		if ( ! $form ) {
			if ( 'newsletter' === $template ) {
				$form = $this->forms->newsletter_starter_form();
			} else {
				$form = array(
					'id'           => 0,
					'title'        => __( 'Contact Form', 'kamboj-form-builder' ),
					'slug'         => '',
					'fields'       => $this->forms->default_fields(),
					'settings'     => $this->forms->default_settings(),
					'notification' => $this->forms->default_notification(),
				);
			}
		}

		$default_messages = $this->settings->default_messages();
		$message_labels   = array(
			'validation_summary' => __( 'Validation summary (fallback message)', 'kamboj-form-builder' ),
			'required'           => __( 'Required field', 'kamboj-form-builder' ),
			'invalid_email'      => __( 'Invalid email', 'kamboj-form-builder' ),
			'invalid_phone'      => __( 'Invalid phone', 'kamboj-form-builder' ),
			'consent_required'   => __( 'Consent required', 'kamboj-form-builder' ),
			'file_required'      => __( 'File required', 'kamboj-form-builder' ),
			'file_failed'        => __( 'File upload failed', 'kamboj-form-builder' ),
			'file_size'          => __( 'File too large', 'kamboj-form-builder' ),
			'file_type'          => __( 'File type not allowed', 'kamboj-form-builder' ),
			'generic_error'      => __( 'Generic submit error', 'kamboj-form-builder' ),
		);

		$validation_messages = array();
		foreach ( $message_labels as $key => $label ) {
			$validation_messages[] = array(
				'key'     => $key,
				'label'   => $label,
				'default' => $default_messages[ $key ] ?? '',
			);
		}

		$phone_countries = array();
		foreach ( KMFB_Phone::countries() as $country ) {
			$phone_countries[] = array(
				'iso'   => $country['iso'],
				'dial'  => $country['dial'],
				'label' => $country['label'],
				'flag'  => KMFB_Phone::flag_emoji( $country['iso'] ),
			);
		}

		$builder_config = array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'kmfb_admin' ),
			'form'                => $form,
			'recaptchaConfigured' => kmfb_plugin()->recaptcha->is_configured(),
			'recaptchaVersion'    => kmfb_plugin()->recaptcha->get_config()['version'],
			'defaultPhoneCountry' => KMFB_Phone::default_country(),
			'phoneCountries'      => $phone_countries,
			'i18n'       => array(
				'saved'       => __( 'Form saved.', 'kamboj-form-builder' ),
				'saveError'   => __( 'Could not save form.', 'kamboj-form-builder' ),
				'addField'    => __( 'Add Field', 'kamboj-form-builder' ),
				'deleteField' => __( 'Delete', 'kamboj-form-builder' ),
				'fieldLabel'  => __( 'Label', 'kamboj-form-builder' ),
				'fieldName'   => __( 'Field name', 'kamboj-form-builder' ),
				'required'    => __( 'Required', 'kamboj-form-builder' ),
				'loadError'   => __( 'Form builder failed to load. Please refresh the page.', 'kamboj-form-builder' ),
			),
			'fieldTypes' => array(
				array( 'value' => 'text', 'label' => __( 'Text', 'kamboj-form-builder' ) ),
				array( 'value' => 'email', 'label' => __( 'Email', 'kamboj-form-builder' ) ),
				array( 'value' => 'tel', 'label' => __( 'Phone', 'kamboj-form-builder' ) ),
				array( 'value' => 'number', 'label' => __( 'Number', 'kamboj-form-builder' ) ),
				array( 'value' => 'textarea', 'label' => __( 'Textarea', 'kamboj-form-builder' ) ),
				array( 'value' => 'select', 'label' => __( 'Dropdown', 'kamboj-form-builder' ) ),
				array( 'value' => 'radio', 'label' => __( 'Radio', 'kamboj-form-builder' ) ),
				array( 'value' => 'checkbox', 'label' => __( 'Checkbox', 'kamboj-form-builder' ) ),
				array( 'value' => 'file', 'label' => __( 'File upload', 'kamboj-form-builder' ) ),
				array( 'value' => 'consent', 'label' => __( 'GDPR consent', 'kamboj-form-builder' ) ),
				array( 'value' => 'hidden', 'label' => __( 'Hidden', 'kamboj-form-builder' ) ),
			),
		);

		wp_enqueue_script(
			'kmfb-form-builder',
			KMFB_PLUGIN_URL . 'admin/js/form-builder.js',
			array(),
			KMFB_VERSION,
			true
		);

		wp_add_inline_script(
			'kmfb-form-builder',
			'window.kmfbBuilder = ' . wp_json_encode( $builder_config ) . ';',
			'before'
		);
	}

	/**
	 * Forms list page.
	 */
	public function render_forms_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$kmfb_forms = $this->forms->get_all_forms();
		include KMFB_PLUGIN_DIR . 'includes/views/admin-forms-list.php';
	}

	/**
	 * Form editor page.
	 */
	public function render_editor_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include KMFB_PLUGIN_DIR . 'includes/views/admin-form-editor.php';
	}

	/**
	 * Submissions inbox page.
	 */
	public function render_submissions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$kmfb_form_id       = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$kmfb_submission_id = isset( $_GET['submission_id'] ) ? (int) $_GET['submission_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$kmfb_forms         = $this->forms->get_all_forms();
		$kmfb_submissions   = $this->submissions->query(
			array(
				'form_id' => $kmfb_form_id,
				'limit'   => 50,
			)
		);
		$kmfb_selected      = $kmfb_submission_id ? $this->submissions->get( $kmfb_submission_id ) : null;

		include KMFB_PLUGIN_DIR . 'includes/views/admin-submissions.php';
	}

	/**
	 * Global settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$kmfb_settings = $this->settings->get();
		$kmfb_mail_log = kmfb_plugin()->mailer->get_mail_log();
		include KMFB_PLUGIN_DIR . 'includes/views/admin-settings.php';
	}

	/**
	 * AJAX: save form.
	 */
	public function ajax_save_form() {
		check_ajax_referer( 'kmfb_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kamboj-form-builder' ) ), 403 );
		}

		$form_raw = isset( $_POST['form'] ) ? sanitize_textarea_field( wp_unslash( $_POST['form'] ) ) : '{}';
		if ( ! is_string( $form_raw ) || '' === $form_raw ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form data.', 'kamboj-form-builder' ) ), 400 );
		}

		$payload = json_decode( $form_raw, true );
		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form data.', 'kamboj-form-builder' ) ), 400 );
		}

		$form_id = isset( $payload['id'] ) ? (int) $payload['id'] : 0;
		$title   = isset( $payload['title'] ) ? sanitize_text_field( $payload['title'] ) : __( 'Untitled Form', 'kamboj-form-builder' );

		$result = $this->forms->save_form( $form_id, $title, $payload );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		$form = $this->forms->get_form( (int) $result );
		wp_send_json_success( array( 'form' => $form, 'message' => __( 'Form saved.', 'kamboj-form-builder' ) ) );
	}

	/**
	 * AJAX: delete form.
	 */
	public function ajax_delete_form() {
		check_ajax_referer( 'kmfb_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kamboj-form-builder' ) ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;
		if ( $form_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form.', 'kamboj-form-builder' ) ), 400 );
		}

		$post = get_post( $form_id );
		if ( ! $post || KMFB_Form_CPT::POST_TYPE !== $post->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'kamboj-form-builder' ) ), 404 );
		}

		wp_delete_post( $form_id, true );

		wp_send_json_success( array( 'message' => __( 'Form deleted.', 'kamboj-form-builder' ) ) );
	}

	/**
	 * AJAX: duplicate form.
	 */
	public function ajax_duplicate_form() {
		check_ajax_referer( 'kmfb_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kamboj-form-builder' ) ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;
		if ( $form_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form.', 'kamboj-form-builder' ) ), 400 );
		}

		$result = $this->forms->duplicate_form( $form_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		wp_send_json_success(
			array(
				'form_id' => (int) $result,
				'editUrl' => admin_url( 'admin.php?page=kmfb-form-editor&form_id=' . (int) $result ),
				'message' => __( 'Form duplicated.', 'kamboj-form-builder' ),
			)
		);
	}

	/**
	 * AJAX: update submission status.
	 */
	public function ajax_update_submission_status() {
		check_ajax_referer( 'kmfb_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kamboj-form-builder' ) ), 403 );
		}

		$id     = isset( $_POST['submission_id'] ) ? (int) $_POST['submission_id'] : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'read';

		$this->submissions->update_status( $id, $status );
		wp_send_json_success();
	}

	/**
	 * AJAX: send test email from settings.
	 */
	public function ajax_send_test_email() {
		check_ajax_referer( 'kmfb_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'kamboj-form-builder' ) ), 403 );
		}

		$to = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : get_option( 'admin_email' );
		$result = kmfb_plugin()->mailer->send_test_email( $to );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: recipient email address */
					__( 'Test email sent to %s. Check your inbox and spam folder.', 'kamboj-form-builder' ),
					$to
				),
			)
		);
	}
}
