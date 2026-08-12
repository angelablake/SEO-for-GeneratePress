<?php
/**
 * Plugin Name:       SEO for GeneratePress
 * Description:       Lightweight, opinionated SEO tools designed for GeneratePress websites.
 * Version:           0.4.3
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Angela Blake
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       seo-for-generatepress
 * Domain Path:       /languages
 * Update URI:        https://github.com/angelablake/SEO-for-GeneratePress
 *
 * @package SEOForGeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SEOGP_VERSION', '0.4.3' );
define( 'SEOGP_FILE', __FILE__ );
define( 'SEOGP_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEOGP_URL', plugin_dir_url( __FILE__ ) );

require_once SEOGP_PATH . 'includes/class-environment.php';
require_once SEOGP_PATH . 'includes/class-settings.php';
require_once SEOGP_PATH . 'includes/class-compatibility.php';
require_once SEOGP_PATH . 'includes/class-sitemaps.php';
require_once SEOGP_PATH . 'includes/class-updater.php';
require_once SEOGP_PATH . 'includes/class-content-controls.php';
require_once SEOGP_PATH . 'includes/class-metadata.php';
require_once SEOGP_PATH . 'includes/class-schema.php';
require_once SEOGP_PATH . 'includes/class-generatepress-integration.php';
require_once SEOGP_PATH . 'includes/class-admin.php';
require_once SEOGP_PATH . 'includes/class-plugin.php';

/**
 * Add the plugin's default settings without overwriting existing data.
 */
function seogp_activate() {
	add_option(
		'seogp_settings',
		array(
			'delete_data_on_uninstall' => false,
			'identity_type'             => 'organization',
			'person_photo_id'           => 0,
			'social_urls'               => array(),
		)
	);

	update_option( 'seogp_version', SEOGP_VERSION );
}
register_activation_hook( __FILE__, 'seogp_activate' );

/**
 * Preserve settings when the plugin is deactivated.
 */
function seogp_deactivate() {
	// Intentionally empty. Deactivation must not remove user data.
}
register_deactivation_hook( __FILE__, 'seogp_deactivate' );

add_action(
	'plugins_loaded',
	static function () {
		\AngelaBlake\SEOForGeneratePress\Plugin::instance()->boot();
	}
);
