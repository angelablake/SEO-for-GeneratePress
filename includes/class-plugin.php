<?php
/**
 * Main plugin coordinator.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin services.
 */
final class Plugin {
	/**
	 * Shared instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance;

	/**
	 * Whether the plugin has already booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the shared plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register plugin services.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		load_plugin_textdomain(
			'seo-for-generatepress',
			false,
			dirname( plugin_basename( SEOGP_FILE ) ) . '/languages'
		);

		$this->maybe_upgrade();

		$settings         = new Settings();
		$environment      = new Environment();
		$compatibility    = new Compatibility();
		$sitemaps         = new Sitemaps();
		$updater          = new Updater();
		$content_controls = new Content_Controls( $compatibility );
		$admin            = new Admin( $environment, $settings, $compatibility, $sitemaps );

		$settings->register_hooks();
		$sitemaps->register_hooks();
		$updater->register_hooks();
		$content_controls->register_hooks();
		$admin->register_hooks();

		$metadata = new Metadata( $settings, $compatibility );
		$metadata->register_hooks();

		$schema = new Schema( $settings, $compatibility, $metadata );
		$schema->register_hooks();

		$integration = new GeneratePress_Integration( $admin, $environment );
		$integration->register_hooks();
	}

	/**
	 * Record the running version and provide a home for future migrations.
	 *
	 * @return void
	 */
	private function maybe_upgrade() {
		$stored_version = get_option( 'seogp_version', '' );

		if ( SEOGP_VERSION !== $stored_version ) {
			update_option( 'seogp_version', SEOGP_VERSION );
		}
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}
}
