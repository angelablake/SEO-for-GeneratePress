<?php
/** GitHub release updates. @package SEOForGeneratePress */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Connects WordPress's native plugin updater to published GitHub releases. */
final class Updater {
	const REPOSITORY_URL = 'https://github.com/angelablake/SEO-for-GeneratePress';
	const RELEASE_API_URL = 'https://api.github.com/repos/angelablake/SEO-for-GeneratePress/releases/latest';
	const ASSET_NAME = 'seo-for-generatepress.zip';
	const CACHE_KEY = 'seogp_github_release';

	/** Register the hostname-specific WordPress update hook. */
	public function register_hooks() {
		add_filter( 'update_plugins_github.com', array( $this, 'filter_update' ), 10, 4 );
	}

	/**
	 * Supply update information for this plugin only.
	 *
	 * @param array|false $update WordPress's existing update response.
	 * @param array       $plugin_data Parsed plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @param string[]    $locales Installed locales.
	 * @return array|false
	 */
	public function filter_update( $update, $plugin_data, $plugin_file, $locales ) {
		unset( $locales );

		if ( $update || plugin_basename( SEOGP_FILE ) !== $plugin_file ) {
			return $update;
		}
		if ( empty( $plugin_data['UpdateURI'] ) || self::REPOSITORY_URL !== untrailingslashit( $plugin_data['UpdateURI'] ) ) {
			return false;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return false;
		}

		return array(
			'id'           => self::REPOSITORY_URL,
			'slug'         => 'seo-for-generatepress',
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires_php' => '7.4',
			'autoupdate'   => false,
		);
	}

	/** @return array<string, string>|false */
	private function get_latest_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return ! empty( $cached['unavailable'] ) ? false : $cached;
		}

		$response = wp_safe_remote_get(
			self::RELEASE_API_URL,
			array(
				'timeout'     => 5,
				'redirection' => 3,
				'headers'     => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'SEO-for-GeneratePress/' . SEOGP_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $this->cache_unavailable();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
			return $this->cache_unavailable();
		}

		$version = isset( $data['tag_name'] ) ? ltrim( (string) $data['tag_name'], 'vV' ) : '';
		$url     = isset( $data['html_url'] ) ? esc_url_raw( $data['html_url'] ) : '';
		$package = $this->find_package_url( isset( $data['assets'] ) && is_array( $data['assets'] ) ? $data['assets'] : array() );

		if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) || ! $url || ! $package ) {
			return $this->cache_unavailable();
		}

		$release = array( 'version' => $version, 'url' => $url, 'package' => $package );
		set_site_transient( self::CACHE_KEY, $release, 12 * HOUR_IN_SECONDS );

		return $release;
	}

	/** @param array<int, array<string, mixed>> $assets Release assets. @return string */
	private function find_package_url( $assets ) {
		foreach ( $assets as $asset ) {
			if ( empty( $asset['name'] ) || self::ASSET_NAME !== $asset['name'] || empty( $asset['browser_download_url'] ) ) {
				continue;
			}

			$url  = esc_url_raw( $asset['browser_download_url'], array( 'https' ) );
			$host = wp_parse_url( $url, PHP_URL_HOST );
			$path = wp_parse_url( $url, PHP_URL_PATH );

			if ( 'github.com' === $host && is_string( $path ) && 0 === strpos( $path, '/angelablake/SEO-for-GeneratePress/releases/download/' ) && self::ASSET_NAME === basename( $path ) ) {
				return $url;
			}
		}

		return '';
	}

	/** Cache a failed check briefly. @return false */
	private function cache_unavailable() {
		set_site_transient( self::CACHE_KEY, array( 'unavailable' => true ), HOUR_IN_SECONDS );
		return false;
	}
}
