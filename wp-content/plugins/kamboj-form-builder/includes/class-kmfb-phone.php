<?php
/**
 * International phone field helpers.
 *
 * @package KambojFormBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Country dial codes and phone field utilities.
 */
class KMFB_Phone {

	/**
	 * Supported countries for the phone picker.
	 *
	 * @return array<int, array{iso: string, dial: string, label: string}>
	 */
	public static function countries() {
		return array(
			array( 'iso' => 'us', 'dial' => '1', 'label' => __( 'United States', 'kamboj-form-builder' ) ),
			array( 'iso' => 'in', 'dial' => '91', 'label' => __( 'India', 'kamboj-form-builder' ) ),
			array( 'iso' => 'gb', 'dial' => '44', 'label' => __( 'United Kingdom', 'kamboj-form-builder' ) ),
			array( 'iso' => 'ca', 'dial' => '1', 'label' => __( 'Canada', 'kamboj-form-builder' ) ),
			array( 'iso' => 'au', 'dial' => '61', 'label' => __( 'Australia', 'kamboj-form-builder' ) ),
			array( 'iso' => 'ae', 'dial' => '971', 'label' => __( 'United Arab Emirates', 'kamboj-form-builder' ) ),
			array( 'iso' => 'de', 'dial' => '49', 'label' => __( 'Germany', 'kamboj-form-builder' ) ),
			array( 'iso' => 'fr', 'dial' => '33', 'label' => __( 'France', 'kamboj-form-builder' ) ),
			array( 'iso' => 'sg', 'dial' => '65', 'label' => __( 'Singapore', 'kamboj-form-builder' ) ),
			array( 'iso' => 'pk', 'dial' => '92', 'label' => __( 'Pakistan', 'kamboj-form-builder' ) ),
			array( 'iso' => 'bd', 'dial' => '880', 'label' => __( 'Bangladesh', 'kamboj-form-builder' ) ),
			array( 'iso' => 'np', 'dial' => '977', 'label' => __( 'Nepal', 'kamboj-form-builder' ) ),
			array( 'iso' => 'lk', 'dial' => '94', 'label' => __( 'Sri Lanka', 'kamboj-form-builder' ) ),
			array( 'iso' => 'sa', 'dial' => '966', 'label' => __( 'Saudi Arabia', 'kamboj-form-builder' ) ),
			array( 'iso' => 'my', 'dial' => '60', 'label' => __( 'Malaysia', 'kamboj-form-builder' ) ),
			array( 'iso' => 'ph', 'dial' => '63', 'label' => __( 'Philippines', 'kamboj-form-builder' ) ),
			array( 'iso' => 'id', 'dial' => '62', 'label' => __( 'Indonesia', 'kamboj-form-builder' ) ),
			array( 'iso' => 'nz', 'dial' => '64', 'label' => __( 'New Zealand', 'kamboj-form-builder' ) ),
			array( 'iso' => 'za', 'dial' => '27', 'label' => __( 'South Africa', 'kamboj-form-builder' ) ),
			array( 'iso' => 'br', 'dial' => '55', 'label' => __( 'Brazil', 'kamboj-form-builder' ) ),
		);
	}

	/**
	 * Default country ISO code based on site locale.
	 *
	 * @return string
	 */
	public static function default_country() {
		$locale = get_locale();
		$map    = array(
			'en_US' => 'us',
			'en_IN' => 'in',
			'en_GB' => 'gb',
			'en_CA' => 'ca',
			'en_AU' => 'au',
			'hi_IN' => 'in',
		);

		if ( isset( $map[ $locale ] ) ) {
			return $map[ $locale ];
		}

		if ( false !== strpos( $locale, '_IN' ) ) {
			return 'in';
		}
		if ( false !== strpos( $locale, '_GB' ) ) {
			return 'gb';
		}
		if ( false !== strpos( $locale, '_AU' ) ) {
			return 'au';
		}
		if ( false !== strpos( $locale, '_CA' ) ) {
			return 'ca';
		}

		return 'us';
	}

	/**
	 * Convert ISO country code to flag emoji.
	 *
	 * @param string $iso Two-letter ISO code.
	 * @return string
	 */
	public static function flag_emoji( $iso ) {
		$iso = strtoupper( sanitize_key( $iso ) );
		if ( strlen( $iso ) !== 2 ) {
			return '';
		}

		$first  = 127397 + ord( $iso[0] );
		$second = 127397 + ord( $iso[1] );

		return mb_chr( $first ) . mb_chr( $second );
	}

	/**
	 * Sanitize stored phone country ISO.
	 *
	 * @param string $iso Raw ISO code.
	 * @return string
	 */
	public static function sanitize_country( $iso ) {
		$iso = sanitize_key( $iso );
		foreach ( self::countries() as $country ) {
			if ( $country['iso'] === $iso ) {
				return $iso;
			}
		}

		return self::default_country();
	}

	/**
	 * Validate an E.164-style phone number.
	 *
	 * @param string $value Phone number.
	 * @return bool
	 */
	public static function is_valid( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return true;
		}

		return (bool) preg_match( '/^\+[1-9]\d{7,14}$/', $value );
	}
}
