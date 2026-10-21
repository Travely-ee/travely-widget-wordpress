=== Travely Widget ===
Requires at least: 5.8
Requires PHP: 7.0
Tested up to: 7.0.2
Stable tag: 1.2.1
License: Proprietary
License URI: https://github.com/Travely-ee/travely-widget-wordpress/blob/main/travely-widget/LICENSE.txt

The plugin allows you to use the Travely system widget on your website.

== Description ==

The plugin allows you to use the Travely system widget on your website.

The widget language is selected at page level. All Travely shortcodes on the same page use the same resolved language.

Do not mix different Travely Widget languages on the same page. The widget is loaded as a UMD bundle and exposes a global `window.TravelySearch` object, so only one language build can be safely used per page.

Supported widget languages:

* `est` - Estonian
* `eng` - English
* `rus` - Russian
* `lav` - Latvian

== Installation ==

1. Download the plugin archive (`travely-widget-v*.zip`) from the GitHub releases page.
2. Go to your WordPress admin panel.
3. Navigate to Plugins -> Add New -> Upload Plugin.
4. Upload the archive and activate the plugin.
5. Create a new page with the `[travely-widget-results]` shortcode to display search results.
6. Go to **Settings -> Travely Widget** to configure the widget. Enter your API key and the path of the search results page.

== Configuration ==

Settings are available in **Settings -> Travely Widget**.

`Default language` is the fallback widget language used when the language cannot be detected automatically.

`Force default language` always uses the selected default language and ignores URL language, Polylang/WPML language and WordPress locale.

When `Force default language` is disabled, the plugin resolves language in this order:

1. URL `?language=`
2. URL `?lang=`
3. Polylang current language
4. WPML current language
5. WordPress locale
6. Default language

When `Force default language` is enabled, the plugin always uses the selected default language and ignores URL language, Polylang/WPML and WordPress locale.

The language shortcode attribute overrides automatic language detection unless `Force default language` is enabled. Use `language="auto"` to keep the automatic detection order.

Path mode controls how `Path to Search` is selected:

* `Single path for all languages` uses the common `Path to Search` setting for every resolved language.
* `Separate path for each language` uses `Estonian search path`, `English search path`, `Russian search path` and `Latvian search path`.

`Path mode` also controls which path fields are shown in the settings page. In `Single path for all languages`, only the common `Path to Search` field is shown. In `Separate path for each language`, only the `Language-specific paths` section is shown.

When a language-specific path is empty, the plugin falls back to the common `Path to Search`. If the common path is empty, it falls back to `/tour-search`.

The path is selected after the final language is resolved. For example, `[travely-widget-search language="rus"]` uses the Russian search path, `[travely-widget-search language="est"]` uses the Estonian search path, and `language="auto"` first resolves the page language and then chooses the matching path. When `Force default language` is enabled, the path is selected for the forced default language.

`Results background` sets the background colour of the embedded results iframe. Leave empty for transparent. Supported values: `transparent`, HEX (`#fff`, `#ffffff`), `rgb()`, `rgba()`, `hsl()`, `hsla()`.

Set `Primary color` in **Settings -> Travely Widget -> Primary color** using the native WordPress color picker. It is an optional global brand color for buttons, prices, active controls and other primary accents in every Travely Widget instance. Use a six-digit HEX value such as `#cc1c21`. Three-digit HEX values are accepted and expanded to six digits when saved. Leave the field empty to use the Travely Widget built-in orange palette (`#ff7a00`). This setting does not change warning or error colors.

The `travely_widget_primary_color` filter is an optional programmatic override and takes priority over the value selected in the admin settings. Its result is validated again: three-digit HEX values are expanded, six-digit values are lowercased, and other invalid values disable the override:

`
add_filter( 'travely_widget_primary_color', function ( $primary_color ) {
    return '#cc1c21';
} );
`

The `background` shortcode attribute on `[travely-widget-results]` overrides the global setting for that individual shortcode:

* `[travely-widget-results background="transparent"]`
* `[travely-widget-results background="#ffffff"]`
* `[travely-widget-results background="rgba(255,255,255,0.9)"]`

`Country widget columns` sets the number of columns in the country widget grid. Supported values: `3` (default) and `4`.

The `columns` shortcode attribute on `[travely-widget-country]`, `[travely-widget-search-country]`, `[travely-widget-best]` and `[travely-widget-results]` overrides the global setting for that individual shortcode:

* `[travely-widget-country columns="4"]`
* `[travely-widget-search-country columns="3"]`
* `[travely-widget-results columns="4"]`
* `[travely-widget-best columns="4"]`

`[travely-widget-results]` needs the setting as well: without search parameters the embedded booking application shows its home page with the same country widget.

