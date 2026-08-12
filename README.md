# SEO for GeneratePress

[![Plugin quality](https://github.com/angelablake/SEO-for-GeneratePress/actions/workflows/quality.yml/badge.svg)](https://github.com/angelablake/SEO-for-GeneratePress/actions/workflows/quality.yml)

SEO for GeneratePress is a lightweight, opinionated SEO plugin designed for GeneratePress websites.

The project is independent and is not affiliated with, endorsed by, or sponsored by GeneratePress or EDGE22 Studios Ltd. GeneratePress is a trademark of its respective owner.

## Current status

Version 0.4.4 adds automatic SEO output and focused per-content controls:

- a top-level SEO admin menu registered after GenerateBlocks;
- an SEO tab in the GeneratePress dashboard;
- a shared settings screen with General and Visibility & Access tabs;
- a direct Settings link on the Installed Plugins screen;
- Organization or Person site identity based on WordPress Site Identity;
- an optional Person photo and validated social/profile URLs;
- a compact WebSite and identity JSON-LD graph;
- automatic descriptions, canonical URLs, Open Graph tags, and Twitter cards;
- WebPage schema for the homepage and pages;
- BlogPosting schema for standard posts;
- ProfilePage and Person schema for author archives;
- optional search title and meta description overrides for posts and pages;
- a Hide from search results control with sitemap exclusion;
- block editor and Classic Editor interfaces;
- conflict-safe schema behavior when another common SEO plugin is active;
- WordPress sitemap, robots.txt, and search-visibility status;
- automatic native WordPress sitemap fallback when no other provider is detected;
- opt-in data removal when the plugin is deleted;
- graceful behavior when GeneratePress is inactive; and
- native WordPress updates from tested, published GitHub releases.

See `TESTING.md` for the release acceptance checklist.

## Automated quality checks

Run `php tests/run.php` for isolated behavioral tests. After installing the development dependencies with Composer, run `composer check` for the tests plus release-blocking security, internationalization, naming, and PHP compatibility checks. GitHub Actions runs those checks, the behavioral tests across PHP 7.4, 8.1, and 8.3, and the official WordPress Plugin Check action on every push and pull request.

Pushing a new version to `main` runs the release workflow. If that version does not already have a release, the workflow validates the source, builds a clean `seo-for-generatepress.zip`, tags the commit, and publishes the ZIP as a GitHub release asset.

See `RELEASING.md` for the complete release process, `SECURITY.md` for vulnerability reporting, and `PRIVACY.md` for the plugin's data and update behavior.

## Requirements

- WordPress 6.5 or newer
- PHP 7.4 or newer
- GeneratePress for theme-specific integrations

GeneratePress Premium is supported but is not required.

## Development principles

- Use native WordPress APIs.
- Add no front-end JavaScript unless a visible feature requires it.
- Load admin assets only on plugin screens.
- Preserve settings during deactivation.
- Avoid duplicate output from other SEO plugins.
- Keep GeneratePress-specific behavior inside a dedicated integration layer.

## License

GPL-2.0-or-later.
