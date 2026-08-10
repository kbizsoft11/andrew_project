<?php
/**
 * Form custom post type and field storage.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers forms as a custom post type.
 */
class KMFB_Form_CPT {

	const POST_TYPE = 'kmfb_form';

	const META_FIELDS       = '_kmfb_fields';
	const META_SETTINGS     = '_kmfb_form_settings';
	const META_NOTIFICATION = '_kmfb_notification';

	/**
	 * Default form settings.
	 *
	 * @return array<string, mixed>
	 */
	public function default_settings() {
		return array(
			'submit_label'      => __( 'Send Message', 'kamboj-form-builder' ),
			'success_message'   => __( 'Thank you! We will get back to you soon.', 'kamboj-form-builder' ),
			'redirect_url'      => '',
			'enable_honeypot'   => true,
			'enable_rate_limit' => true,
			'webhook_url'       => '',
			'store_submissions' => true,
			'enable_recaptcha'  => false,
			'form_layout'       => 'stacked',
		);
	}

	/**
	 * Default email notification settings.
	 *
	 * @return array<string, string>
	 */
	public function default_notification() {
		return array(
			'enabled'                => '1',
			'to'                     => '',
			'subject'                => __( 'New form submission: {{form_title}}', 'kamboj-form-builder' ),
			'body'                   => "You received a new submission.\n\n{{all_fields}}\n\n--\nSent from {{site_name}}",
			'confirmation_enabled'   => '0',
			'confirmation_subject'   => __( 'Thank you for contacting {{site_name}}', 'kamboj-form-builder' ),
			'confirmation_body'      => __( "Hi {{name}},\n\nThank you for reaching out. We have received your message and will get back to you soon.\n\n— {{site_name}}", 'kamboj-form-builder' ),
			'file_delivery'          => 'url',
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_shortcode( 'kmfb_form', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register CPT.
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Forms', 'kamboj-form-builder' ),
					'singular_name' => __( 'Form', 'kamboj-form-builder' ),
					'add_new_item'  => __( 'Add New Form', 'kamboj-form-builder' ),
					'edit_item'     => __( 'Edit Form', 'kamboj-form-builder' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'   => 0,
				'slug' => '',
			),
			$atts,
			'kmfb_form'
		);

		$form = null;
		if ( ! empty( $atts['id'] ) ) {
			$form = $this->get_form( (int) $atts['id'] );
		} elseif ( ! empty( $atts['slug'] ) ) {
			$form = $this->get_form_by_slug( sanitize_title( $atts['slug'] ) );
		}

		if ( ! $form ) {
			return current_user_can( 'edit_posts' )
				? '<p class="kmfb-error">' . esc_html__( 'Form not found.', 'kamboj-form-builder' ) . '</p>'
				: '';
		}

		return kmfb_plugin()->renderer->render( $form );
	}

	/**
	 * Get form package by ID.
	 *
	 * @param int $form_id Form ID.
	 * @return array<string, mixed>|null
	 */
	public function get_form( $form_id ) {
		$post = get_post( $form_id );
		if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return null;
		}

		return $this->package_form( $post );
	}

	/**
	 * Get form by slug (post_name).
	 *
	 * @param string $slug Form slug.
	 * @return array<string, mixed>|null
	 */
	public function get_form_by_slug( $slug ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'name'           => $slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);

		if ( empty( $posts ) ) {
			return null;
		}

