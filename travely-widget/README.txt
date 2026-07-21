=== Travely Widget ===
Requires at least: 5.8
Requires PHP: 7.0
Tested up to: 7.0
Stable tag: 1.0.22
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

The `background` shortcode attribute on `[travely-widget-results]` overrides the global setting for that individual shortcode:

* `[travely-widget-results background="transparent"]`
* `[travely-widget-results background="#ffffff"]`
* `[travely-widget-results background="rgba(255,255,255,0.9)"]`

Shortcode examples:

* `[travely-widget-search language="auto"]`
* `[travely-widget-search language="rus"]`
* `[travely-widget-search language="eng"]`
* `[travely-widget-search language="est"]`
* `[travely-widget-search language="lav"]`
* `[travely-widget-search-country language="eng"]`
* `[travely-widget-country language="rus"]`
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

== Usage ==

Use the `[travely-widget-search]`, `[travely-widget-country]`, or `[travely-widget-search-country]` shortcodes in any post or page to display Travely widgets.
