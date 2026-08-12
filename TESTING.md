# Metadata and schema acceptance checklist

## Automated checks

Run `php tests/run.php` to test title overrides, noindex output, WordPress and Jetpack sitemap exclusions, description fallbacks, SEO-plugin conflict suppression, and GitHub release validation. Run `composer check` after `composer install` to add release-blocking security, internationalization, naming, and PHP 7.4+ compatibility checks.

The GitHub Actions workflow runs these checks automatically on supported PHP versions and also runs the official WordPress Plugin Check action. The remaining sections are exploratory checks retained for future live-site verification; they are not required for routine automated releases.

Use public URLs and **View Page Source** rather than the browser inspector. Page caches should be cleared after installing or updating the plugin.

## Test fixtures

Test these views:

1. Homepage with a Site Title, Tagline, and Site Logo.
2. Standard post with a manual excerpt and featured image.
3. Standard post without a manual excerpt.
4. Page with a manual excerpt and featured image.
5. Page without an excerpt or featured image.
6. Author archive for a user with a biography and avatar.
7. A public custom post type, if one is available.

## Source checks

On each supported view, search the source for:

- `<title`
- `name="description"`
- `rel="canonical"`
- `property="og:`
- `name="twitter:`
- `application/ld+json`

Confirm that:

- WordPress outputs one document title.
- The plugin outputs no more than one description and canonical URL.
- Open Graph and Twitter values match the current page.
- No block comments, shortcodes, HTML, or navigation text appears in descriptions.
- The JSON-LD is valid JSON and contains stable `@id` references.
- A known full SEO plugin suppresses SEO for GeneratePress metadata and schema.
- Jetpack Open Graph comments and tags do not appear alongside SEO for GeneratePress tags on supported views.

## Expected defaults

| View | Description | Social image | Page-level schema |
| --- | --- | --- | --- |
| Homepage | Site Tagline | Site Logo | `WebPage` |
| Standard post | Manual excerpt, then generated excerpt | Featured image, then Site Logo | `BlogPosting` plus its `WebPage` |
| Page | Manual excerpt, then generated excerpt | Featured image, then Site Logo | `WebPage` |
| Author archive | Author biography | Author avatar, then Site Logo | `ProfilePage` and `Person` |
| Custom post type | None from this release | None from this release | None from this release |

Empty descriptions and images should be omitted. The global `WebSite` and Organization or Person entities remain available on public frontend views.

## Fallback checks

- Remove a manual excerpt: the description should switch to a cleaned WordPress-generated excerpt.
- Restore the excerpt: it should take priority again.
- Remove a featured image: the Site Logo should become the fallback image.
- Remove the author biography: the author description should be omitted.
- Remove the Site Tagline: the homepage description should be omitted.
- Visit a custom post type: page-specific metadata and schema should not be added by this release.

## Per-content controls

Test the SEO panel on both a standard Post and Page:

- Leave the search title blank: the WordPress content title should be used.
- Add a search title override: the document-title content portion, Open Graph title, and Twitter title should change, while the schema headline or page name should retain the actual WordPress title.
- Leave the meta description blank: the manual excerpt or generated excerpt should be used.
- Add a meta description: it should be saved in full and take priority in description, Open Graph, Twitter, and schema output.
- Enable Hide from search results: source should contain `noindex, follow`, while canonical and social metadata remain present.
- Check the active XML sitemap after it regenerates: hidden content should be absent from WordPress core and Jetpack sitemaps.
- Disable Hide from search results: the noindex directive should disappear and the content should return to the sitemap after regeneration.
- Save, reload, autosave, and restore a revision to confirm the registered SEO metadata follows normal editor behavior.

## External validation

Validate public supported URLs with Google Rich Results Test and Schema.org Validator. Social debuggers can be used to refresh and inspect the Open Graph preview after caches are cleared.
