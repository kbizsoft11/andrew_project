<?php
/**
 * Frontend form rendering.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders forms on the frontend.
 */
class KMFB_Renderer {

	/**
	 * @var KMFB_Form_CPT
	 */
	private $forms;

	/**
	 * Constructor.
	 *
	 * @param KMFB_Form_CPT $forms Form service.
	 */
	public function __construct( KMFB_Form_CPT $forms ) {
		$this->forms = $forms;
	}

	/**
	 * Render a form.
	 *
	 * @param array<string, mixed> $form Form package.
	 * @return string
	 */
	public function render( $form ) {
		wp_enqueue_style( 'kmfb-form' );
		wp_enqueue_script( 'kmfb-form' );
		kmfb_plugin()->recaptcha->enqueue_for_form( $form );

		$kmfb_form = $form;
		ob_start();
		include KMFB_PLUGIN_DIR . 'templates/form-wrapper.php';
		return (string) ob_get_clean();
	}

	/**
	 * Render a single field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	public function render_field( $field ) {
		$type      = $field['type'];
		$id        = 'kmfb-' . esc_attr( $field['id'] );
		$name      = esc_attr( $field['name'] );
		$required  = ! empty( $field['required'] );
		$show_label = $this->field_show_label( $field, $type );
		$aria_label = $this->field_aria_label( $field, $show_label );
		$classes   = 'kmfb-field kmfb-field-' . esc_attr( $type ) . ' ' . esc_attr( $this->field_width_class( $field ) );
		if ( ! $show_label ) {
			$classes .= ' kmfb-field-no-label';
		}
		if ( ! empty( $field['css_class'] ) ) {
			$classes .= ' ' . esc_attr( $field['css_class'] );
		}

		$conditions = ! empty( $field['conditions'] ) ? wp_json_encode( $field['conditions'] ) : '';
		$hidden     = ! empty( $field['conditions'] ) ? ' kmfb-conditional' : '';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes . $hidden ); ?>" data-field-name="<?php echo esc_attr( $field['name'] ); ?>" <?php echo $conditions ? 'data-conditions="' . esc_attr( $conditions ) . '"' : ''; ?>>
			<?php if ( $show_label ) : ?>
				<label for="<?php echo esc_attr( $id ); ?>" class="kmfb-label">
					<?php echo esc_html( $field['label'] ); ?>
					<?php if ( $required ) : ?><span class="kmfb-required">*</span><?php endif; ?>
				</label>
			<?php endif; ?>

			<?php
			switch ( $type ) {
				case 'textarea':
					?>
					<textarea
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="kmfb-input"
						rows="5"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
						<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $aria_label ) : ?>
							aria-label="<?php echo esc_attr( $aria_label ); ?>"
						<?php endif; ?>
					></textarea>
					<?php
					break;

				case 'select':
					?>
					<select
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="kmfb-input"
						<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $aria_label ) : ?>
							aria-label="<?php echo esc_attr( $aria_label ); ?>"
						<?php endif; ?>
					>
						<option value=""><?php esc_html_e( 'Select…', 'kamboj-form-builder' ); ?></option>
						<?php foreach ( $field['options'] as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					break;

				case 'checkbox':
					if ( ! empty( $field['options'] ) ) {
						foreach ( $field['options'] as $index => $option ) {
							$option_id = $id . '-' . $index;
							printf(
								'<label class="kmfb-choice"><input type="checkbox" id="%1$s" name="%2$s[]" value="%3$s" %4$s /> %5$s</label>',
								esc_attr( $option_id ),
								esc_attr( $name ),
								esc_attr( $option ),
								$required ? 'required' : '',
								esc_html( $option )
							);
						}
					} else {
						?>
						<label class="kmfb-choice">
							<input
								type="checkbox"
								id="<?php echo esc_attr( $id ); ?>"
								name="<?php echo esc_attr( $name ); ?>"
								value="1"
								<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php if ( $aria_label ) : ?>
									aria-label="<?php echo esc_attr( $aria_label ); ?>"
								<?php endif; ?>
							/>
							<?php echo esc_html( $field['label'] ); ?>
						</label>
						<?php
					}
					break;

				case 'radio':
					foreach ( $field['options'] as $index => $option ) {
						$option_id = $id . '-' . $index;
						printf(
							'<label class="kmfb-choice"><input type="radio" id="%1$s" name="%2$s" value="%3$s" %4$s /> %5$s</label>',
							esc_attr( $option_id ),
							esc_attr( $name ),
							esc_attr( $option ),
							$required ? 'required' : '',
							esc_html( $option )
						);
					}
					break;

				case 'file':
					?>
					<input
						type="file"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="kmfb-input"
						accept="<?php echo esc_attr( $field['accept'] ); ?>"
						<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $aria_label ) : ?>
							aria-label="<?php echo esc_attr( $aria_label ); ?>"
						<?php endif; ?>
					/>
					<?php
					break;

				case 'consent':
					printf(
						'<label class="kmfb-choice kmfb-consent"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
						esc_attr( $id ),
						esc_attr( $name ),
						$required ? 'required' : '',
						esc_html( $field['label'] )
					);
					break;

				case 'hidden':
					printf(
						'<input type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
						esc_attr( $id ),
						esc_attr( $name ),
						esc_attr( $field['default'] )
					);
					break;

				case 'tel':
					echo $this->render_phone_control( $field, $id, $name, $required, $aria_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					break;

				default:
					?>
					<input
						type="<?php echo esc_attr( $type ); ?>"
						id="<?php echo esc_attr( $id ); ?>"
						name="<?php echo esc_attr( $name ); ?>"
						class="kmfb-input"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>"
						value="<?php echo esc_attr( $field['default'] ); ?>"
						<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( $aria_label ) : ?>
							aria-label="<?php echo esc_attr( $aria_label ); ?>"
						<?php endif; ?>
					/>
					<?php
					break;
			}
			?>
			<p class="kmfb-field-error" aria-live="polite"></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render international phone input with country selector.
	 *
	 * @param array<string, mixed> $field      Field definition.
	 * @param string               $id         Input ID.
	 * @param string               $name       Input name.
	 * @param bool                 $required   Whether required.
	 * @param string               $aria_label Accessible label text when the visual label is hidden.
	 * @return string
	 */
	private function render_phone_control( $field, $id, $name, $required, $aria_label ) {
		$selected_country = KMFB_Phone::sanitize_country( $field['phone_country'] ?? KMFB_Phone::default_country() );
		$placeholder      = ! empty( $field['placeholder'] ) ? $field['placeholder'] : __( 'Phone', 'kamboj-form-builder' );

		ob_start();
		?>
		<div class="kmfb-phone-control">
			<div class="kmfb-phone-country-wrap">
				<select class="kmfb-phone-country" aria-label="<?php esc_attr_e( 'Country code', 'kamboj-form-builder' ); ?>">
					<?php foreach ( KMFB_Phone::countries() as $country ) : ?>
						<option
							value="<?php echo esc_attr( $country['dial'] ); ?>"
							data-iso="<?php echo esc_attr( $country['iso'] ); ?>"
							<?php selected( $selected_country, $country['iso'] ); ?>
						>
							<?php echo esc_html( KMFB_Phone::flag_emoji( $country['iso'] ) . ' +' . $country['dial'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<input
				type="tel"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				class="kmfb-input kmfb-phone-number"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				inputmode="tel"
				autocomplete="tel-national"
				<?php echo $required ? 'required' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( $aria_label ) : ?>
					aria-label="<?php echo esc_attr( $aria_label ); ?>"
				<?php endif; ?>
			/>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Whether the field label should render above the control.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param string               $type  Field type.
	 * @return bool
	 */
	private function field_show_label( $field, $type ) {
		if ( 'hidden' === $type || 'consent' === $type ) {
			return false;
		}

		if ( ! empty( $field['options'] ) && in_array( $type, array( 'checkbox', 'radio' ), true ) ) {
			return ! array_key_exists( 'show_label', $field ) || ! empty( $field['show_label'] );
		}

		if ( 'checkbox' === $type ) {
			return false;
		}

		return ! array_key_exists( 'show_label', $field ) || ! empty( $field['show_label'] );
	}

	/**
	 * Accessible label text when the visual label is hidden.
	 *
	 * @param array<string, mixed> $field      Field definition.
	 * @param bool                 $show_label Whether the label is visible.
	 * @return string
	 */
	private function field_aria_label( $field, $show_label ) {
		if ( $show_label ) {
			return '';
		}

		return trim( (string) ( $field['label'] ?? $field['name'] ?? '' ) );
	}

	/**
	 * Resolve CSS class for field width.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @return string
	 */
	private function field_width_class( $field ) {
		return 'kmfb-field-width-' . kmfb_plugin()->forms->sanitize_field_width( $field['width'] ?? 'full' );
	}
}
