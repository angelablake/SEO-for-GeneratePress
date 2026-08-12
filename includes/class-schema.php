<?php
/**
 * Structured data output.
 *
 * @package SEOForGeneratePress
 */

namespace AngelaBlake\SEOForGeneratePress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Outputs a connected JSON-LD graph for the site and supported page types. */
final class Schema {
	/** @var Settings */
	private $settings;

	/** @var Compatibility */
	private $compatibility;

	/** @var Metadata */
	private $metadata;

	/**
	 * Constructor.
	 *
	 * @param Settings      $settings Settings service.
	 * @param Compatibility $compatibility Compatibility service.
	 * @param Metadata      $metadata Metadata service.
	 */
	public function __construct( Settings $settings, Compatibility $compatibility, Metadata $metadata ) {
		$this->settings      = $settings;
		$this->compatibility = $compatibility;
		$this->metadata      = $metadata;
	}

	/** Register frontend hooks. */
	public function register_hooks() {
		add_action( 'wp_head', array( $this, 'render' ), 20 );
	}

	/** Output the JSON-LD graph. */
	public function render() {
		if ( is_admin() || is_feed() || is_404() || is_search() || $this->compatibility->get_conflicting_seo_plugin() ) {
			return;
		}

		$settings = $this->settings->get_all();
		$name     = trim( wp_strip_all_tags( get_bloginfo( 'name' ) ) );
		$home_url = home_url( '/' );

		if ( '' === $name || ! $home_url ) {
			return;
		}

		$identity_id = $home_url . '#identity';
		$website_id  = $home_url . '#website';
		$graph       = array(
			$this->get_website_entity( $website_id, $identity_id, $name, $home_url ),
			$this->get_identity_entity( $settings, $identity_id, $name, $home_url ),
		);

		$page_entities = $this->get_page_entities( $website_id, $identity_id );
		if ( $page_entities ) {
			$graph = array_merge( $graph, $page_entities );
		}

		echo "\n<!-- SEO for GeneratePress structured data -->\n<script type=\"application/ld+json\">" . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n<!-- /SEO for GeneratePress structured data -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/** @return array<string, mixed> WebSite entity. */
	private function get_website_entity( $website_id, $identity_id, $name, $home_url ) {
		return array(
			'@type'     => 'WebSite',
			'@id'       => $website_id,
			'url'       => $home_url,
			'name'      => $name,
			'publisher' => array( '@id' => $identity_id ),
		);
	}

	/** @return array<string, mixed> Organization or Person site identity. */
	private function get_identity_entity( $settings, $identity_id, $name, $home_url ) {
		$is_person = 'person' === $settings['identity_type'];
		$identity  = array(
			'@type' => $is_person ? 'Person' : 'Organization',
			'@id'   => $identity_id,
			'name'  => $name,
			'url'   => $home_url,
		);

		$image_id = $is_person && ! empty( $settings['person_photo_id'] ) ? absint( $settings['person_photo_id'] ) : absint( get_theme_mod( 'custom_logo' ) );
		$image    = $image_id ? wp_get_attachment_image_src( $image_id, 'full' ) : false;

		if ( $image ) {
			$image_object = array(
				'@type'  => 'ImageObject',
				'@id'    => $home_url . ( $is_person ? '#person-image' : '#organization-logo' ),
				'url'    => $image[0],
				'width'  => $image[1],
				'height' => $image[2],
			);
			$identity[ $is_person ? 'image' : 'logo' ] = $image_object;
		}

		if ( ! empty( $settings['social_urls'] ) ) {
			$identity['sameAs'] = array_values( $settings['social_urls'] );
		}

		return $identity;
	}

	/**
	 * Build page-level entities for supported WordPress views.
	 *
	 * @param string $website_id WebSite entity ID.
	 * @param string $identity_id Publisher entity ID.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_page_entities( $website_id, $identity_id ) {
		if ( ! $this->metadata->is_supported_view() ) {
			return array();
		}

		$canonical   = $this->metadata->get_canonical_url();
		$title       = $this->metadata->get_schema_title();
		$description = $this->metadata->get_description();
		$image       = $this->metadata->get_image();

		if ( ! $canonical || ! $title ) {
			return array();
		}

		if ( is_singular( 'post' ) ) {
			return $this->get_blog_posting_entities( $canonical, $title, $description, $image, $website_id, $identity_id );
		}

		if ( is_author() ) {
			return $this->get_profile_entities( $canonical, $title, $description, $image, $website_id );
		}

		$page = array(
			'@type'    => 'WebPage',
			'@id'      => $canonical . '#webpage',
			'url'      => $canonical,
			'name'     => $title,
			'isPartOf' => array( '@id' => $website_id ),
			'about'    => array( '@id' => $identity_id ),
		);

		if ( $description ) {
			$page['description'] = $description;
		}
		if ( ! empty( $image['url'] ) ) {
			$page['primaryImageOfPage'] = $this->get_image_object( $image, $canonical . '#primaryimage' );
		}

		return array( $page );
	}

	/** @return array<int, array<string, mixed>> BlogPosting plus author entity. */
	private function get_blog_posting_entities( $canonical, $title, $description, $image, $website_id, $identity_id ) {
		$post       = get_queried_object();
		$author_id  = $post instanceof \WP_Post ? (int) $post->post_author : 0;
		$author_url = $author_id ? get_author_posts_url( $author_id ) : '';
		$author_ref = $author_url ? $author_url . '#person' : '';
		$article    = array(
			'@type'            => 'BlogPosting',
			'@id'              => $canonical . '#article',
			'url'              => $canonical,
			'headline'         => $title,
			'mainEntityOfPage' => array( '@id' => $canonical . '#webpage' ),
			'isPartOf'         => array( '@id' => $website_id ),
			'publisher'        => array( '@id' => $identity_id ),
			'datePublished'    => get_the_date( DATE_W3C ),
			'dateModified'     => get_the_modified_date( DATE_W3C ),
		);

		if ( $description ) {
			$article['description'] = $description;
		}
		if ( $author_ref ) {
			$article['author'] = array( '@id' => $author_ref );
		}
		if ( ! empty( $image['url'] ) ) {
			$article['image'] = $this->get_image_object( $image, $canonical . '#primaryimage' );
		}

		$webpage = array(
			'@type'     => 'WebPage',
			'@id'       => $canonical . '#webpage',
			'url'       => $canonical,
			'name'      => $title,
			'isPartOf'  => array( '@id' => $website_id ),
			'mainEntity' => array( '@id' => $canonical . '#article' ),
		);
		if ( $description ) {
			$webpage['description'] = $description;
		}
		if ( ! empty( $image['url'] ) ) {
			$webpage['primaryImageOfPage'] = array( '@id' => $canonical . '#primaryimage' );
		}

		$entities = array( $webpage, $article );
		if ( $author_id && $author_ref ) {
			$author = array(
				'@type' => 'Person',
				'@id'   => $author_ref,
				'name'  => get_the_author_meta( 'display_name', $author_id ),
				'url'   => $author_url,
			);
			$avatar = get_avatar_url( $author_id, array( 'size' => 512 ) );
			if ( $avatar ) {
				$author['image'] = array( '@type' => 'ImageObject', 'url' => $avatar );
			}
			$entities[] = $author;
		}

		return $entities;
	}

	/** @return array<int, array<string, mixed>> ProfilePage plus Person entity. */
	private function get_profile_entities( $canonical, $title, $description, $image, $website_id ) {
		$author_id = (int) get_queried_object_id();
		$person_id = $canonical . '#person';
		$profile   = array(
			'@type'      => 'ProfilePage',
			'@id'        => $canonical . '#profilepage',
			'url'        => $canonical,
			'name'       => $title,
			'isPartOf'   => array( '@id' => $website_id ),
			'mainEntity' => array( '@id' => $person_id ),
		);
		$person = array(
			'@type' => 'Person',
			'@id'   => $person_id,
			'name'  => get_the_author_meta( 'display_name', $author_id ),
			'url'   => $canonical,
		);

		if ( $description ) {
			$profile['description'] = $description;
			$person['description']  = $description;
		}
		if ( ! empty( $image['url'] ) ) {
			$person['image'] = $this->get_image_object( $image, $canonical . '#personimage' );
		}

		return array( $profile, $person );
	}

	/** @return array<string, int|string> Schema ImageObject. */
	private function get_image_object( $image, $id ) {
		$object = array(
			'@type' => 'ImageObject',
			'@id'   => $id,
			'url'   => $image['url'],
		);

		foreach ( array( 'width', 'height' ) as $property ) {
			if ( ! empty( $image[ $property ] ) ) {
				$object[ $property ] = (int) $image[ $property ];
			}
		}

		if ( ! empty( $image['alt'] ) ) {
			$object['caption'] = $image['alt'];
		}

		return $object;
	}
}
