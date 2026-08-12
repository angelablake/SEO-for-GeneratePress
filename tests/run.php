<?php
/** Isolated behavioral tests for core SEO decisions. */

require_once __DIR__ . '/bootstrap.php';

use AngelaBlake\SEOForGeneratePress\Compatibility;
use AngelaBlake\SEOForGeneratePress\Content_Controls;
use AngelaBlake\SEOForGeneratePress\Metadata;
use AngelaBlake\SEOForGeneratePress\Schema;
use AngelaBlake\SEOForGeneratePress\Settings;
use AngelaBlake\SEOForGeneratePress\Sitemaps;
use AngelaBlake\SEOForGeneratePress\Updater;

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

function seogp_render_metadata() {
	list( , $metadata ) = seogp_services();
	ob_start();
	$metadata->render();
	return ob_get_clean();
}

function seogp_render_schema() {
	$settings      = new Settings();
	$compatibility = new Compatibility();
	$metadata      = new Metadata( $settings, $compatibility );
	$schema        = new Schema( $settings, $compatibility, $metadata );
	ob_start();
	$schema->render();
	$output = ob_get_clean();
	preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $output, $matches );
	return isset( $matches[1] ) ? json_decode( $matches[1], true ) : null;
}

function seogp_assert_snapshot( $name, $actual ) {
	$path     = __DIR__ . '/snapshots/' . $name;
	$expected = file_get_contents( $path );
	seogp_assert_same( rtrim( $expected, "\r\n" ), rtrim( $actual, "\r\n" ), 'Snapshot mismatch: ' . $name );
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

seogp_test( 'published GitHub release supplies a native WordPress update', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['http'] = array(
		'response' => array( 'code' => 200 ),
		'body'     => json_encode(
			array(
				'tag_name'  => 'v0.5.0',
				'html_url'  => 'https://github.com/angelablake/SEO-for-GeneratePress/releases/tag/v0.5.0',
				'draft'     => false,
				'prerelease' => false,
				'assets'    => array(
					array(
						'name'                 => 'seo-for-generatepress.zip',
						'browser_download_url' => 'https://github.com/angelablake/SEO-for-GeneratePress/releases/download/v0.5.0/seo-for-generatepress.zip',
					),
				),
			)
		),
	);
	$update = ( new Updater() )->filter_update( false, array( 'UpdateURI' => Updater::REPOSITORY_URL ), 'seo-for-generatepress/seo-for-generatepress.php', array( 'en_US' ) );
	seogp_assert_same( '0.5.0', $update['version'] );
	seogp_assert_same( 'seo-for-generatepress', $update['slug'] );
	seogp_assert_same( false, $update['autoupdate'] );
} );

seogp_test( 'updater rejects a release without the trusted ZIP asset', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['http'] = array(
		'response' => array( 'code' => 200 ),
		'body'     => json_encode(
			array(
				'tag_name' => 'v0.5.0',
				'html_url' => 'https://github.com/angelablake/SEO-for-GeneratePress/releases/tag/v0.5.0',
				'assets'   => array(),
			)
		),
	);
	$update = ( new Updater() )->filter_update( false, array( 'UpdateURI' => Updater::REPOSITORY_URL ), 'seo-for-generatepress/seo-for-generatepress.php', array() );
	seogp_assert_same( false, $update );
} );

seogp_test( 'updater leaves other GitHub-hosted plugins untouched', function () {
	seogp_test_reset();
	$update = ( new Updater() )->filter_update( false, array( 'UpdateURI' => 'https://github.com/example/plugin' ), 'example/plugin.php', array() );
	seogp_assert_same( false, $update );
} );

seogp_test( 'homepage uses site identity defaults', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['view'] = 'front';
	list( , $metadata ) = seogp_services();
	seogp_assert_same( 'https://example.com/', $metadata->get_canonical_url() );
	seogp_assert_same( 'Example tagline', $metadata->get_description() );
} );

seogp_test( 'pages use WebPage schema without BlogPosting', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['view']                 = 'page';
	$GLOBALS['seogp_test']['object']->post_type    = 'page';
	$GLOBALS['seogp_test']['post_types'][10]       = 'page';
	$settings      = new Settings();
	$compatibility = new Compatibility();
	$schema        = new Schema( $settings, $compatibility, new Metadata( $settings, $compatibility ) );
	ob_start();
	$schema->render();
	$output = ob_get_clean();
	seogp_assert_same( true, false !== strpos( $output, '"@type":"WebPage"' ) );
	seogp_assert_same( false, false !== strpos( $output, '"@type":"BlogPosting"' ) );
} );