Unsupported values fall back to `3`. The 4 column grid is used on screens wider than 1024px. Tablets and phones automatically fall back to the 3 column and single column layouts.

Shortcode examples:

* `[travely-widget-search language="auto"]`
* `[travely-widget-search language="rus"]`
* `[travely-widget-search language="eng"]`
* `[travely-widget-search language="est"]`
* `[travely-widget-search language="lav"]`
* `[travely-widget-search-country language="eng" columns="4"]`
* `[travely-widget-country language="rus" columns="4"]`
* `[travely-widget-best language="eng" columns="4"]`
* `[travely-widget-results language="est"]`

URL examples:

* `?language=est`
* `?language=eng`
* `?language=rus`
* `?language=lav`
* `?lang=et`
* `?lang=en`
* `?lang=ru`
* `?lang=lv`

For advanced multilingual path customization, use the `travely_widget_path_to_search` filter. The filter runs after the final path is selected:

`apply_filters( 'travely_widget_path_to_search', $path, $language );`

Example:

`
add_filter( 'travely_widget_path_to_search', function ( $path, $language ) {
    switch ( $language ) {
        case 'eng':
            return '/en/tour-search';
        case 'rus':
            return '/ru/tour-search';
        case 'lav':
            return '/lv/tour-search';
        case 'est':
        default:
            return '/tour-search';
    }
}, 10, 2 );
`

`Open Graph tags` (enabled by default) lets Facebook and messengers show the hotel photo, name and description when someone shares a tour link from the results page. They do not run JavaScript and do not look inside the widget iframe, so without the tags they show the site logo. When `?path` opens a tour card or a best tour, whether the link was copied from the address bar or made with the Share tour button, the plugin adds `og:title` (hotel name, stars, resort and country), `og:description` (the first sentence of the hotel description), `og:image` with `og:image:width` and `og:image:height` (the main hotel photo, its wide version when there is one), `og:url`, `og:type` and `og:site_name` in the widget language of the page. Hotel data comes from the public Travely API and is cached for a month per hotel and language. For a best tour the plugin asks Travely which hotel the best tour is about now, with the API key from the settings, and caches the answer for an hour. If the tags cannot be built (the Travely API does not answer, the hotel or best tour is not published, the API key is missing or not accepted for the site domain, or 30 hotels and best tours that were not cached yet were already looked up in the current minute), the page is rendered as before, other plugins' tags stay, and the plugin tries again later. On these pages only, Open Graph tags printed in `wp_head` by other plugins are replaced. Facebook caches previews: after updating the plugin, after the agency changed the hotel of a best tour that was already shared, or if a link was shared while the tags could not be built, open the link in the Sharing Debugger and press Scrape Again.

The `travely_widget_enable_open_graph` filter enables the tags when the results widget is rendered by a theme template; the widget's `language` attribute is not visible then, so the language is detected as for `language="auto"`. The `travely_widget_open_graph_tags` filter changes the tags before they are printed; `$best_id` is the best tour id, or 0 for a tour card:

`
add_filter( 'travely_widget_open_graph_tags', function ( $tags, $hotel, $hotel_id, $language, $best_id ) {
    $tags['og:title'] = $hotel['name'] . ' ' . $hotel['stars'];

    return $tags;
}, 10, 5 );
`

== Usage ==

Use the `[travely-widget-search]`, `[travely-widget-country]`, `[travely-widget-search-country]`, or `[travely-widget-best]` shortcodes in any post or page to display Travely widgets.

`[travely-widget-best]` shows the agency's own Best Tours, created in the Travely agency cabinet. It uses the API key from the plugin settings; without a key the block shows nothing (the browser console shows `[TravelySearch] Best tours need the agency api key.`). If the key is wrong or the site domain is not allowed for it, the block shows nothing either and the console shows `[TravelySearch] Best tours request failed. Check the agency api key and that this site domain is allowed for it.`. Attributes: `language` and `columns`. It needs a remote Travely Widget build with Best Tours support.

Primary-color appearance requires a remote Travely Widget build with `WidgetAppearance` support. Deploy both remote `widget` and `booking` targets before releasing/installing this WordPress plugin; configure the site color only after that rollout.

== Changelog ==

= 1.2.0 =
* Added Open Graph tags for shared tour card and best tour links: Facebook and messengers show the hotel photo, name and description instead of the site logo. On those pages, Open Graph tags from other plugins (Yoast SEO, Rank Math and others) are replaced. Enabled by default; can be turned off in Settings -> Travely Widget.

= 1.1.0 =
* Added the `[travely-widget-best]` shortcode: the agency's own Best Tours, with `language` and `columns` attributes like the country widget.

= 1.0.29 =
* Added an optional global primary color setting and secure appearance forwarding to every shortcode and Gutenberg block.
