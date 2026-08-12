=== SEO for GeneratePress ===
Tags: seo, generatepress, metadata, schema, performance
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.4.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, opinionated SEO tools designed for GeneratePress websites.

== Description ==

SEO for GeneratePress is an independent plugin being built to provide essential search metadata, structured data, indexing controls, sitemaps, and breadcrumbs without the weight of a large all-in-one SEO suite.

SEO for GeneratePress adds essential metadata and connected structured data while using native WordPress content and Site Identity as its source of truth. It also provides visibility, sitemap, and robots.txt status information.

SEO for GeneratePress is not affiliated with, endorsed by, or sponsored by GeneratePress or EDGE22 Studios Ltd. GeneratePress is a trademark of its respective owner.

== Installation ==

1. Upload the `seo-for-generatepress` folder to `/wp-content/plugins/`, or install the plugin ZIP through the WordPress Plugins screen.
2. Activate SEO for GeneratePress.
3. Open the SEO menu in WordPress admin, or use the SEO tab under Appearance > GeneratePress.

== Frequently Asked Questions ==

= Is GeneratePress required? =

The settings screen remains accessible without GeneratePress, but GeneratePress-specific integrations require the GeneratePress theme to be active.

= What structured data does this version output? =

Version 0.4.5 adds exact-output regression coverage while retaining the same focused metadata, schema, indexing, sitemap, and GitHub update behavior.

= What happens when I deactivate or delete the plugin? =

Deactivation always preserves plugin settings. Deletion also preserves settings unless you explicitly enable data deletion on the General screen before deleting the plugin.

== Changelog ==

= 0.4.5 =

* Added exact metadata snapshots for the homepage, posts, pages, and author archives.
* Added exact JSON-LD graph snapshots for every supported view.
* Added comprehensive Jetpack and recognized SEO-plugin conflict tests.
* Added regression coverage confirming overrides and noindex preserve expected metadata and schema.

= 0.4.4 =

* Expanded automated coverage for homepage, page, author, Person, settings, sitemap fallback, and updater behavior.
* Added license, security, privacy, and release-process documentation.
* Added dependency update automation and a build-status badge.
* Improved production-code consistency and release quality checks.

= 0.4.3 =

* Added self-contained updates through published GitHub releases.
* Added strict release-asset validation and cached, failure-safe update checks.
* Added automated GitHub release packaging after quality checks pass.

= 0.4.2 =

* Clarified that per-content search exclusion applies to supported sitemaps.
* Added automated behavioral tests for metadata, schema, indexing, sitemap exclusions, and conflict suppression.
* Added continuous integration for PHP 7.4–8.3, WordPress coding standards, PHP compatibility, and WordPress Plugin Check.

= 0.4.1 =

* Simplified the per-content SEO field labels and help text.

= 0.4.0 =

* Added a native SEO document panel to the block editor for standard posts and pages.
* Added a Classic Editor SEO meta box fallback.
* Added optional search title and meta description overrides.
* Added a Hide from search results control that outputs noindex, follow.
* Excluded hidden content from WordPress and Jetpack XML sitemaps.
* Preserved canonical URLs, social metadata, and structured data for noindexed content.
* Added revision support, REST authorization, sanitization, and uninstall cleanup for SEO post metadata.

= 0.3.0 =

* Added automatic meta descriptions and canonical URLs for the homepage, posts, pages, and author archives.
* Added Open Graph and Twitter card metadata using native WordPress titles, excerpts, featured images, avatars, and Site Identity.
* Added WebPage schema for the homepage and pages.
* Added BlogPosting schema for standard posts, including author, publisher, dates, and featured images.
* Added ProfilePage and Person schema for author archives.
* Connected page-level entities to the existing WebSite and site identity graph.
* Prevented duplicate Jetpack Open Graph and Twitter card output on supported views.
* Linked Search Engine Visibility directly to the relevant WordPress control.
* Added a repeatable metadata and schema acceptance checklist.

= 0.2.1 =

* Removed introductory headings and descriptions from the settings tabs.
* Added Jetpack, robots.txt, and conventional sitemap URL detection.
* Replaced the sitemap-disabled claim with more accurate detected or not-detected states.
* Automatically enables the native WordPress sitemap when no other sitemap provider is detected.
* Added an always-visible link to WordPress Search Engine Visibility settings.

= 0.2.0 =

* Added Organization or Person site identity selection.
* Reused the WordPress Site Title, Site URL, and Custom Logo in structured data.
* Added an optional Media Library photo for Person schema.
* Added validated, repeatable social and profile URLs.
* Added global WebSite and identity JSON-LD output.
* Added conflict protection for common SEO plugins.
* Added a Visibility & Access tab with search visibility, core sitemap, and robots.txt status.

= 0.1.3 =

* Removed conflicting WordPress and GeneratePress wrapper classes so the settings container is centered at the Font Library width.

= 0.1.2 =

* Matched the General screen more closely to the GeneratePress Font Library layout.
* Positioned the GeneratePress SEO tab after the Font Library tab when available.

= 0.1.1 =

* Added a Settings link to the Installed Plugins screen.
* Changed the top-level menu label to SEO for GP.
* Replaced the vertical navigation with GeneratePress-inspired in-page tabs.
* Removed environment details from the General screen.

= 0.1.0 =

* Added the initial plugin scaffold.
* Added a top-level SEO admin menu.
* Added an SEO tab to the GeneratePress dashboard.
* Added the General screen with environment information.
* Added opt-in data removal during plugin deletion.
