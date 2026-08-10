<?php
/**
 * Main plugin bootstrap.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-installer.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-phone.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-form-cpt.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-settings.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-submissions.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-spam.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-recaptcha.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-mailer.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-renderer.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-handler.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-export.php';
require_once KMFB_PLUGIN_DIR . 'includes/class-kmfb-privacy.php';
require_once KMFB_PLUGIN_DIR . 'admin/class-kmfb-admin.php';

/**
 * Coordinates plugin services.
 */
class KMFB_Plugin {

	/**
	 * Singleton.
	 *
	 * @var KMFB_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var KMFB_Form_CPT
	 */
	public $forms;

	/**
	 * @var KMFB_Settings
	 */
	public $settings;

	/**
	 * @var KMFB_Submissions
	 */
	public $submissions;

	/**
	 * @var KMFB_Spam
	 */
	public $spam;

	/**
	 * @var KMFB_Mailer
	 */
	public $mailer;

	/**
	 * @var KMFB_Renderer
	 */
	public $renderer;

	/**
	 * @var KMFB_Handler
	 */
	public $handler;

	/**
	 * @var KMFB_Export
	 */
	public $export;

	/**
	 * @var KMFB_Recaptcha
	 */
	public $recaptcha;

	/**
	 * @var KMFB_Admin
	 */
	public $admin;

	/**
	 * @var KMFB_Privacy
	 */
	public $privacy;

	/**
	 * Get instance.
	 *
	 * @return KMFB_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings    = new KMFB_Settings();
		$this->forms       = new KMFB_Form_CPT();
		$this->submissions = new KMFB_Submissions();
		$this->recaptcha   = new KMFB_Recaptcha( $this->settings );
		$this->spam        = new KMFB_Spam( $this->settings, $this->recaptcha );
		$this->mailer      = new KMFB_Mailer();
		$this->renderer    = new KMFB_Renderer( $this->forms );
		$this->handler     = new KMFB_Handler( $this->forms, $this->submissions, $this->spam, $this->mailer, $this->settings );
		$this->export      = new KMFB_Export( $this->submissions, $this->forms );
		$this->privacy     = new KMFB_Privacy( $this->submissions );

		if ( is_admin() ) {
			$this->admin = new KMFB_Admin( $this->forms, $this->submissions, $this->settings, $this->export );
		}

		add_action( 'admin_init', array( 'KMFB_Installer', 'maybe_migrate' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
	}

	/**
	 * Register shared frontend assets.
	 */
	public function enqueue_public_assets() {
		$settings = $this->settings->get();
		$messages = isset( $settings['messages'] ) && is_array( $settings['messages'] )
			? $settings['messages']
			: $this->settings->default_messages();

		wp_register_style(
			'kmfb-form',
			KMFB_PLUGIN_URL . 'public/css/form.css',
			array(),
			KMFB_VERSION
		);

		wp_register_script(
			'kmfb-form',
			KMFB_PLUGIN_URL . 'public/js/form.js',
			array(),
			KMFB_VERSION,
			true
		);

		wp_localize_script(
			'kmfb-form',
			'kmfbForm',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'sending'            => __( 'Sending…', 'kamboj-form-builder' ),
					'success'            => __( 'Thank you! Your message has been sent.', 'kamboj-form-builder' ),
					'error'              => $messages['generic_error'] ?? __( 'Something went wrong. Please try again.', 'kamboj-form-builder' ),
					'validationSummary'  => $messages['validation_summary'] ?? __( 'Please correct the errors below.', 'kamboj-form-builder' ),
					'errorListTitle'     => __( 'Please fix the following:', 'kamboj-form-builder' ),
					'required' => $messages['required'] ?? __( 'This field is required.', 'kamboj-form-builder' ),
					'invalid'  => $messages['invalid_email'] ?? __( 'Please check this field.', 'kamboj-form-builder' ),
					'fileSize' => $messages['file_size'] ?? __( 'File is too large.', 'kamboj-form-builder' ),
					'fileType' => $messages['file_type'] ?? __( 'File type is not allowed.', 'kamboj-form-builder' ),
					'recaptchaRequired' => $messages['recaptcha_required'] ?? __( 'Please complete the reCAPTCHA challenge.', 'kamboj-form-builder' ),
					'recaptchaFailed'   => $messages['recaptcha_failed'] ?? __( 'reCAPTCHA verification failed. Please try again.', 'kamboj-form-builder' ),
				),
			)
		);
	}
}
