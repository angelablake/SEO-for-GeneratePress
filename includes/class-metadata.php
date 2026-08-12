<?php
/**
 * Automatic page metadata.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Outputs canonical, description, Open Graph, and Twitter card metadata. */
final class Metadata {
	/** @var Settings */
	private $settings;

	/** @var Compatibility */
	private $compatibility;

	/**
	 * Constructor.
	 *
	 * @param Settings      $settings Settings service.
	 * @param Compatibility $compatibility Compatibility service.
	 */
	public function __construct( Settings $settings, Compatibility $compatibility ) {
		$this->settings      = $settings;
		$this->compatibility = $compatibility;
	}

	/** Register frontend hooks. */
	public function register_hooks() {
		add_action( 'wp', array( $this, 'prepare_head' ) );
		add_action( 'wp_head', array( $this, 'render' ), 5 );
		add_filter( 'jetpack_enable_open_graph', array( $this, 'filter_jetpack_open_graph' ), PHP_INT_MAX );
	}

	/** Remove WordPress's singular canonical when this service will replace it. */
	public function prepare_head() {
		if ( $this->should_render() && $this->get_canonical_url() ) {
			remove_action( 'wp_head', 'rel_canonical' );
		}
	}

	/**
	 * Let this plugin own social tags only on its supported views.
	 *
	 * @param bool $enabled Whether Jetpack should output Open Graph tags.
	 * @return bool
	 */
	public function filter_jetpack_open_graph( $enabled ) {
		if ( ! did_action( 'wp' ) ) {
			return $enabled;
		}

		return $this->should_render() ? false : $enabled;
	}

	/** @return bool Whether page-specific metadata is supported for this request. */
	public function is_supported_view() {
		return is_front_page() || is_singular( 'post' ) || is_page() || is_author();
	}

	/** @return bool Whether metadata should be emitted. */
	public function should_render() {
		return ! is_admin()
			&& ! is_feed()
			&& ! is_404()
			&& ! is_search()
			&& $this->is_supported_view()
			&& ! $this->compatibility->get_conflicting_seo_plugin();
	}

	/** @return string Canonical URL for the current supported view. */
	public function get_canonical_url() {
		if ( is_front_page() ) {
			$url = home_url( '/' );
			$paged = max( 1, (int) get_query_var( 'paged' ) );

			return $paged > 1 ? (string) get_pagenum_link( $paged ) : (string) $url;
		}

		if ( is_singular( array( 'post', 'page' ) ) ) {
			return (string) get_permalink();
		}

		if ( is_author() ) {
			$url = get_author_posts_url( (int) get_queried_object_id() );
		} else {
			return '';
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );

		return $paged > 1 ? (string) get_pagenum_link( $paged ) : (string) $url;
	}

	/** @return string Clean title for metadata and schema. */
	public function get_title() {
		if ( is_front_page() ) {
			$override = is_page() ? get_post_meta( get_queried_object_id(), Content_Controls::TITLE_META, true ) : '';
			if ( $override ) {
				return $this->clean_text( $override, 0 );
			}

			return $this->clean_text( wp_get_document_title(), 0 );
		}

		if ( is_singular( array( 'post', 'page' ) ) ) {
			$override = get_post_meta( get_queried_object_id(), Content_Controls::TITLE_META, true );

			return $this->clean_text( $override ? $override : get_the_title(), 0 );
		}

		return $this->clean_text( wp_get_document_title(), 0 );
	}

	/** @return string Actual WordPress content title for structured data. */
	public function get_schema_title() {
		if ( is_singular( array( 'post', 'page' ) ) ) {
			return $this->clean_text( get_the_title(), 0 );
		}

		return $this->clean_text( wp_get_document_title(), 0 );
	}

	/** @return string Description derived from native WordPress content. */
	public function get_description() {
		$length = 160;

		if ( is_front_page() ) {
			$override = is_page() ? get_post_meta( get_queried_object_id(), Content_Controls::DESCRIPTION_META, true ) : '';
			$text     = $override ? $override : get_bloginfo( 'description' );
			$length   = $override ? 0 : 160;
		} elseif ( is_singular( array( 'post', 'page' ) ) && ! post_password_required() ) {
			$post = get_queried_object();
			$override = get_post_meta( get_queried_object_id(), Content_Controls::DESCRIPTION_META, true );
			$text = $override ? $override : ( $post instanceof \WP_Post ? get_the_excerpt( $post ) : '' );
			$length = $override ? 0 : 160;
		} elseif ( is_author() ) {
			$text = get_the_author_meta( 'description', (int) get_queried_object_id() );
		} else {
			$text = '';
		}

		return $this->clean_text( $text, $length );
	}

