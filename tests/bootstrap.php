<?php
/** Minimal WordPress test doubles for isolated plugin behavior tests. */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'SEOGP_VERSION', '0.4.3' );
define( 'SEOGP_FILE', dirname( __DIR__ ) . '/seo-for-generatepress.php' );

class WP_Post {
	public $ID;
	public $post_author;
	public $post_type;

	public function __construct( $id = 1, $author = 2, $post_type = 'post' ) {
		$this->ID          = $id;
		$this->post_author = $author;
		$this->post_type   = $post_type;
	}
}

$GLOBALS['seogp_test'] = array();

function seogp_test_reset() {
	$GLOBALS['seogp_test'] = array(
		'view'        => 'post',
		'object_id'   => 10,
		'object'      => new WP_Post( 10, 2, 'post' ),
		'meta'        => array(),
		'options'     => array(),
		'query_vars'  => array(),
		'excerpt'     => 'Default excerpt',
		'title'       => 'Default title',
		'doc_title'   => 'Default title – Example Site',
		'bloginfo'    => array( 'name' => 'Example Site', 'description' => 'Example tagline', 'charset' => 'UTF-8' ),
		'theme_mods'  => array(),
		'post_types'  => array( 10 => 'post' ),
		'authors'     => array( 2 => array( 'display_name' => 'Test Author', 'description' => 'Author biography' ) ),
		'transients'  => array(),
		'http'        => null,
	);
}

seogp_test_reset();

function get_option( $key, $default = false ) { return isset( $GLOBALS['seogp_test']['options'][ $key ] ) ? $GLOBALS['seogp_test']['options'][ $key ] : $default; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function absint( $value ) { return abs( (int) $value ); }
function get_site_option( $key, $default = false ) { return $default; }
function is_multisite() { return false; }
function is_front_page() { return 'front' === $GLOBALS['seogp_test']['view']; }
function is_page() { return in_array( $GLOBALS['seogp_test']['view'], array( 'page', 'front-page' ), true ); }
function is_author() { return 'author' === $GLOBALS['seogp_test']['view']; }
function is_admin() { return 'admin' === $GLOBALS['seogp_test']['view']; }
function is_feed() { return 'feed' === $GLOBALS['seogp_test']['view']; }
function is_404() { return '404' === $GLOBALS['seogp_test']['view']; }
function is_search() { return 'search' === $GLOBALS['seogp_test']['view']; }
function is_singular( $types = null ) {
	$type = in_array( $GLOBALS['seogp_test']['view'], array( 'post', 'page', 'front-page' ), true ) ? str_replace( 'front-', '', $GLOBALS['seogp_test']['view'] ) : '';
	if ( null === $types ) { return '' !== $type; }
	return in_array( $type, (array) $types, true );
}
function get_queried_object_id() { return $GLOBALS['seogp_test']['object_id']; }
function get_queried_object() { return $GLOBALS['seogp_test']['object']; }
function get_post_meta( $id, $key, $single = false ) { return isset( $GLOBALS['seogp_test']['meta'][ $id ][ $key ] ) ? $GLOBALS['seogp_test']['meta'][ $id ][ $key ] : ''; }
function get_post_type( $id ) { return isset( $GLOBALS['seogp_test']['post_types'][ $id ] ) ? $GLOBALS['seogp_test']['post_types'][ $id ] : ''; }
function get_permalink() { return 'https://example.com/test-post/'; }
function home_url( $path = '' ) { return 'https://example.com' . $path; }
function get_author_posts_url( $id ) { return 'https://example.com/author/test/'; }
function get_query_var( $key ) { return isset( $GLOBALS['seogp_test']['query_vars'][ $key ] ) ? $GLOBALS['seogp_test']['query_vars'][ $key ] : 0; }
function get_pagenum_link( $page ) { return 'https://example.com/page/' . $page . '/'; }
function wp_get_document_title() { return $GLOBALS['seogp_test']['doc_title']; }
function get_the_title() { return $GLOBALS['seogp_test']['title']; }
function get_the_excerpt() { return $GLOBALS['seogp_test']['excerpt']; }
function get_bloginfo( $key ) { return isset( $GLOBALS['seogp_test']['bloginfo'][ $key ] ) ? $GLOBALS['seogp_test']['bloginfo'][ $key ] : ''; }
function post_password_required() { return false; }
function get_theme_mod( $key ) { return isset( $GLOBALS['seogp_test']['theme_mods'][ $key ] ) ? $GLOBALS['seogp_test']['theme_mods'][ $key ] : 0; }
function has_post_thumbnail() { return false; }
function get_avatar_url() { return ''; }
function get_the_author_meta( $key, $id ) { return isset( $GLOBALS['seogp_test']['authors'][ $id ][ $key ] ) ? $GLOBALS['seogp_test']['authors'][ $id ][ $key ] : ''; }
function get_the_date() { return '2026-08-11T12:00:00+00:00'; }
function get_the_modified_date() { return '2026-08-12T12:00:00+00:00'; }
function wp_strip_all_tags( $text ) { return strip_tags( $text ); }
function strip_shortcodes( $text ) { return preg_replace( '/\[[^\]]+\]/', '', $text ); }
function wp_html_excerpt( $text, $length, $more ) { return mb_strlen( $text ) > $length ? mb_substr( $text, 0, $length ) . $more : $text; }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return $url; }
function get_locale() { return 'en_US'; }
function did_action() { return 1; }
function wp_get_attachment_image_src() { return false; }
function plugin_basename() { return 'seo-for-generatepress/seo-for-generatepress.php'; }
function untrailingslashit( $value ) { return rtrim( $value, '/\\' ); }
function get_site_transient( $key ) { return isset( $GLOBALS['seogp_test']['transients'][ $key ] ) ? $GLOBALS['seogp_test']['transients'][ $key ] : false; }
function set_site_transient( $key, $value, $expiration ) { $GLOBALS['seogp_test']['transients'][ $key ] = $value; return true; }
function wp_safe_remote_get() { return $GLOBALS['seogp_test']['http']; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code( $response ) { return isset( $response['response']['code'] ) ? $response['response']['code'] : 0; }
function wp_remote_retrieve_body( $response ) { return isset( $response['body'] ) ? $response['body'] : ''; }
function esc_url_raw( $url ) { return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : ''; }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }

class WP_Error {}

require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-compatibility.php';
require_once dirname( __DIR__ ) . '/includes/class-content-controls.php';
require_once dirname( __DIR__ ) . '/includes/class-metadata.php';
require_once dirname( __DIR__ ) . '/includes/class-schema.php';
require_once dirname( __DIR__ ) . '/includes/class-updater.php';
