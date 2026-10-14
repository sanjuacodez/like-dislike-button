=== Like Dislike Button ===
Contributors: sanju-shankar
Tags: like, dislike, voting, reactions, feedback
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Like and dislike buttons with 9 icon sets, 6 styles, a block, a Most Liked Posts list and vote analytics. Works with page caching.

== Description ==

Like Dislike Button adds like and dislike buttons to posts, pages, WooCommerce products, custom post types and comments. Set up the look in a live preview, then watch what readers like on a built-in dashboard.

Everything is free, with no upsells.

= Looks the way you want =

* **9 icon sets:** thumbs, hearts, up/down arrows, smileys, yes/no, hot/not, emoji, text only, or your own images.
* **6 button styles:** soft, outline, pill, minimal, solid and glass.
* **Sizes, alignment and layout:** small, medium or large; left, center or right; side by side or stacked (Reddit-style up/down votes).
* **Counts your way:** show, hide, show only after voting, percentages, or a single score (likes minus dislikes). Large numbers shorten to 1.2K.
* **Like ratio bar** under the buttons.
* **Click animations:** pop, bounce or a particle burst. Visitors who prefer reduced motion never see them.
* **Presets** in one click: Like / dislike, Was this helpful?, Hearts, Upvote / downvote, Emoji and Rating bar.
* **Live preview** on light, dark and image backgrounds while you change settings.
* **Fits any theme:** neutral colours follow your theme's text colour, so buttons work on light and dark themes without setup. Or pick your own colours.

= Voting that holds up =

* One vote per person. Logged-in users vote with their account; visitors per browser or per IP address.
* People can change their mind or remove their vote (optional).
* Open to everyone, or to logged-in users only, or only to chosen roles. Visitors who can't vote get a log-in link.
* **Works with page caching** (WP Rocket, LiteSpeed, Cloudflare and others). Counts and each visitor's own vote load fresh after the page loads.
* Rate limiting stops scripted vote flooding.
* Works behind Cloudflare and proxies, with a setting for the visitor IP header.

= Placement =

* Automatically before or after content, or both, on the post types you choose.
* Small buttons under each comment.
* WooCommerce products show buttons in the product summary.
* **Like Dislike Buttons block**, with its own style options, that also works inside Query Loop and in the site editor.
* **Most Liked Posts block** and shortcode: all time, or the last week, month or year, with optional featured images.
* Shortcode `[like_dislike_button]`, a template tag for themes, and an Elementor widget.
* Hide the buttons on any single post from the editor.

= Structured data =

* Like and dislike counts are added to your page's schema.org markup as `interactionStatistic` (LikeAction and DislikeAction).
* They join your existing Article or WebPage entity rather than adding a competing one. This works with Yoast SEO, Rank Math, All in One SEO, and themes or plugins that print their own JSON-LD.
* Counts are only marked up where visitors can see the results (counts, percentages or score), as search engines expect. No self-serving review stars are added.

= Analytics =

* Dashboard with likes, dislikes, total votes and like ratio, compared with the previous period.
* Votes per day or month chart, with a table view.
* Most liked and most disliked posts for 7, 30 or 90 days, 12 months or all time.
* Sortable Votes column in the posts list.
* "Likes this week" widget on the WordPress dashboard.
* CSV export.

= Switching from another plugin =

Import your existing votes from **WP ULike**, **Like Dislike for WP** and **Posts Like Dislike** in one click. Individual votes keep their dates, and logged-in users keep their earlier vote. Old shortcodes keep working after the import.

= Privacy =

* IP addresses are never stored, only a keyed one-way hash.
* The visitor cookie holds a random ID and is set only after someone votes.
* Works with the WordPress personal data export and erase tools.
* Suggested privacy policy text is included.
* No external requests, no tracking, no jQuery on the front end.

= For developers =

