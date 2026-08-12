<?php
/**
 * Per-post and per-page SEO controls.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registers editor controls and applies per-content indexing behavior. */
final class Content_Controls {
	/** @var Compatibility */
	private $compatibility;

	/** Post-meta key for the optional search title. */
	const TITLE_META = '_seogp_search_title';

	/** Post-meta key for the optional description. */
	const DESCRIPTION_META = '_seogp_meta_description';

	/** Post-meta key for search exclusion. */
	const NOINDEX_META = '_seogp_noindex';

	/** Nonce action used by the Classic Editor meta box. */
	const NONCE_ACTION = 'seogp_save_content_controls';

	/** @param Compatibility $compatibility Compatibility service. */
	public function __construct( Compatibility $compatibility ) {
		$this->compatibility = $compatibility;
	}

	/** Register WordPress hooks. */
	public function register_hooks() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_classic_editor_assets' ) );
		add_action( 'add_meta_boxes_post', array( $this, 'add_classic_meta_box' ) );
		add_action( 'add_meta_boxes_page', array( $this, 'add_classic_meta_box' ) );
		add_action( 'save_post_post', array( $this, 'save_classic_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save_classic_meta_box' ) );
		add_filter( 'document_title_parts', array( $this, 'filter_document_title' ) );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'filter_sitemap_query' ), 10, 2 );
		add_filter( 'jetpack_sitemap_skip_post', array( $this, 'filter_jetpack_sitemap_post' ), 10, 2 );
	}

	/** Register private REST-editable metadata for posts and pages. */
	public function register_meta() {
		foreach ( array( 'post', 'page' ) as $post_type ) {
			register_post_meta(
				$post_type,
				self::TITLE_META,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'revisions_enabled' => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => array( $this, 'can_edit_meta' ),
				)
			);

			register_post_meta(
				$post_type,
				self::DESCRIPTION_META,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'revisions_enabled' => true,
					'sanitize_callback' => 'sanitize_textarea_field',
					'auth_callback'     => array( $this, 'can_edit_meta' ),
				)
			);

			register_post_meta(
				$post_type,
				self::NOINDEX_META,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'default'           => false,
					'show_in_rest'      => true,
					'revisions_enabled' => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'auth_callback'     => array( $this, 'can_edit_meta' ),
				)
			);
		}
	}

	/**
	 * Authorize protected post-meta edits.
	 *
	 * @param bool   $allowed WordPress's existing decision.
	 * @param string $meta_key Meta key being edited.
	 * @param int    $post_id Post ID.
	 * @return bool
	 */
	public function can_edit_meta( $allowed, $meta_key, $post_id ) {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $post_id );
	}

	/** Load the block-editor document panel on posts and pages. */
	public function enqueue_block_editor_assets() {
		$screen = get_current_screen();
		if ( $this->compatibility->get_conflicting_seo_plugin() || ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'seo-for-generatepress-editor',
			SEOGP_URL . 'assets/js/editor.js',
			array( 'wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
			SEOGP_VERSION,
			true
		);
		wp_localize_script(
			'seo-for-generatepress-editor',
			'seogpEditor',
			array(
				'titleMeta'       => self::TITLE_META,
				'descriptionMeta' => self::DESCRIPTION_META,
				'noindexMeta'     => self::NOINDEX_META,
				'characters'      => __( '%d characters', 'seo-for-generatepress' ),
			)
		);
		wp_enqueue_style( 'seo-for-generatepress-editor', SEOGP_URL . 'assets/css/editor.css', array(), SEOGP_VERSION );
	}

	/** Load shared styling for the Classic Editor meta box. */
	public function enqueue_classic_editor_assets() {
		$screen = get_current_screen();
		if ( $this->compatibility->get_conflicting_seo_plugin() || ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'seo-for-generatepress-editor', SEOGP_URL . 'assets/css/editor.css', array(), SEOGP_VERSION );
	}

	/** Add a meta box only when the current post is not using the block editor. */
	public function add_classic_meta_box( $post ) {
		if ( $this->compatibility->get_conflicting_seo_plugin() || ( function_exists( 'use_block_editor_for_post' ) && use_block_editor_for_post( $post ) ) ) {
			return;
		}

		add_meta_box(
			'seogp-content-controls',
			__( 'SEO', 'seo-for-generatepress' ),
			array( $this, 'render_classic_meta_box' ),
			$post->post_type,
			'normal',
			'default'
		);
	}

	/** Render the Classic Editor fields. */
	public function render_classic_meta_box( $post ) {
		$title       = get_post_meta( $post->ID, self::TITLE_META, true );
		$description = get_post_meta( $post->ID, self::DESCRIPTION_META, true );
		$noindex     = (bool) get_post_meta( $post->ID, self::NOINDEX_META, true );
		wp_nonce_field( self::NONCE_ACTION, 'seogp_content_controls_nonce' );
		?>
		<div class="seogp-classic-field">
			<label for="seogp-search-title"><strong><?php esc_html_e( 'Search title', 'seo-for-generatepress' ); ?></strong></label>
			<input type="text" class="widefat" id="seogp-search-title" name="seogp_search_title" value="<?php echo esc_attr( $title ); ?>">
			<p class="description"><?php esc_html_e( 'Use a different title in search results, browser tabs, and social shares.', 'seo-for-generatepress' ); ?></p>
		</div>
		<div class="seogp-classic-field">
			<label for="seogp-meta-description"><strong><?php esc_html_e( 'Meta description', 'seo-for-generatepress' ); ?></strong></label>
			<textarea class="widefat" id="seogp-meta-description" name="seogp_meta_description" rows="4"><?php echo esc_textarea( $description ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Summarize this content for search results and social shares. Aim for 120–160 characters.', 'seo-for-generatepress' ); ?></p>
		</div>
		<div class="seogp-classic-field">
			<label><input type="checkbox" name="seogp_noindex" value="1" <?php checked( $noindex ); ?>> <strong><?php esc_html_e( 'Hide from search results', 'seo-for-generatepress' ); ?></strong></label>
			<p class="description"><?php esc_html_e( 'Keep this content out of search results and supported sitemaps. Anyone with the URL can still view it.', 'seo-for-generatepress' ); ?></p>
		</div>
		<?php
	}

	/** Save Classic Editor fields. */
	public function save_classic_meta_box( $post_id ) {
		if ( ! isset( $_POST['seogp_content_controls_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seogp_content_controls_nonce'] ) ), self::NONCE_ACTION )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$title = isset( $_POST['seogp_search_title'] ) ? sanitize_text_field( wp_unslash( $_POST['seogp_search_title'] ) ) : '';
		$description = isset( $_POST['seogp_meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seogp_meta_description'] ) ) : '';

		$this->save_or_delete_meta( $post_id, self::TITLE_META, $title );
		$this->save_or_delete_meta( $post_id, self::DESCRIPTION_META, $description );
		$this->save_or_delete_meta( $post_id, self::NOINDEX_META, ! empty( $_POST['seogp_noindex'] ) ? '1' : '' );
	}

	/** Apply the optional title override while preserving WordPress title formatting. */
	public function filter_document_title( $parts ) {
		if ( $this->compatibility->get_conflicting_seo_plugin() ) {
			return $parts;
		}

		if ( is_singular( array( 'post', 'page' ) ) ) {
			$override = get_post_meta( get_queried_object_id(), self::TITLE_META, true );
			if ( $override ) {
				$parts['title'] = $override;
			}
		}

		return $parts;
	}

	/** Add noindex, follow to opted-out posts and pages. */
	public function filter_robots( $robots ) {
		if ( $this->compatibility->get_conflicting_seo_plugin() ) {
			return $robots;
		}

		if ( is_singular( array( 'post', 'page' ) ) && get_post_meta( get_queried_object_id(), self::NOINDEX_META, true ) ) {
			unset( $robots['index'], $robots['nofollow'] );
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}

		return $robots;
	}

	/** Exclude noindexed posts and pages from WordPress core sitemap queries. */
	public function filter_sitemap_query( $args, $post_type ) {
		if ( $this->compatibility->get_conflicting_seo_plugin() || ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			return $args;
		}

		$visibility_query = array(
			'relation' => 'OR',
			array(
				'key'     => self::NOINDEX_META,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => self::NOINDEX_META,
				'value'   => '1',
				'compare' => '!=',
			),
		);

		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = $visibility_query;
		} else {
			$args['meta_query'] = array(
				'relation' => 'AND',
				$args['meta_query'],
				$visibility_query,
			);
		}

		return $args;
	}

	/**
	 * Exclude noindexed posts and pages from Jetpack's sitemap.
	 *
	 * @param bool   $skip Whether Jetpack already plans to skip the post.
	 * @param object $post Jetpack sitemap post record.
	 * @return bool
	 */
	public function filter_jetpack_sitemap_post( $skip, $post ) {
		if ( $skip || $this->compatibility->get_conflicting_seo_plugin() || empty( $post->ID ) ) {
			return (bool) $skip;
		}

		$post_type = get_post_type( (int) $post->ID );
		if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			return false;
		}

		return (bool) get_post_meta( (int) $post->ID, self::NOINDEX_META, true );
	}

	/** Save a non-empty value or remove the unused metadata row. */
	private function save_or_delete_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
