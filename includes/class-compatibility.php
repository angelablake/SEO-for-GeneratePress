<?php
/**
 * SEO-plugin compatibility checks.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Detects plugins likely to already own structured-data output. */
final class Compatibility {
	/** @return string Name of a detected SEO plugin, or an empty string. */
	public function get_conflicting_seo_plugin() {
		$checks = array(
			'WPSEO_VERSION'             => 'Yoast SEO',
			'RANK_MATH_VERSION'         => 'Rank Math SEO',
			'AIOSEO_VERSION'            => 'All in One SEO',
			'SEOPRESS_VERSION'          => 'SEOPress',
			'SLIM_SEO_VERSION'          => 'Slim SEO',
			'THE_SEO_FRAMEWORK_VERSION' => 'The SEO Framework',
		);

		foreach ( $checks as $constant => $name ) {
			if ( defined( $constant ) ) {
				return $name;
			}
		}

		if ( class_exists( 'The_SEO_Framework\\Load' ) || function_exists( 'the_seo_framework' ) ) {
			return 'The SEO Framework';
		}

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_files = array(
			'wordpress-seo/wp-seo.php'                    => 'Yoast SEO',
			'seo-by-rank-math/rank-math.php'              => 'Rank Math SEO',
			'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
			'wp-seopress/seopress.php'                    => 'SEOPress',
			'slim-seo/slim-seo.php'                       => 'Slim SEO',
			'autodescription/autodescription.php'         => 'The SEO Framework',
		);

		foreach ( $plugin_files as $file => $name ) {
			if ( in_array( $file, $active_plugins, true ) ) {
				return $name;
			}
		}

		return '';
	}
}
