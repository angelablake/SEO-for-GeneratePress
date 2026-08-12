<?php
/**
 * WordPress admin screen.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the plugin admin experience.
 */
final class Admin {
	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'seo-for-generatepress';

	/**
	 * Environment service.
	 *
	 * @var Environment
	 */
	private $environment;
	/** @var Settings */
	private $settings;
	/** @var Compatibility */
	private $compatibility;
	/** @var Sitemaps */
	private $sitemaps;

	/**
	 * Hook suffix returned by add_menu_page().
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Environment   $environment Environment service.
	 * @param Settings      $settings Settings service.
	 * @param Compatibility $compatibility Compatibility service.
	 * @param Sitemaps      $sitemaps Sitemap service.
	 */
	public function __construct( Environment $environment, Settings $settings, Compatibility $compatibility, Sitemaps $sitemaps ) {
		$this->environment   = $environment;
		$this->settings      = $settings;
		$this->compatibility = $compatibility;
		$this->sitemaps      = $sitemaps;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * GenerateBlocks registers its menu at priority 9. Registering at priority
	 * 10 with no explicit position lets WordPress place this item after it.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SEOGP_FILE ), array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Add the top-level SEO menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->hook_suffix = add_menu_page(
			__( 'SEO for GeneratePress', 'seo-for-generatepress' ),
			__( 'SEO for GP', 'seo-for-generatepress' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-search'
		);
	}

	/**
	 * Add a direct settings link on the Installed Plugins screen.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[]
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $this->get_url() ),
			esc_html__( 'Settings', 'seo-for-generatepress' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Get the admin page hook suffix.
	 *
	 * @return string
	 */
	public function get_hook_suffix() {
		return $this->hook_suffix;
	}