	/**
	 * Get the best available social image.
	 *
	 * @return array<string, int|string>
	 */
	public function get_image() {
		$attachment_id = 0;

		if ( is_front_page() ) {
			$attachment_id = (int) get_theme_mod( 'custom_logo' );
		} elseif ( is_singular( array( 'post', 'page' ) ) && has_post_thumbnail() ) {
			$attachment_id = (int) get_post_thumbnail_id();
		} elseif ( is_author() ) {
			$author_id  = (int) get_queried_object_id();
			$avatar_url = get_avatar_url( $author_id, array( 'size' => 512 ) );
			if ( $avatar_url ) {
				return array(
					'url' => $avatar_url,
					'alt' => get_the_author_meta( 'display_name', $author_id ),
				);
			}
		}

		if ( ! $attachment_id ) {
			$attachment_id = (int) get_theme_mod( 'custom_logo' );
		}

		if ( ! $attachment_id ) {
			return array();
		}

		$image = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( ! $image ) {
			return array();
		}

		$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( ! $alt ) {
			$alt = get_the_title( $attachment_id );
		}

		return array(
			'url'    => $image[0],
			'width'  => (int) $image[1],
			'height' => (int) $image[2],
			'alt'    => $this->clean_text( $alt, 0 ),
		);
	}

	/** Output supported metadata tags. */
	public function render() {
		if ( ! $this->should_render() ) {
			return;
		}

		echo "\n<!-- SEO for GeneratePress metadata -->";

		$title       = $this->get_title();
		$description = $this->get_description();
		$canonical   = $this->get_canonical_url();
		$image       = $this->get_image();
		$type        = is_singular( 'post' ) ? 'article' : 'website';

		if ( $description ) {
			$this->meta( 'name', 'description', $description );
		}
		if ( $canonical ) {
			printf( "\n<link rel=\"canonical\" href=\"%s\">", esc_url( $canonical ) );
		}

		$this->meta( 'property', 'og:locale', get_locale() );
		$this->meta( 'property', 'og:type', $type );
		$this->meta( 'property', 'og:title', $title );
		$this->meta( 'property', 'og:url', $canonical );
		$this->meta( 'property', 'og:site_name', get_bloginfo( 'name' ) );

		if ( $description ) {
			$this->meta( 'property', 'og:description', $description );
		}

		if ( is_singular( 'post' ) ) {
			$this->meta( 'property', 'article:published_time', get_the_date( DATE_W3C ) );
			$this->meta( 'property', 'article:modified_time', get_the_modified_date( DATE_W3C ) );
		}

		if ( ! empty( $image['url'] ) ) {
			$this->meta( 'property', 'og:image', $image['url'] );
			if ( ! empty( $image['width'] ) ) {
				$this->meta( 'property', 'og:image:width', (string) $image['width'] );
			}
			if ( ! empty( $image['height'] ) ) {
				$this->meta( 'property', 'og:image:height', (string) $image['height'] );
			}
			if ( ! empty( $image['alt'] ) ) {
				$this->meta( 'property', 'og:image:alt', $image['alt'] );
			}
		}

		$this->meta( 'name', 'twitter:card', ! empty( $image['url'] ) ? 'summary_large_image' : 'summary' );
		$this->meta( 'name', 'twitter:title', $title );
		if ( $description ) {
			$this->meta( 'name', 'twitter:description', $description );
		}
		if ( ! empty( $image['url'] ) ) {
			$this->meta( 'name', 'twitter:image', $image['url'] );
			if ( ! empty( $image['alt'] ) ) {
				$this->meta( 'name', 'twitter:image:alt', $image['alt'] );
			}
		}

		echo "\n<!-- /SEO for GeneratePress metadata -->\n";
	}

	/**
	 * Render one escaped meta element.
	 *
	 * @param string $attribute Attribute name: name or property.
	 * @param string $key Metadata key.
	 * @param string $content Metadata value.
	 */
	private function meta( $attribute, $key, $content ) {
		if ( '' === (string) $content ) {
			return;
		}

		printf( "\n<meta %1\$s=\"%2\$s\" content=\"%3\$s\">", esc_attr( $attribute ), esc_attr( $key ), esc_attr( $content ) );
	}

	/**
	 * Normalize text and optionally limit it to a character count.
	 *
	 * @param string $text Source text.
	 * @param int    $length Maximum characters; zero means unlimited.
	 * @return string
	 */
	private function clean_text( $text, $length ) {
		$text = html_entity_decode( wp_strip_all_tags( strip_shortcodes( (string) $text ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) );

		return $length && $text ? wp_html_excerpt( $text, $length, '…' ) : $text;
	}
}
