<?php
/**
 * GeneratePress admin-dashboard integration.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the plugin screen to GeneratePress's supported dashboard extension points.
 */
final class GeneratePress_Integration {
	/**
	 * Admin service.
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Environment service.
	 *
	 * @var Environment
	 */
	private $environment;

	/**
	 * Constructor.
	 *
	 * @param Admin       $admin Admin service.
	 * @param Environment $environment Environment service.
	 */
	public function __construct( Admin $admin, Environment $environment ) {
		$this->admin       = $admin;
		$this->environment = $environment;
	}

	/**
	 * Register GeneratePress filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! $this->environment->is_generatepress_active() ) {
			return;
		}

		// Run after GP Premium so SEO follows its Font Library tab when available.
		add_filter( 'generate_dashboard_tabs', array( $this, 'add_dashboard_tab' ), 99 );
		add_filter( 'generate_dashboard_screens', array( $this, 'add_dashboard_screen' ) );
	}

	/**
	 * Add the SEO tab to the GeneratePress header.
	 *
	 * @param array<string, array<string, mixed>> $tabs Existing dashboard tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_dashboard_tab( $tabs ) {
		$current_screen = get_current_screen();
		$is_active      = $current_screen && $this->admin->get_hook_suffix() === $current_screen->id;

		$tabs['seo'] = array(
			'name'  => __( 'SEO', 'seo-for-generatepress' ),
			'url'   => $this->admin->get_url(),
			'class' => $is_active ? 'active' : '',
		);

		return $tabs;
	}

	/**
	 * Tell GeneratePress to render its dashboard header and styles on our screen.
	 *
	 * @param string[] $screens Existing GeneratePress dashboard screen IDs.
	 * @return string[]
	 */
	public function add_dashboard_screen( $screens ) {
		$hook_suffix = $this->admin->get_hook_suffix();

		if ( $hook_suffix && ! in_array( $hook_suffix, $screens, true ) ) {
			$screens[] = $hook_suffix;
		}

		return $screens;
	}
}