seogp_test( 'author archives output ProfilePage and Person schema', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['view']      = 'author';
	$GLOBALS['seogp_test']['object_id'] = 2;
	$settings      = new Settings();
	$compatibility = new Compatibility();
	$schema        = new Schema( $settings, $compatibility, new Metadata( $settings, $compatibility ) );
	ob_start();
	$schema->render();
	$output = ob_get_clean();
	seogp_assert_same( true, false !== strpos( $output, '"@type":"ProfilePage"' ) );
	seogp_assert_same( true, false !== strpos( $output, 'Author biography' ) );
} );

seogp_test( 'Person identity prefers its photo over the site logo', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['options'][ Settings::OPTION_NAME ] = array( 'identity_type' => 'person', 'person_photo_id' => 22 );
	$GLOBALS['seogp_test']['theme_mods']['custom_logo']        = 11;
	$GLOBALS['seogp_test']['images'][11] = array( 'https://example.com/logo.png', 300, 100 );
	$GLOBALS['seogp_test']['images'][22] = array( 'https://example.com/person.jpg', 512, 512 );
	$settings      = new Settings();
	$compatibility = new Compatibility();
	$schema        = new Schema( $settings, $compatibility, new Metadata( $settings, $compatibility ) );
	ob_start();
	$schema->render();
	$output = ob_get_clean();
	seogp_assert_same( true, false !== strpos( $output, 'https://example.com/person.jpg' ) );
	seogp_assert_same( false, false !== strpos( $output, '#organization-logo' ) );
} );

seogp_test( 'Person identity falls back to the site logo', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['options'][ Settings::OPTION_NAME ] = array( 'identity_type' => 'person' );
	$GLOBALS['seogp_test']['theme_mods']['custom_logo']        = 11;
	$GLOBALS['seogp_test']['images'][11] = array( 'https://example.com/logo.png', 300, 100 );
	$settings      = new Settings();
	$compatibility = new Compatibility();
	$schema        = new Schema( $settings, $compatibility, new Metadata( $settings, $compatibility ) );
	ob_start();
	$schema->render();
	$output = ob_get_clean();
	seogp_assert_same( true, false !== strpos( $output, 'https://example.com/logo.png' ) );
	seogp_assert_same( true, false !== strpos( $output, '#person-image' ) );
} );

seogp_test( 'settings retain unique valid profile URLs', function () {
	seogp_test_reset();
	$clean = ( new Settings() )->sanitize(
		array(
			'identity_type' => 'person',
			'social_urls'   => array( 'https://example.com/angela', 'https://example.com/angela', 'not-a-url', '' ),
		)
	);
	seogp_assert_same( array( 'https://example.com/angela' ), $clean['social_urls'] );
	seogp_assert_same( 1, count( $GLOBALS['seogp_test']['settings_errors'] ) );
} );

seogp_test( 'invalid Person photo preserves the previous valid image', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['options'][ Settings::OPTION_NAME ] = array( 'person_photo_id' => 22 );
	$clean = ( new Settings() )->sanitize( array( 'person_photo_id' => 99 ) );
	seogp_assert_same( 22, $clean['person_photo_id'] );
	seogp_assert_same( 'seogp_invalid_person_photo', $GLOBALS['seogp_test']['settings_errors'][0]['code'] );
} );

seogp_test( 'sitemap fallback activates only for public sites without a provider', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['options']['blog_public'] = '1';
	$sitemaps = new Sitemaps();
	seogp_assert_same( true, $sitemaps->enable_fallback( false ) );
	seogp_assert_same( true, $sitemaps->is_fallback_active() );

	seogp_test_reset();
	$GLOBALS['seogp_test']['options']['blog_public'] = '0';
	seogp_assert_same( false, ( new Sitemaps() )->enable_fallback( false ) );
} );

seogp_test( 'cached external sitemap prevents fallback activation', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['options']['blog_public'] = '1';
	$GLOBALS['seogp_test']['transients'][ Sitemaps::DISCOVERY_TRANSIENT ] = array( 'https://example.com/sitemap.xml' );
	seogp_assert_same( false, ( new Sitemaps() )->enable_fallback( false ) );
} );

seogp_test( 'updater ignores prereleases and caches the failed check', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['http'] = array(
		'response' => array( 'code' => 200 ),
		'body'     => json_encode( array( 'tag_name' => 'v0.5.0-beta.1', 'prerelease' => true ) ),
	);
	$update = ( new Updater() )->filter_update( false, array( 'UpdateURI' => Updater::REPOSITORY_URL ), 'seo-for-generatepress/seo-for-generatepress.php', array() );
	seogp_assert_same( false, $update );
	seogp_assert_same( true, $GLOBALS['seogp_test']['transients'][ Updater::CACHE_KEY ]['unavailable'] );
} );

seogp_test( 'homepage metadata matches its exact snapshot', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['view']                      = 'front';
	$GLOBALS['seogp_test']['theme_mods']['custom_logo'] = 11;
	$GLOBALS['seogp_test']['images'][11]                = array( 'https://example.com/logo.png', 300, 100 );
	$GLOBALS['seogp_test']['attachment_titles'][11]     = 'Example Site logo';
	seogp_assert_snapshot( 'metadata-homepage.html', seogp_render_metadata() );
} );

