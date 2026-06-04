=== Travely Widget ===
Requires at least: 5.8
Tested up to: 5.8
Stable tag: 1.0.14
License: Proprietary
License URI: https://github.com/Travely-ee/travely-widget-wordpress/blob/main/travely-widget/LICENSE.txt

The plugin allows you to use the Travely system widget on your website.

== Description ==

The plugin allows you to use the Travely system widget on your website.

The widget language is selected at page level. All Travely shortcodes on the same page use the same resolved language.

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

URL examples:

* `?language=est`
* `?language=eng`
* `?language=rus`
* `?language=lav`
* `?lang=et`
* `?lang=en`
* `?lang=ru`
* `?lang=lv`

For multilingual sites where search results pages have language-specific URLs, use the `travely_widget_path_to_search` filter:

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