		return $this->package_form( $posts[0] );
	}

	/**
	 * Build normalized form array.
	 *
	 * @param WP_Post $post Form post.
	 * @return array<string, mixed>
	 */
	public function package_form( WP_Post $post ) {
		$fields       = get_post_meta( $post->ID, self::META_FIELDS, true );
		$settings     = get_post_meta( $post->ID, self::META_SETTINGS, true );
		$notification = get_post_meta( $post->ID, self::META_NOTIFICATION, true );

		if ( ! is_array( $fields ) ) {
			$fields = array();
		}
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		if ( ! is_array( $notification ) ) {
			$notification = array();
		}

		// Legacy meta from Mk Form Manager (_mfm_*) and Swift Contact Forms (_scf_*).
		if ( empty( $notification ) ) {
			$legacy_notification = get_post_meta( $post->ID, '_mfm_notification', true );
			if ( is_array( $legacy_notification ) && ! empty( $legacy_notification ) ) {
				$notification = $legacy_notification;
			}
		}

		if ( empty( $notification ) ) {
			$legacy_notification = get_post_meta( $post->ID, '_scf_notification', true );
			if ( is_array( $legacy_notification ) && ! empty( $legacy_notification ) ) {
				$notification = $legacy_notification;
			}
		}

		if ( empty( $fields ) ) {
			$legacy_fields = get_post_meta( $post->ID, '_mfm_fields', true );
			if ( is_array( $legacy_fields ) && ! empty( $legacy_fields ) ) {
				$fields = $legacy_fields;
			}
		}

		if ( empty( $fields ) ) {
			$legacy_fields = get_post_meta( $post->ID, '_scf_fields', true );
			if ( is_array( $legacy_fields ) && ! empty( $legacy_fields ) ) {
				$fields = $legacy_fields;
			}
		}

		if ( empty( $settings ) ) {
			$legacy_settings = get_post_meta( $post->ID, '_mfm_form_settings', true );
			if ( is_array( $legacy_settings ) && ! empty( $legacy_settings ) ) {
				$settings = $legacy_settings;
			}
		}

		if ( empty( $settings ) ) {
			$legacy_settings = get_post_meta( $post->ID, '_scf_form_settings', true );
			if ( is_array( $legacy_settings ) && ! empty( $legacy_settings ) ) {
				$settings = $legacy_settings;
			}
		}

		$mailer = kmfb_plugin()->mailer;

		return array(
			'id'           => (int) $post->ID,
			'title'        => $post->post_title,
			'slug'         => $post->post_name,
			'fields'       => $this->sanitize_fields( $fields ),
			'settings'     => wp_parse_args( $settings, $this->default_settings() ),
			'notification' => $mailer->normalize_notification( wp_parse_args( $notification, $this->default_notification() ) ),
		);
	}

	/**
	 * Sanitize field definitions.
	 *
	 * @param array<int, array<string, mixed>> $fields Raw fields.
	 * @return array<int, array<string, mixed>>
	 */
	public function sanitize_fields( $fields ) {
		$allowed_types = array( 'text', 'email', 'tel', 'textarea', 'select', 'checkbox', 'radio', 'file', 'consent', 'hidden', 'number' );
		$clean         = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';
			if ( ! in_array( $type, $allowed_types, true ) ) {
				continue;
			}

			$id = ! empty( $field['id'] ) ? sanitize_key( $field['id'] ) : uniqid( 'field_', false );
			$name = ! empty( $field['name'] ) ? sanitize_key( $field['name'] ) : $id;

			$clean_field = array(
				'id'          => $id,
				'type'        => $type,
				'label'       => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
				'name'        => $name,
				'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
				'required'    => ! empty( $field['required'] ),
				'width'       => $this->sanitize_field_width( $field['width'] ?? 'full' ),
				'options'     => array(),
				'conditions'  => array(),
				'max_size_mb' => isset( $field['max_size_mb'] ) ? max( 1, (int) $field['max_size_mb'] ) : 5,
				'accept'      => isset( $field['accept'] ) ? sanitize_text_field( $field['accept'] ) : '',
				'default'     => isset( $field['default'] ) ? sanitize_text_field( $field['default'] ) : '',
				'css_class'   => isset( $field['css_class'] ) ? sanitize_html_class( $field['css_class'] ) : '',
				'show_label'  => ! array_key_exists( 'show_label', $field ) || ! empty( $field['show_label'] ),
				'phone_country' => 'tel' === $type ? KMFB_Phone::sanitize_country( $field['phone_country'] ?? KMFB_Phone::default_country() ) : '',
			);

			if ( ! empty( $field['options'] ) && is_array( $field['options'] ) ) {
				foreach ( $field['options'] as $option ) {
					$clean_field['options'][] = sanitize_text_field( (string) $option );
				}
			}

			if ( ! empty( $field['conditions'] ) && is_array( $field['conditions'] ) ) {
				foreach ( $field['conditions'] as $condition ) {
					if ( ! is_array( $condition ) ) {
						continue;
					}
					$clean_field['conditions'][] = array(
						'field'    => sanitize_key( $condition['field'] ?? '' ),
						'operator' => in_array( $condition['operator'] ?? '', array( 'equals', 'not_equals', 'filled' ), true ) ? $condition['operator'] : 'equals',
						'value'    => sanitize_text_field( $condition['value'] ?? '' ),
					);
				}
			}

			$clean[] = $clean_field;
		}

		return $clean;
	}

	/**
	 * Sanitize field width value.
	 *
	 * @param mixed $width Raw width value.
	 * @return string
	 */
	public function sanitize_field_width( $width ) {
		$allowed = array( 'full', 'half', 'third' );
		$width   = sanitize_key( (string) $width );

		return in_array( $width, $allowed, true ) ? $width : 'full';
	}

	/**
	 * Save form data.
	 *
	 * @param int                  $form_id Form ID. 0 for new.
	 * @param string               $title   Form title.
	 * @param array<string, mixed> $payload Form payload.
	 * @return int|WP_Error
	 */
	public function save_form( $form_id, $title, $payload ) {
		$postarr = array(
			'post_type'   => self::POST_TYPE,
			'post_title'  => sanitize_text_field( $title ),
			'post_status' => 'publish',
		);

		if ( $form_id > 0 ) {
			$postarr['ID'] = $form_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$form_id = (int) $result;

		$fields = isset( $payload['fields'] ) ? $this->sanitize_fields( $payload['fields'] ) : array();
		update_post_meta( $form_id, self::META_FIELDS, $fields );

		$settings = isset( $payload['settings'] ) && is_array( $payload['settings'] )
			? wp_parse_args( $payload['settings'], $this->default_settings() )
			: $this->default_settings();
		update_post_meta( $form_id, self::META_SETTINGS, $this->sanitize_settings( $settings ) );

		$notification = isset( $payload['notification'] ) && is_array( $payload['notification'] )
			? wp_parse_args( $payload['notification'], $this->default_notification() )
			: $this->default_notification();
		update_post_meta( $form_id, self::META_NOTIFICATION, $this->sanitize_notification( $notification ) );

		return $form_id;
	}

	/**
	 * Duplicate an existing form.
	 *
	 * @param int $form_id Source form ID.
	 * @return int|WP_Error New form ID or error.
	 */
	public function duplicate_form( $form_id ) {
		$form = $this->get_form( $form_id );
		if ( ! $form ) {
			return new WP_Error( 'kmfb_not_found', __( 'Form not found.', 'kamboj-form-builder' ) );
		}

		$title  = sprintf(
			/* translators: %s: original form title */
			__( '%s (Copy)', 'kamboj-form-builder' ),
			$form['title']
		);
		$fields = $form['fields'];

		foreach ( $fields as $index => $field ) {
			$fields[ $index ]['id'] = function_exists( 'wp_unique_id' )
				? wp_unique_id( 'field_' )
				: 'field_' . wp_generate_password( 8, false, false );
		}

		return $this->save_form(
			0,
			$title,
			array(
				'fields'       => $fields,
				'settings'     => $form['settings'],
				'notification' => $form['notification'],
			)
		);
	}

	/**
	 * Sanitize per-form settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $settings ) {
		return array(
			'submit_label'      => sanitize_text_field( $settings['submit_label'] ?? '' ),
			'success_message'   => sanitize_textarea_field( $settings['success_message'] ?? '' ),
			'redirect_url'      => esc_url_raw( $settings['redirect_url'] ?? '' ),
			'enable_honeypot'   => ! empty( $settings['enable_honeypot'] ),
			'enable_rate_limit' => ! empty( $settings['enable_rate_limit'] ),
			'webhook_url'       => esc_url_raw( $settings['webhook_url'] ?? '' ),
			'store_submissions' => ! empty( $settings['store_submissions'] ),
			'enable_recaptcha'  => ! empty( $settings['enable_recaptcha'] ),
			'form_layout'       => in_array( $settings['form_layout'] ?? 'stacked', array( 'stacked', 'inline' ), true )
				? $settings['form_layout']
				: 'stacked',
			'messages'          => $this->sanitize_messages( $settings['messages'] ?? array() ),
		);
	}

	/**
	 * Sanitize per-form validation message overrides.
	 *
	 * @param mixed $messages Raw messages.
	 * @return array<string, string>
	 */
	public function sanitize_messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		$defaults = kmfb_plugin()->settings->default_messages();
		$clean    = array();

		foreach ( $defaults as $key => $default ) {
			$value = isset( $messages[ $key ] ) ? sanitize_text_field( (string) $messages[ $key ] ) : '';
			$clean[ $key ] = $value;
		}

		return $clean;
	}

	/**
	 * Sanitize notification settings.
	 *
	 * @param array<string, string> $notification Notification settings.
	 * @return array<string, string>
	 */
	public function sanitize_notification( $notification ) {
		$mailer = kmfb_plugin()->mailer;

		return $mailer->normalize_notification(
			is_array( $notification ) ? $notification : array()
		);
	}

	/**
	 * Get all forms.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_forms() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$forms = array();
		foreach ( $posts as $post ) {
			$forms[] = $this->package_form( $post );
		}

		return $forms;
	}

	/**
	 * Starter fields for a new form.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function default_fields() {
		return array(
			array(
				'id'          => 'field_name',
				'type'        => 'text',
				'label'       => __( 'Your Name', 'kamboj-form-builder' ),
				'name'        => 'name',
				'placeholder' => __( 'John Doe', 'kamboj-form-builder' ),
				'required'    => true,
				'options'     => array(),
				'conditions'  => array(),
			),
			array(
				'id'          => 'field_email',
				'type'        => 'email',
				'label'       => __( 'Email Address', 'kamboj-form-builder' ),
				'name'        => 'email',
				'placeholder' => 'you@example.com',
				'required'    => true,
				'options'     => array(),
				'conditions'  => array(),
			),
			array(
				'id'          => 'field_message',
				'type'        => 'textarea',
				'label'       => __( 'Message', 'kamboj-form-builder' ),
				'name'        => 'message',
				'placeholder' => __( 'How can we help?', 'kamboj-form-builder' ),
				'required'    => true,
				'options'     => array(),
				'conditions'  => array(),
			),
			array(
				'id'         => 'field_consent',
				'type'       => 'consent',
				'label'      => __( 'I agree to the privacy policy.', 'kamboj-form-builder' ),
				'name'       => 'consent',
				'required'   => true,
				'options'    => array(),
				'conditions' => array(),
			),
		);
	}

	/**
	 * Starter package for a newsletter / subscriber signup form.
	 *
	 * @return array<string, mixed>
	 */
	public function newsletter_starter_form() {
		return array(
			'id'           => 0,
			'title'        => __( 'Newsletter Signup', 'kamboj-form-builder' ),
			'slug'         => '',
			'fields'       => array(
				array(
					'id'          => 'field_email',
					'type'        => 'email',
					'label'       => __( 'Email', 'kamboj-form-builder' ),
					'name'        => 'email',
					'placeholder' => __( 'Enter your email address', 'kamboj-form-builder' ),
					'required'    => true,
					'show_label'  => false,
					'width'       => 'full',
					'options'     => array(),
					'conditions'  => array(),
				),
			),
			'settings'     => wp_parse_args(
				array(
					'form_layout'     => 'inline',
					'submit_label'    => __( 'Subscribe', 'kamboj-form-builder' ),
					'success_message' => __( 'Thanks for subscribing!', 'kamboj-form-builder' ),
					'store_submissions' => true,
					'enable_honeypot'   => true,
					'enable_rate_limit' => true,
				),
				$this->default_settings()
			),
			'notification' => wp_parse_args(
				array(
					'subject' => __( 'New newsletter signup: {{form_title}}', 'kamboj-form-builder' ),
					'body'    => "New subscriber:\n\n{{all_fields}}\n\n--\nSent from {{site_name}}",
				),
				$this->default_notification()
			),
		);
	}
}