* REST API: `GET /wp-json/ldb/v1/state` and `POST /wp-json/ldb/v1/vote`, plus an `ldb_votes` field on post endpoints.
* Template tags: `ldb_buttons()`, `ldb_get_buttons()`, `ldb_get_counts()`.
* Action: `ldb_vote_recorded`. Filters: `ldb_buttons_html`, `ldb_can_vote`, `ldb_icon_sets`, `ldb_visitor_ip`, `ldb_rate_limit`, `ldb_schema_statistics`.
* WP-CLI: `wp ldb stats`, `wp ldb recount`, `wp ldb import <source>`, `wp ldb reset`.
* CSS custom properties such as `--ldb-like`, `--ldb-dislike` and `--ldb-radius`.

= Credits =

Icons are adapted from [Lucide](https://lucide.dev) (ISC licence).

== Installation ==

1. Install the plugin from **Plugins → Add New**, or upload the `like-dislike-button` folder to `/wp-content/plugins/`.
2. Activate it. Buttons appear under your blog posts straight away.
3. Go to **Like Dislike → Settings** to choose icons, style and where buttons appear.

== Frequently Asked Questions ==

= The buttons don't appear =

Check that the post type is ticked under **Like Dislike → Settings → Placement**, and that Position isn't set to "Only where I place them". Some themes don't use the standard post content. In that case, add the Like Dislike Buttons block, the `[like_dislike_button]` shortcode, or `<?php if ( function_exists( 'ldb_buttons' ) ) { ldb_buttons(); } ?>` to your template.

= Does it work with caching plugins? =

Yes. The page can be cached as usual. Counts and the visitor's own vote load through a small request after the page loads.

= How do I show the buttons in a Query Loop or on the blog page? =

In block themes, add the Like Dislike Buttons block inside the Post Template of a Query Loop. In classic themes, turn on "Also show on blog and archive pages" under Placement.

= Can I use it for "Was this helpful?" on documentation? =

Yes. Click the "Was this helpful?" preset. It adds the question, Yes / No labels and a thank-you message, and hides the counts.

= Can I show only a like button? =

Yes. Under Placement → Which buttons, choose "Like only". The Hearts preset does this too.

= Why does everyone in my office share one vote? =

You chose "Per IP address" tracking, and your office shares one IP. Switch to "Per browser" under Voting.

= Every visitor seems to have the same IP address =

Your site is probably behind Cloudflare or a proxy. Under Voting, set "Visitor IP comes from" to the matching header.

= How do I move my votes from another like plugin? =

Go to **Like Dislike → Tools** and click Import next to the plugin. You can run it again safely: it updates rather than doubles. On very large sites, use `wp ldb import wp-ulike`.

= Does it add structured data? =

Yes. When vote results are shown (counts, percentages or score), like and dislike totals are added to the post's existing schema.org Article or WebPage entity as `interactionStatistic`. Nothing is added when counts are hidden. Turn it off under Advanced.

= What happens to my votes if I delete the plugin? =

They are kept, unless you switch on "Delete all votes and settings when the plugin is deleted" under Advanced first.

== Screenshots ==

1. Settings with live preview, presets, icon sets and button styles.
2. Button styles and icon sets on the front end.
3. Analytics dashboard with trends and most liked posts.
4. Like Dislike Buttons and Most Liked Posts blocks in the editor.
5. Import votes from other like plugins.

== Changelog ==

= 3.0.0 =
* Rebuilt from scratch.
* New: 9 icon sets, 6 button styles, sizes, layouts, colours, click animations and presets, with a live preview.
* New: count display options, short numbers and a like ratio bar.
* New: "Was this helpful?" question and thank-you message.
* New: change or remove a vote; like-only and dislike-only modes.
* New: logged-in only voting with role limits; per-browser or per-IP visitor tracking; rate limiting.
* New: comments and WooCommerce products.
* New: Like Dislike Buttons block (works in Query Loop) and Most Liked Posts block and shortcode.
* New: analytics dashboard, posts list column, dashboard widget and CSV export.
* New: import from WP ULike, Like Dislike for WP and Posts Like Dislike.
* New: works with page caching.
* New: REST API, WP-CLI commands, hooks and template tags.
* New: like and dislike counts in schema.org structured data (interactionStatistic), merged into existing Yoast SEO, Rank Math, All in One SEO or theme markup.
* New: personal data export and erase, privacy policy text.

== Upgrade Notice ==

= 3.0.0 =
A complete rebuild. Counts from version 2.x can be brought over under Like Dislike → Tools → Import.
