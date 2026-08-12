# Privacy

SEO for GeneratePress stores its settings and per-content SEO fields in the site's WordPress database. It does not include analytics, telemetry, advertising, user tracking, or an external account system.

## GitHub update checks

Installed copies periodically request public release metadata from GitHub to check for updates. The request identifies the plugin and its installed version in the user-agent string. It does not intentionally send the site's URL, content, plugin settings, or WordPress user information.

As the receiving service, GitHub may process standard request information such as the originating IP address according to GitHub's own privacy practices. Release information is cached for 12 hours. Failed checks are cached for one hour, and the plugin continues operating normally when GitHub is unavailable.

## Front-end output

The plugin publishes SEO metadata and structured data using public site identity, content, author-profile, and image information already configured in WordPress. Site owners control that source information and should avoid placing private information in public WordPress fields.