	/**
	 * Get the plugin admin URL.
	 *
	 * @return string
	 */
	public function get_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Load assets only on this plugin's admin screen.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $this->hook_suffix !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'seo-for-generatepress-admin',
			SEOGP_URL . 'assets/css/admin.css',
			array(),
			SEOGP_VERSION
		);

		wp_enqueue_media();
		wp_enqueue_script(
			'seo-for-generatepress-admin',
			SEOGP_URL . 'assets/js/admin.js',
			array(),
			SEOGP_VERSION,
			true
		);
		wp_localize_script(
			'seo-for-generatepress-admin',
			'seogpAdmin',
			array(
				'choosePhoto'  => __( 'Choose a person photo', 'seo-for-generatepress' ),
				'usePhoto'     => __( 'Use this photo', 'seo-for-generatepress' ),
				'chooseButton' => __( 'Choose photo', 'seo-for-generatepress' ),
				'replaceButton' => __( 'Replace photo', 'seo-for-generatepress' ),
				'remove'       => __( 'Remove', 'seo-for-generatepress' ),
			)
		);
	}

	/**
	 * Render the plugin admin screen.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'seo-for-generatepress' ) );
		}

		$tab                       = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab                       = in_array( $tab, array( 'general', 'visibility-access' ), true ) ? $tab : 'general';
		$uses_generatepress_header = $this->environment->is_generatepress_active()
			&& class_exists( 'GeneratePress_Dashboard' );
		?>
		<div class="seogp-wrap">
			<?php if ( ! $uses_generatepress_header ) : ?>
				<h1><?php esc_html_e( 'SEO for GeneratePress', 'seo-for-generatepress' ); ?></h1>
			<?php endif; ?>

			<?php settings_errors(); ?>

			<?php $this->render_conflict_notice(); ?>

			<?php if ( ! $this->environment->is_generatepress_active() ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php esc_html_e( 'GeneratePress is not currently active. Your settings remain available, but GeneratePress-specific integrations are disabled.', 'seo-for-generatepress' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="seogp-page">
				<nav class="seogp-tabs" aria-label="<?php esc_attr_e( 'SEO settings', 'seo-for-generatepress' ); ?>">
					<a class="seogp-tabs__item <?php echo 'general' === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( $this->get_url() ); ?>" <?php echo 'general' === $tab ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'General', 'seo-for-generatepress' ); ?>
					</a>
					<a class="seogp-tabs__item <?php echo 'visibility-access' === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'tab', 'visibility-access', $this->get_url() ) ); ?>" <?php echo 'visibility-access' === $tab ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'Visibility & Access', 'seo-for-generatepress' ); ?>
					</a>
				</nav>

				<main class="seogp-tab-content">
					<?php if ( 'visibility-access' === $tab ) : ?>
						<?php $this->render_visibility_access(); ?>
					<?php else : ?>
					<form action="options.php" method="post" class="seogp-settings-form">
						<?php
						settings_fields( 'seogp_general' );
						do_settings_sections( 'seogp_general' );
						submit_button( __( 'Save settings', 'seo-for-generatepress' ) );
						?>
					</form>
					<?php endif; ?>
				</main>
			</div>
		</div>
		<?php
	}

	/** Display a notice when another SEO plugin is likely to output schema. */
	private function render_conflict_notice() {
		$plugin = $this->compatibility->get_conflicting_seo_plugin();
		if ( ! $plugin ) {
			return;
		}
		?>
		<div class="notice notice-warning inline"><p>
			<?php
			echo esc_html( sprintf(
				/* translators: %s is another SEO plugin name. */
				__( '%s appears to be active. SEO for GeneratePress output and per-content controls are disabled while it is active, preventing conflicting SEO data.', 'seo-for-generatepress' ),
				$plugin
			) );
			?>
		</p></div>
		<?php
	}

	/** Render the read-only visibility and crawler status screen. */
	private function render_visibility_access() {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$is_public        = (bool) get_option( 'blog_public' );
		$physical_robots  = file_exists( trailingslashit( get_home_path() ) . 'robots.txt' );
		$sitemaps         = $this->detect_sitemaps();
		?>
		<section class="seogp-status-section">
			<h3><?php esc_html_e( 'Search visibility', 'seo-for-generatepress' ); ?></h3>
			<?php $this->render_status( $is_public, __( 'Visible to search engines', 'seo-for-generatepress' ), __( 'Search engines are discouraged', 'seo-for-generatepress' ) ); ?>
			<?php $visibility_anchor = has_action( 'blog_privacy_selector' ) ? 'blog-norobots' : 'blog_public'; ?>
			<p><a href="<?php echo esc_url( admin_url( 'options-reading.php#' . $visibility_anchor ) ); ?>"><?php esc_html_e( 'Change Search Engine Visibility', 'seo-for-generatepress' ); ?></a></p>
		</section>
		<section class="seogp-status-section">
			<h3><?php esc_html_e( 'XML sitemap', 'seo-for-generatepress' ); ?></h3>
			<?php $this->render_status( ! empty( $sitemaps ), __( 'Sitemap detected', 'seo-for-generatepress' ), __( 'No sitemap detected', 'seo-for-generatepress' ) ); ?>
			<?php if ( $sitemaps ) : ?>
				<ul class="seogp-sitemap-list">
					<?php foreach ( $sitemaps as $sitemap ) : ?>
						<li>
							<strong><?php echo esc_html( $sitemap['label'] ); ?>:</strong>
							<a href="<?php echo esc_url( $sitemap['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $sitemap['url'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'SEO for GeneratePress could not identify a sitemap from WordPress, Jetpack, robots.txt, or the common /sitemap.xml location. Another sitemap may still exist at a custom URL.', 'seo-for-generatepress' ); ?></p>
			<?php endif; ?>
		</section>
		<section class="seogp-status-section">
			<h3><?php esc_html_e( 'Robots.txt', 'seo-for-generatepress' ); ?></h3>
			<?php $this->render_status( ! $physical_robots, __( 'Managed virtually by WordPress', 'seo-for-generatepress' ), __( 'A physical robots.txt file overrides WordPress', 'seo-for-generatepress' ) ); ?>
			<p><a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( home_url( '/robots.txt' ) ); ?></a></p>
			<?php if ( $physical_robots ) : ?><p class="description"><?php esc_html_e( 'SEO for GeneratePress will not overwrite this file. Any future crawler controls added here would require the physical file to be removed or edited separately.', 'seo-for-generatepress' ); ?></p><?php endif; ?>
		</section>
		<?php
	}

	/** Render a compact status indicator. */
	private function render_status( $good, $good_text, $bad_text ) {
		printf( '<p class="seogp-status %1$s"><span aria-hidden="true"></span>%2$s</p>', $good ? 'is-good' : 'is-warning', esc_html( $good ? $good_text : $bad_text ) );
	}

	/**
	 * Detect sitemap indexes supplied by WordPress, Jetpack, or another provider.
	 *
	 * @return array<int, array{label: string, url: string}>
	 */
	private function detect_sitemaps() {
		$sitemaps  = array();
		$is_public = (bool) get_option( 'blog_public' );
		$discovered = get_transient( Sitemaps::DISCOVERY_TRANSIENT );

		if ( false === $discovered ) {
			$discovered = $this->discover_sitemaps_from_site();
			set_transient( Sitemaps::DISCOVERY_TRANSIENT, $discovered, 6 * HOUR_IN_SECONDS );
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying a WordPress core filter.
		if ( $is_public && (bool) apply_filters( 'wp_sitemaps_enabled', true ) ) {
			$sitemaps[] = array(
				'label' => $this->sitemaps->is_fallback_active() ? __( 'WordPress fallback', 'seo-for-generatepress' ) : __( 'WordPress', 'seo-for-generatepress' ),
				'url'   => home_url( '/wp-sitemap.xml' ),
			);
		}

		if ( class_exists( '\\Jetpack' ) && is_callable( array( '\\Jetpack', 'is_module_active' ) ) && \Jetpack::is_module_active( 'sitemaps' ) ) {
			$sitemaps[] = array(
				'label' => __( 'Jetpack', 'seo-for-generatepress' ),
				'url'   => home_url( '/sitemap.xml' ),
			);
		}

		foreach ( (array) $discovered as $url ) {
			$sitemaps[] = array(
				'label' => __( 'Detected sitemap', 'seo-for-generatepress' ),
				'url'   => $url,
			);
		}

		$unique = array();
		foreach ( $sitemaps as $sitemap ) {
			if ( ! isset( $unique[ $sitemap['url'] ] ) ) {
				$unique[ $sitemap['url'] ] = $sitemap;
			}
		}

		return array_values( $unique );
	}

	/**
	 * Inspect robots.txt and the conventional sitemap URL without downloading a full sitemap.
	 *
	 * @return string[]
	 */
	private function discover_sitemaps_from_site() {
		$urls            = array();
		$request_options = array(
			'timeout'             => 3,
			'redirection'         => 3,
			'limit_response_size' => 8192,
			'user-agent'          => 'SEO for GeneratePress/' . SEOGP_VERSION,
		);
		$robots_response = wp_safe_remote_get( home_url( '/robots.txt' ), $request_options );

		if ( ! is_wp_error( $robots_response ) && 200 === wp_remote_retrieve_response_code( $robots_response ) ) {
			$robots = wp_remote_retrieve_body( $robots_response );
			if ( preg_match_all( '/^\s*Sitemap:\s*(https?:\/\/\S+)\s*$/im', $robots, $matches ) ) {
				foreach ( $matches[1] as $url ) {
					$url = esc_url_raw( trim( $url ), array( 'http', 'https' ) );
					if ( $url && home_url( '/wp-sitemap.xml' ) !== $url && wp_http_validate_url( $url ) ) {
						$urls[] = $url;
					}
				}
			}
		}

		$conventional_url = home_url( '/sitemap.xml' );
		if ( ! in_array( $conventional_url, $urls, true ) ) {
			$sitemap_response = wp_safe_remote_get( $conventional_url, $request_options );
			if ( ! is_wp_error( $sitemap_response ) && 200 === wp_remote_retrieve_response_code( $sitemap_response ) ) {
				$body = ltrim( wp_remote_retrieve_body( $sitemap_response ) );
				if ( false !== stripos( $body, '<urlset' ) || false !== stripos( $body, '<sitemapindex' ) ) {
					$urls[] = $conventional_url;
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

}
