<?php
/** Isolated behavioral tests for core SEO decisions. */

require_once __DIR__ . '/bootstrap.php';

use AngelaBlake\SEOForGeneratePress\Compatibility;
use AngelaBlake\SEOForGeneratePress\Content_Controls;
use AngelaBlake\SEOForGeneratePress\Metadata;
use AngelaBlake\SEOForGeneratePress\Schema;
use AngelaBlake\SEOForGeneratePress\Settings;

$tests = array();

function seogp_test( $name, $callback ) {
	global $tests;
	$tests[ $name ] = $callback;
}

function seogp_assert_same( $expected, $actual, $message = '' ) {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message ? $message : 'Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
	}
}

function seogp_services() {
	$compatibility = new Compatibility();
	return array( new Content_Controls( $compatibility ), new Metadata( new Settings(), $compatibility ) );
}

seogp_test( 'search title overrides only the content title part', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::TITLE_META ] = 'Custom search title';
	list( $controls ) = seogp_services();
	seogp_assert_same( array( 'title' => 'Custom search title', 'site' => 'Example Site' ), $controls->filter_document_title( array( 'title' => 'Default title', 'site' => 'Example Site' ) ) );
} );

seogp_test( 'noindex preserves following links', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::NOINDEX_META ] = '1';
	list( $controls ) = seogp_services();
	seogp_assert_same( array( 'noindex' => true, 'follow' => true ), $controls->filter_robots( array( 'index' => true, 'nofollow' => true ) ) );
} );

seogp_test( 'WordPress sitemap query excludes hidden posts', function () {
	seogp_test_reset();
	list( $controls ) = seogp_services();
	$result = $controls->filter_sitemap_query( array( 'posts_per_page' => 100 ), 'post' );
	seogp_assert_same( 'OR', $result['meta_query']['relation'] );
	seogp_assert_same( Content_Controls::NOINDEX_META, $result['meta_query'][0]['key'] );
} );

seogp_test( 'existing WordPress sitemap filters are retained', function () {
	seogp_test_reset();
	list( $controls ) = seogp_services();
	$result = $controls->filter_sitemap_query( array( 'meta_query' => array( array( 'key' => 'existing' ) ) ), 'page' );
	seogp_assert_same( 'AND', $result['meta_query']['relation'] );
	seogp_assert_same( 'existing', $result['meta_query'][0][0]['key'] );
} );

seogp_test( 'Jetpack skips hidden posts but not unrelated post types', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::NOINDEX_META ] = '1';
	list( $controls ) = seogp_services();
	seogp_assert_same( true, $controls->filter_jetpack_sitemap_post( false, (object) array( 'ID' => 10 ) ) );
	$GLOBALS['seogp_test']['post_types'][10] = 'product';
	seogp_assert_same( false, $controls->filter_jetpack_sitemap_post( false, (object) array( 'ID' => 10 ) ) );
} );

seogp_test( 'manual description wins over the WordPress excerpt', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::DESCRIPTION_META ] = 'Purpose-built search description';
	list( , $metadata ) = seogp_services();
	seogp_assert_same( 'Purpose-built search description', $metadata->get_description() );
} );

seogp_test( 'native excerpt is cleaned and used as the default description', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['excerpt'] = "<p>A  default\nexcerpt [gallery ids=1]</p>";
	list( , $metadata ) = seogp_services();
	seogp_assert_same( 'A default excerpt', $metadata->get_description() );
} );

seogp_test( 'known SEO plugins suppress plugin behavior', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['options']['active_plugins'] = array( 'wordpress-seo/wp-seo.php' );
	list( $controls, $metadata ) = seogp_services();
	$parts = array( 'title' => 'Original' );
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::TITLE_META ] = 'Ignored';
	seogp_assert_same( $parts, $controls->filter_document_title( $parts ) );
	seogp_assert_same( false, $metadata->should_render() );
} );

seogp_test( 'standard posts output a connected BlogPosting graph', function () {
	seogp_test_reset();
	$settings      = new Settings();
	$compatibility = new Compatibility();
	$metadata      = new Metadata( $settings, $compatibility );
	$schema        = new Schema( $settings, $compatibility, $metadata );

	ob_start();
	$schema->render();
	$output = ob_get_clean();
	preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $output, $matches );
	$document = json_decode( isset( $matches[1] ) ? $matches[1] : '', true );
	$types    = array_column( $document['@graph'], '@type' );

	seogp_assert_same( array( 'WebSite', 'Organization', 'WebPage', 'BlogPosting', 'Person' ), $types );
	seogp_assert_same( 'https://example.com/test-post/#webpage', $document['@graph'][3]['mainEntityOfPage']['@id'] );
	seogp_assert_same( 'https://example.com/#identity', $document['@graph'][3]['publisher']['@id'] );
} );

$failures = 0;
foreach ( $tests as $name => $test ) {
	try {
		$test();
		echo "PASS: {$name}\n";
	} catch ( Throwable $error ) {
		++$failures;
		fwrite( STDERR, "FAIL: {$name}\n  {$error->getMessage()}\n" );
	}
}

echo sprintf( "\n%d tests, %d failures.\n", count( $tests ), $failures );
exit( $failures ? 1 : 0 );
