<?php
/**
 * Environment and compatibility information.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads relevant WordPress and GeneratePress environment details.
 */
final class Environment {
	/**
	 * Minimum supported WordPress version.
	 */
	const MINIMUM_WORDPRESS_VERSION = '6.5';

	/**
	 * Minimum supported PHP version.
	 */
	const MINIMUM_PHP_VERSION = '7.4';

	/**
	 * Determine whether GeneratePress is the active parent theme.
	 *
	 * This also returns true when a GeneratePress child theme is active.
	 *
	 * @return bool
	 */
	public function is_generatepress_active() {
		return 'generatepress' === get_template();
	}

	/**
	 * Get the installed GeneratePress parent theme version.
	 *
	 * @return string
	 */
	public function get_generatepress_version() {
		$theme = wp_get_theme( 'generatepress' );

		if ( ! $theme->exists() ) {
			return '';
		}

		return (string) $theme->get( 'Version' );
	}

	/**
	 * Determine whether GeneratePress Premium is active.
	 *
	 * @return bool
	 */
	public function is_generatepress_premium_active() {
		return defined( 'GP_PREMIUM_VERSION' );
	}

	/**
	 * Get the active GeneratePress Premium version.
	 *
	 * @return string
	 */
	public function get_generatepress_premium_version() {
		return $this->is_generatepress_premium_active() ? (string) GP_PREMIUM_VERSION : '';
	}

	/**
	 * Determine whether the current WordPress version is supported.
	 *
	 * @return bool
	 */
	public function is_wordpress_supported() {
		return version_compare( get_bloginfo( 'version' ), self::MINIMUM_WORDPRESS_VERSION, '>=' );
	}

	/**
	 * Determine whether the current PHP version is supported.
	 *
	 * @return bool
	 */
	public function is_php_supported() {
		return version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=' );
	}
}