seogp_test( 'post metadata with overrides and featured image matches its exact snapshot', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::TITLE_META ]       = 'Custom search title';
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::DESCRIPTION_META ] = 'Custom search description';
	$GLOBALS['seogp_test']['featured_image_id']                              = 21;
	$GLOBALS['seogp_test']['images'][21]                                     = array( 'https://example.com/featured.jpg', 1200, 630 );
	$GLOBALS['seogp_test']['meta'][21]['_wp_attachment_image_alt']           = 'Featured image';
	seogp_assert_snapshot( 'metadata-post.html', seogp_render_metadata() );
} );

seogp_test( 'page metadata matches its exact snapshot', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['view']              = 'page';
	$GLOBALS['seogp_test']['object']->post_type = 'page';
	$GLOBALS['seogp_test']['post_types'][10]    = 'page';
	seogp_assert_snapshot( 'metadata-page.html', seogp_render_metadata() );
} );

seogp_test( 'author metadata with avatar matches its exact snapshot', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['view']      = 'author';
	$GLOBALS['seogp_test']['object_id'] = 2;
	$GLOBALS['seogp_test']['avatars'][2] = 'https://example.com/avatar.jpg';
	seogp_assert_snapshot( 'metadata-author.html', seogp_render_metadata() );
} );

seogp_test( 'schema graphs match exact snapshots for every supported view', function () {
	$views = array( 'homepage' => 'front', 'post' => 'post', 'page' => 'page', 'author' => 'author' );
	foreach ( $views as $snapshot => $view ) {
		seogp_test_reset();
		$GLOBALS['seogp_test']['view'] = $view;
		if ( 'page' === $view ) {
			$GLOBALS['seogp_test']['object']->post_type = 'page';
			$GLOBALS['seogp_test']['post_types'][10]    = 'page';
		}
		if ( 'author' === $view ) {
			$GLOBALS['seogp_test']['object_id'] = 2;
		}
		$document = seogp_render_schema();
		seogp_assert_snapshot( 'schema-' . $snapshot . '.json', wp_json_encode( $document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
} );

seogp_test( 'Jetpack Open Graph is disabled only when this plugin owns supported metadata', function () {
	seogp_test_reset();
	list( , $metadata ) = seogp_services();
	seogp_assert_same( false, $metadata->filter_jetpack_open_graph( true ) );
	$GLOBALS['seogp_test']['view'] = 'search';
	seogp_assert_same( true, $metadata->filter_jetpack_open_graph( true ) );
} );

seogp_test( 'every recognized SEO plugin suppresses metadata schema and content controls', function () {
	$plugins = array(
		'wordpress-seo/wp-seo.php'                    => 'Yoast SEO',
		'seo-by-rank-math/rank-math.php'              => 'Rank Math SEO',
		'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
		'wp-seopress/seopress.php'                    => 'SEOPress',
		'slim-seo/slim-seo.php'                       => 'Slim SEO',
		'autodescription/autodescription.php'         => 'The SEO Framework',
	);
	foreach ( $plugins as $file => $name ) {
		seogp_test_reset();
		$GLOBALS['seogp_test']['options']['active_plugins'] = array( $file );
		$compatibility = new Compatibility();
		$controls      = new Content_Controls( $compatibility );
		seogp_assert_same( $name, $compatibility->get_conflicting_seo_plugin() );
		seogp_assert_same( '', seogp_render_metadata() );
		seogp_assert_same( null, seogp_render_schema() );
		seogp_assert_same( array( 'index' => true ), $controls->filter_robots( array( 'index' => true ) ) );
	}
} );

seogp_test( 'noindex does not remove metadata or schema output', function () {
	seogp_test_reset();
	$GLOBALS['seogp_test']['meta'][10][ Content_Controls::NOINDEX_META ] = '1';
	list( $controls ) = seogp_services();
	seogp_assert_same( array( 'noindex' => true, 'follow' => true ), $controls->filter_robots( array( 'index' => true ) ) );
	seogp_assert_same( true, false !== strpos( seogp_render_metadata(), 'rel="canonical"' ) );
	seogp_assert_same( 'BlogPosting', seogp_render_schema()['@graph'][3]['@type'] );
} );

$failures = 0;
foreach ( $tests as $name => $test ) {
	try {
		$test();
		echo "PASS: {$name}\n";
	} catch ( Throwable $error ) {
		++$failures;
		echo "FAIL: {$name}\n  {$error->getMessage()}\n";
	}
}

echo sprintf( "\n%d tests, %d failures.\n", count( $tests ), $failures );
exit( $failures ? 1 : 0 );
