=== Best Related Posts ===
Contributors: rmak78
Donate link: http://www.sutlej.net/downloads/best-related-posts/
Tags: related posts, related content, thumbnails, seo, internal linking
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shows truly related posts with thumbnails, ranked by shared tags, categories and keywords. Design your own layout, use the block, the shortcode or auto-append.

== Description ==

Best Related Posts shows a list of posts related to the one the visitor is reading, with a thumbnail for each. It is good for internal linking, SEO and reducing bounce rate.

**How relatedness is computed (2.0)**

Every candidate post is scored against the current post:

* shared tags count the most,
* shared categories count next,
* keywords are extracted automatically from the current post's title and content (title words count double) and matched against each candidate's title and content, so posts relate even when nobody added tags,
* newer posts win ties.

If fewer posts than configured score above zero, the list is padded with recent posts so it is never empty. Results are cached and refreshed automatically when posts or terms change. No database tables are altered.

**Display options**

* Turn on "Automatic display" to append the list after every single post.
* Add the "Best Related Posts" block in the block editor.
* Use the `[best_related_posts max="4"]` shortcode.
* Call `<?php if ( function_exists( 'boposts_show' ) ) { boposts_show(); } ?>` from your theme.

**Thumbnails** use the featured image when one is set and fall back to the first image in the post.

**Your own layout**: write the header, body and footer HTML yourself using the `{title}`, `{link}`, `{excerpt}` and `{image}` placeholders.

Developers can tune the engine with filters such as `boposts_weights`, `boposts_stopwords`, `boposts_taxonomies`, `boposts_related_ids`, `boposts_placeholders`, `boposts_output` and `boposts_cache_ttl`.

== Installation ==

1. Upload the `best-related-posts` folder to `/wp-content/plugins/`, or install it from the Plugins screen.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to Settings > Best Related Posts and choose how to display the list (automatic display, block, shortcode or theme call) and adjust the template.

== Frequently Asked Questions ==

= Do I have to create thumbnails for each post? =

No. The plugin uses the featured image when you set one, otherwise the first image found in the post.

= My posts have no tags. Will it still work? =

Yes. Shared categories and automatically extracted keywords from the title and content are used, and recent posts fill any remaining slots.

= I am upgrading from 1.x. What changes? =

Your header, body, footer, max and excerpt settings are kept. The FULLTEXT index that 1.x added to the posts table is removed automatically. Related posts are now chosen by the new scoring engine, so the lists will differ from before. The settings page moved to Settings > Best Related Posts.

= The list shows up twice. =

Turn off "Automatic display" if you also call `boposts_show()` from your theme, or use the block or shortcode instead.

== Screenshots ==

1. Showing related posts to visitors.
2. Plugin Options Page for Admin.

== Changelog ==

= 2.0.0 =
* Works with current WordPress (tested up to 7.1) and PHP 8: removed the `WPLANG` constant, `get_magic_quotes_gpc()`, short open tags and other removed APIs that caused fatal errors.
* New relevance engine: posts are ranked by shared tags, shared categories and automatically extracted keywords, with recency as a tie-break. Weights are filterable.
* No more changes to core database tables; the old FULLTEXT index is dropped on upgrade.
* Results are cached and invalidated automatically when posts, terms or settings change.
* New "Best Related Posts" block for the block editor.
* New `[best_related_posts]` shortcode.
* New option to append the list automatically after single posts.
* Featured images are used as thumbnails, falling back to the first image in the post.
* Excerpts are trimmed at word boundaries and are UTF-8 safe.
* Settings page rebuilt on the Settings API with nonce, capability checks and sanitization.
* Translation ready (text domain `best-related-posts`).
* Requires WordPress 6.2 and PHP 7.4 or newer.

= 1.1.0 =
* Removed all external links from front end as per wordpress.org policy

= 1.0.9 =
* Cleaner Code for Plugin installation Provided so that blog does not break when you disable to plugin

= 1.0.8 =
* Fixed Wrapping issues in IE 6 +

= 1.0.7 =
* Fixed the Function call bug

= 1.0.6 =
* Fixed Formatting issue with IE

= 1.0.5 =
* Fixed the error that was not showing posts on some blogs after last update

= 1.0.4 =
* Fixed the code so related posts does not include pages

= 1.0.3 =
* Fixed install issues including file location problems

= 1.0.2 =
* Fixed image width issue
* Added multiple language support

= 1.0.1 =
* Fixed bug in admin menu
* Fixed array_filter errors

= 1.0 =
* Released the plugin

== Upgrade Notice ==

= 2.0.0 =
Major update: fixes fatal errors on modern WordPress/PHP, replaces the FULLTEXT search with a tag/category/keyword scoring engine, adds a block, a shortcode and automatic display. Your template settings are preserved.
