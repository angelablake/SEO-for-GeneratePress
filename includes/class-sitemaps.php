<?php
/**
 * Native WordPress sitemap fallback.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Keeps the WordPress sitemap available when no other provider is detected. */
final class Sitemaps {
	/** Cached URLs discovered from robots.txt or the conventional endpoint. */
	const DISCOVERY_TRANSIENT = 'seogp_discovered_sitemaps';

	/** @var bool Whether this service changed a disabled core sitemap to enabled. */
	private $fallback_active = false;

	/** Register the late core-sitemap safeguard. */
	public function register_hooks() {
		add_filter( 'wp_sitemaps_enabled', array( $this, 'enable_fallback' ), PHP_INT_MAX );
	}

	/**
	 * Enable WordPress core sitemaps on public sites lacking another provider.
	 *
	 * @param bool $enabled Core sitemap state determined by earlier callbacks.
	 * @return bool
	 */
	public function enable_fallback( $enabled ) {
		if ( $enabled || ! get_option( 'blog_public' ) || $this->has_external_sitemap() ) {
			return (bool) $enabled;
		}

		$this->fallback_active = true;

		return true;
	}

	/** @return bool Whether the plugin supplied the enabled state. */
	public function is_fallback_active() {
		return $this->fallback_active;
	}

	/**
	 * Detect strong local evidence that another sitemap provider owns the job.
	 *
	 * @return bool
	 */
	private function has_external_sitemap() {
		if ( class_exists( '\\Jetpack' ) && is_callable( array( '\\Jetpack', 'is_module_active' ) ) && \Jetpack::is_module_active( 'sitemaps' ) ) {
			return true;
		}

		$discovered = get_transient( self::DISCOVERY_TRANSIENT );
		if ( is_array( $discovered ) && ! empty( $discovered ) ) {
			return true;
		}

		if ( file_exists( ABSPATH . 'sitemap.xml' ) || file_exists( ABSPATH . 'sitemap_index.xml' ) ) {
			return true;
		}

		$rewrite_rules = (array) get_option( 'rewrite_rules', array() );
		foreach ( array_keys( $rewrite_rules ) as $rule ) {
			if ( preg_match( '/sitemaps?(?:_index)?[^a-z0-9]*xml/i', $rule ) ) {
				return true;
			}
		}

		return false;
	}
}
