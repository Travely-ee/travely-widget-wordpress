<?php

/**
 * Open Graph tags for shared tour links.
 *
 * @link       https://travely-solutions.eu
 * @since      1.2.0
 *
 * @package    Travely_Widget
 * @subpackage Travely_Widget/public
 */

/**
 * Describes the hotel opened in the results widget with Open Graph tags.
 *
 * Facebook and messengers do not run JavaScript and never look inside the
 * booking iframe, so a shared tour link only shows what WordPress renders.
 * The opened hotel is taken from the ?path query parameter, which the widget
 * keeps in sync with the iframe route, and its public description is read
 * from the Travely API.
 *
 * Open Graph tags printed by other plugins inside wp_head are replaced on
 * these pages only. Every other page is left untouched.
 *
 * @since      1.2.0
 * @package    Travely_Widget
 * @subpackage Travely_Widget/public
 * @author     Travely Solutions OÜ <info@travely.ee>
 */
class Travely_Widget_Open_Graph {

    /**
     * Transient prefix. The version segment invalidates cached previews
     * whenever their structure changes.
     */
    const CACHE_PREFIX = 'travely_widget_og_v1_';

    /**
     * Site-wide transient set while the Travely API does not answer.
     */
    const API_DOWN_KEY = 'travely_widget_og_v1_api_down';

    /**
     * Site-wide transient counting API lookups in the current minute.
     */
    const BUDGET_KEY = 'travely_widget_og_v1_budget';

    /**
     * Uncached hotel lookups allowed per minute for the whole site.
     *
     * The hotel id comes from the visitor's URL, so this bounds the outgoing
     * requests a crawl of random ids can cause.
     */
    const LOOKUPS_PER_MINUTE = 30;

    /**
     * How long a failed lookup is remembered, in seconds.
     */
    const FAILURE_TTL = 600;

    /**
     * Timeout of the hotel description request, in seconds.
     */
    const API_TIMEOUT = 3;

    /**
     * Timeout of each image check, in seconds.
     */
    const IMAGE_TIMEOUT = 2;

    /**
     * Bytes downloaded to read the image size from its header.
     */
    const IMAGE_PROBE_BYTES = 65536;

    /**
     * Largest og:image Facebook accepts, in bytes (8 MB).
     */
    const MAX_IMAGE_BYTES = 8388608;

    /**
     * Maximum length of the description, in characters.
     */
    const EXCERPT_LENGTH = 160;

    /**
     * Comment printed before our tags. Output before it belongs to other
     * wp_head callbacks.
     */
    const MARKER = '<!-- Travely Widget: Open Graph -->';

    /**
     * Name PHP gives to our output buffer, see ob_get_status().
     */
    const BUFFER_NAME = 'Travely_Widget_Open_Graph::filter_buffer';

    /**
     * Tags prepared for the current request.
     *
     * @since    1.2.0
     * @access   private
     * @var      array    $tags    Open Graph property => content.
     */
    private $tags = array();

    /**
     * Output buffer level opened around wp_head, or 0 when not buffering.
     *
     * @since    1.2.0
     * @access   private
     * @var      int    $buffer_level
     */
    private $buffer_level = 0;

    /**
     * Whether our tags have been printed.
     *
     * @since    1.2.0
     * @access   private
     * @var      bool    $tags_printed
     */
    private $tags_printed = false;

    /**
     * Whether the current request has already been handled.
     *
     * Some themes call wp_head more than once.
     *
     * @since    1.2.0
     * @access   private
     * @var      bool    $started
     */
    private $started = false;

    /**
     * Prepare the tags and start filtering the wp_head output.
     *
     * Runs before every other wp_head callback. When the page is not a shared
     * tour card, nothing is buffered and the page renders as before.
     *
     * @since 1.2.0
     */
    public function start_head() {
        if ( $this->started ) {
            return;
        }

        $this->started = true;
        $this->tags    = $this->prepare_tags();

        if ( empty( $this->tags ) ) {
            return;
        }

        if ( ob_start( array( $this, 'filter_buffer' ) ) ) {
            $this->buffer_level = ob_get_level();
        } else {
            self::log( 'could not start an output buffer; Open Graph tags of other plugins stay on the page' );
        }
    }

    /**
     * Print our tags and release the wp_head output.
     *
     * Runs after every other wp_head callback. If another callback changed the
     * output buffers in between, its buffer is not closed here: our buffer
     * stays open and is filtered when PHP flushes it.
     *
     * @since 1.2.0
     */
    public function finish_head() {
        if ( empty( $this->tags ) ) {
            return;
        }

        echo $this->render_tags( $this->tags ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        $this->tags         = array();
        $this->tags_printed = true;

        if ( 0 === $this->buffer_level ) {
            return;
        }

        $status = ob_get_status();

        if ( ob_get_level() === $this->buffer_level && isset( $status['name'] ) && self::BUFFER_NAME === $status['name'] ) {
            ob_end_flush();
        } else {
            self::log(
                sprintf(
                    'another wp_head callback changed the output buffers (level %d, expected %d); our buffer is released at the end of the page',
                    ob_get_level(),
                    $this->buffer_level
                )
            );
        }

        $this->buffer_level = 0;
    }

    /**
     * Output buffer callback: remove Open Graph tags printed before ours.
     *
     * Everything before the marker comes from other wp_head callbacks. The
     * buffer may be flushed early or released late by another plugin, so each
     * chunk is filtered on its own: chunks flushed before our tags are
     * stripped, later chunks pass through unchanged.
     *
     * @since 1.2.0
     * @param string $buffer Buffered output.
     * @return string
     */
    public function filter_buffer( $buffer ) {
        $buffer   = (string) $buffer;
        $position = strpos( $buffer, self::MARKER );

        if ( false !== $position ) {
            return self::strip_og_tags( substr( $buffer, 0, $position ) ) . substr( $buffer, $position );
        }

        return $this->tags_printed ? $buffer : self::strip_og_tags( $buffer );
    }

    /**
     * Build the tags for the current request.
     *
     * Reasons for printing nothing are logged only when ?path is present, so
     * ordinary pages stay quiet.
     *
     * @since 1.2.0
     * @return array Open Graph property => content, empty when nothing should be printed.
     */
    private function prepare_tags() {
        $path = self::get_query_path();

        if ( '' === $path ) {
            return array();
        }

        if ( ! get_option( 'travely_widget_open_graph', true ) ) {
            self::log( 'disabled in Settings -> Travely Widget' );

            return array();
        }

        $post            = get_queried_object();
        $widget_language = null;

        if ( is_singular() && $post instanceof WP_Post ) {
            $widget_language = self::find_results_widget_language( $post->post_content );
        }

        // Sites that render the results widget from a theme template can
        // enable the tags with this filter.
        $enabled = (bool) apply_filters(
            'travely_widget_enable_open_graph',
            null !== $widget_language,
            $post
        );

        if ( ! $enabled ) {
            self::log( 'the page content has no results widget; use the travely_widget_enable_open_graph filter if the theme renders it' );

            return array();
        }

        $hotel_id = self::parse_hotel_id( $path );

        if ( 0 === $hotel_id ) {
            self::log( 'the path does not point to exactly one hotel' );

            return array();
        }

        $language = Travely_Widget_Language::resolve_language(
            array( 'language' => null === $widget_language ? 'auto' : $widget_language )
        );
        $preview  = $this->get_preview( $hotel_id, $language );

        if ( empty( $preview ) ) {
            return array();
        }

        $tags = array(
            'og:type'         => 'website',
            'og:site_name'    => get_bloginfo( 'name' ),
            'og:url'          => self::get_current_url(),
            'og:title'        => $preview['title'],
            'og:description'  => $preview['description'],
            'og:image'        => $preview['image'],
            'og:image:width'  => $preview['image_width'],
            'og:image:height' => $preview['image_height'],
        );

        $tags = apply_filters(
            'travely_widget_open_graph_tags',
            $tags,
            $preview['hotel'],
            $hotel_id,
            $language
        );

        return is_array( $tags ) ? $tags : array();
    }

    /**
     * Read the ?path query parameter without changing it.
     *
     * The value is only matched against strict patterns and never printed.
     *
     * @since 1.2.0
     * @return string
     */
    private static function get_query_path() {
        if ( ! isset( $_GET['path'] ) || ! is_string( $_GET['path'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return '';
        }

        return wp_unslash( $_GET['path'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    }

    /**
     * Get the address of the current request as the visitor sent it.
     *
     * The host comes from the site settings, not from the request headers.
     *
     * @since 1.2.0
     * @return string
     */
    private static function get_current_url() {
        $home = wp_parse_url( home_url() );

        if ( ! is_array( $home ) || empty( $home['host'] ) ) {
            return '';
        }

        $origin = ( empty( $home['scheme'] ) ? 'https' : $home['scheme'] ) . '://' . $home['host'];

        if ( ! empty( $home['port'] ) ) {
            $origin .= ':' . absint( $home['port'] );
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ( ! is_string( $request_uri ) || '/' !== substr( $request_uri, 0, 1 ) ) {
            return '';
        }

        return $origin . $request_uri;
    }

    /**
     * Get the hotel preview from the cache or the Travely API.
     *
     * The hotel id comes from the visitor's URL, so only entries for hotels
     * the API knows are stored per id, which bounds them by the catalog:
     * - a complete preview is kept for a month;
     * - a preview built while the media server did not answer, and a known
     *   hotel without a usable photo, are kept for FAILURE_TTL;
     * - unknown hotels and API errors are not stored per id;
     * - a network failure is kept once for the whole site, so an outage costs
     *   one timeout per FAILURE_TTL and not one per hotel.
     * Uncached lookups are limited to LOOKUPS_PER_MINUTE for the whole site.
     *
     * @since 1.2.0
     * @param int    $hotel_id Travely hotel id.
     * @param string $language Travely language code.
     * @return array Preview, empty when the hotel cannot be described.
     */
    private function get_preview( $hotel_id, $language ) {
        $key    = self::CACHE_PREFIX . $hotel_id . '_' . $language;
        $cached = get_transient( $key );

        if ( is_array( $cached ) ) {
            if ( empty( $cached['ok'] ) ) {
                self::log( sprintf( 'hotel %d (%s), cached failure: %s', $hotel_id, $language, isset( $cached['reason'] ) ? $cached['reason'] : 'unknown' ) );

                return array();
            }

            return $cached;
        }

        $api_down = get_transient( self::API_DOWN_KEY );

        if ( false !== $api_down ) {
            self::log( sprintf( 'hotel %d (%s) skipped, the Travely API did not answer recently: %s', $hotel_id, $language, (string) $api_down ) );

            return array();
        }

        if ( ! self::take_lookup_budget() ) {
            self::log( sprintf( 'hotel %d (%s) skipped, more than %d lookups in this minute', $hotel_id, $language, self::LOOKUPS_PER_MINUTE ) );

            return array();
        }

        $preview = $this->fetch_preview( $hotel_id, $language );

        if ( empty( $preview['ok'] ) ) {
            self::log( sprintf( 'hotel %d (%s): %s', $hotel_id, $language, $preview['reason'] ) );

            if ( 'site' === $preview['scope'] ) {
                set_transient( self::API_DOWN_KEY, $preview['reason'], self::FAILURE_TTL );
            } elseif ( 'hotel' === $preview['scope'] ) {
                set_transient( $key, array( 'ok' => false, 'reason' => $preview['reason'] ), self::FAILURE_TTL );
            }

            return array();
        }

        set_transient( $key, $preview, empty( $preview['degraded'] ) ? MONTH_IN_SECONDS : self::FAILURE_TTL );

        return $preview;
    }

    /**
     * Request the public hotel description and build the preview.
     *
     * The endpoint is anonymous and returns only published hotels, so the
     * agency API key is not sent.
     *
     * @since 1.2.0
     * @param int    $hotel_id Travely hotel id.
     * @param string $language Travely language code.
     * @return array Preview with 'ok' => true, or a failure from failure().
     */
    private function fetch_preview( $hotel_id, $language ) {
        $origin   = untrailingslashit( (string) apply_filters( 'travely_widget_api_origin', 'https://travely.ee' ) );
        $url      = $origin . '/api/v1/hotel-descriptions/hotel/' . $hotel_id;
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => self::API_TIMEOUT,
                'headers' => array(
                    'Accept'           => 'application/json',
                    'Content-Language' => $language,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            // A network failure does not depend on the hotel id, so it cannot
            // be caused by choosing one.
            return self::failure( sprintf( 'GET %s failed: %s', $url, $response->get_error_message() ), 'site' );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );

        if ( 404 === $status ) {
            // Hidden hotel, fewer than five photos, a hidden country, or an id
            // that does not exist.
            return self::failure( sprintf( 'GET %s returned HTTP 404, the hotel is not published', $url ), 'request' );
        }

        if ( 200 !== $status ) {
            return self::failure( sprintf( 'GET %s returned HTTP %d', $url, $status ), 'request' );
        }

        $raw  = wp_remote_retrieve_body( $response );
        $body = json_decode( $raw, true );

        if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
            // For example a proxy or firewall page answered with 200.
            return self::failure(
                sprintf(
                    'GET %s returned an unexpected body (%s, %s): %s',
                    $url,
                    self::header_text( $response, 'content-type' ),
                    json_last_error_msg(),
                    self::snippet( $raw )
                ),
                'request'
            );
        }

        $data  = $body['data'];
        $hotel = array(
            'name'    => self::text_at( $data, array( 'hotel', 'name' ) ),
            'stars'   => self::text_at( $data, array( 'hotel', 'hotelStar', 'name' ) ),
            'region'  => self::text_at( $data, array( 'hotel', 'region', 'name' ) ),
            'country' => self::text_at( $data, array( 'hotel', 'country', 'name' ) ),
            'excerpt' => self::build_excerpt( self::text_at( $data, array( 'main', 'description' ) ), self::EXCERPT_LENGTH ),
        );
        $image = self::text_at( $data, array( 'hotel', 'preview', 'url' ) );

        if ( '' === $hotel['name'] || ! preg_match( '#^https?://#i', $image ) ) {
            return self::failure( sprintf( 'GET %s has no hotel name or main photo URL', $url ), 'hotel' );
        }

        $picked = $this->pick_image( $image );

        if ( null === $picked ) {
            // A broken or rejected image would be worse than the site logo.
            return self::failure( sprintf( 'no usable image for main photo %s', $image ), 'hotel' );
        }

        return array(
            'ok'           => true,
            'degraded'     => $picked['degraded'],
            'hotel'        => $hotel,
            'title'        => self::build_title( $hotel ),
            'description'  => '' !== $hotel['excerpt'] ? $hotel['excerpt'] : self::build_place( $hotel ),
            'image'        => $picked['url'],
            'image_width'  => $picked['width'],
            'image_height' => $picked['height'],
        );
    }

    /**
     * Choose the image for the preview.
     *
     * The wide variant is close to the 1.91:1 format of Facebook link cards
     * and usually several times smaller than the original. Not every photo
     * has it, and some variants are heavier than their original, so the
     * original is the fallback.
     *
     * @since 1.2.0
     * @param string $original_url URL of the main hotel photo.
     * @return array|null url, width, height (empty when unknown) and degraded
     *                    (the media server did not answer, retry soon);
     *                    null when no candidate can be used.
     */
    private function pick_image( $original_url ) {
        $degraded = false;

        foreach ( array_filter( array( self::variant_url( $original_url ), $original_url ) ) as $url ) {
            $probe = $this->probe_image( $url );

            if ( 'ok' === $probe['state'] ) {
                if ( 0 === $probe['width'] || 0 === $probe['height'] ) {
                    self::log( sprintf( 'size of %s not found in its first %d bytes', $url, self::IMAGE_PROBE_BYTES ) );
                }

                return array(
                    'url'      => $url,
                    'width'    => $probe['width'] > 0 ? (string) $probe['width'] : '',
                    'height'   => $probe['height'] > 0 ? (string) $probe['height'] : '',
                    'degraded' => $degraded,
                );
            }

            self::log( sprintf( 'image %s: %s', $url, $probe['reason'] ) );

            if ( 'unreachable' === $probe['state'] ) {
                $degraded = true;
            }
        }

        if ( ! $degraded ) {
            return null;
        }

        return array(
            'url'      => $original_url,
            'width'    => '',
            'height'   => '',
            'degraded' => true,
        );
    }

    /**
     * Check an image and read its size from the first bytes.
     *
     * @since 1.2.0
     * @param string $url Image URL.
     * @return array state: 'ok' (with width and height, 0 when unreadable),
     *               'unusable' (missing, not an image or too large) or
     *               'unreachable' (with reason).
     */
    private function probe_image( $url ) {
        $response = wp_remote_get(
            $url,
            array(
                'timeout'             => self::IMAGE_TIMEOUT,
                // limit_response_size alone does not stop the transfer, the
                // Range header does. The limit stays for servers that ignore it.
                'limit_response_size' => self::IMAGE_PROBE_BYTES,
                'headers'             => array( 'Range' => 'bytes=0-' . ( self::IMAGE_PROBE_BYTES - 1 ) ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return array(
                'state'  => 'unreachable',
                'reason' => $response->get_error_message(),
            );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );

        if ( 200 !== $status && 206 !== $status ) {
            // S3 answers 403 for a missing object. Timeouts and rate limits
            // are temporary.
            $missing = $status >= 400 && $status < 500 && ! in_array( $status, array( 408, 429 ), true );

            return array(
                'state'  => $missing ? 'unusable' : 'unreachable',
                'reason' => 'HTTP ' . $status,
            );
        }

        $type = self::header_text( $response, 'content-type' );

        if ( 0 !== stripos( $type, 'image/' ) ) {
            return array(
                'state'  => 'unusable',
                'reason' => 'not an image: ' . $type,
            );
        }

        // A 206 response tells the full file size in Content-Range
        // ("bytes 0-65535/1092021"); a server that ignored Range sends the
        // whole file and its Content-Length.
        $total = 0;

        if ( preg_match( '#/(\d+)\s*$#', self::header_text( $response, 'content-range' ), $matches ) ) {
            $total = (int) $matches[1];
        } elseif ( 200 === $status ) {
            $total = (int) self::header_text( $response, 'content-length' );
        }

        if ( $total > self::MAX_IMAGE_BYTES ) {
            return array(
                'state'  => 'unusable',
                'reason' => sprintf( 'too large for Facebook: %.1f MB, the limit is 8 MB', $total / 1048576 ),
            );
        }

        // Only the beginning of the file is downloaded, so the parser may
        // complain about the missing tail after it has already read the size.
        $size = @getimagesizefromstring( wp_remote_retrieve_body( $response ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        return array(
            'state'  => 'ok',
            'width'  => is_array( $size ) ? (int) $size[0] : 0,
            'height' => is_array( $size ) ? (int) $size[1] : 0,
        );
    }

    /**
     * Render the tags.
     *
     * Values changed by the travely_widget_open_graph_tags filter are escaped
     * here as well; empty and non-scalar values are skipped.
     *
     * @since 1.2.0
     * @param array $tags Open Graph property => content.
     * @return string
     */
    private function render_tags( $tags ) {
        $html = "\n" . self::MARKER . "\n";

        foreach ( $tags as $property => $content ) {
            if ( ! is_string( $property ) || ! is_scalar( $content ) || '' === trim( (string) $content ) ) {
                continue;
            }

            $content = (string) $content;
            $content = in_array( $property, array( 'og:url', 'og:image' ), true ) ? esc_url( $content ) : esc_attr( $content );

            if ( '' === $content ) {
                continue;
            }

            $html .= sprintf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $property ), $content );
        }

        return $html;
    }

    /**
     * Find the results widget on the page and its language attribute.
     *
     * @since 1.2.0
     * @param string $content Post content.
     * @return string|null The language attribute ('auto' when not set), or null when the page has no results widget.
     */
    public static function find_results_widget_language( $content ) {
        if ( ! is_string( $content ) || '' === $content ) {
            return null;
        }

        if ( has_shortcode( $content, 'travely-widget-results' ) ) {
            if ( preg_match( '/' . get_shortcode_regex( array( 'travely-widget-results' ) ) . '/', $content, $matches ) ) {
                $atts = shortcode_parse_atts( $matches[3] );

                if ( is_array( $atts ) && isset( $atts['language'] ) && is_string( $atts['language'] ) ) {
                    return $atts['language'];
                }
            }

            return 'auto';
        }

        if ( function_exists( 'has_block' ) && has_block( 'travely/widget-results', $content ) ) {
            $language = self::find_block_language( parse_blocks( $content ) );

            return null === $language ? 'auto' : $language;
        }

        return null;
    }

    /**
     * Find the language attribute of the first results block, nested blocks included.
     *
     * @since 1.2.0
     * @param array $blocks Parsed blocks.
     * @return string|null
     */
    private static function find_block_language( $blocks ) {
        foreach ( $blocks as $block ) {
            if ( isset( $block['blockName'] ) && 'travely/widget-results' === $block['blockName'] ) {
                return ( isset( $block['attrs']['language'] ) && is_string( $block['attrs']['language'] ) )
                    ? $block['attrs']['language']
                    : 'auto';
            }

            if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
                $language = self::find_block_language( $block['innerBlocks'] );

                if ( null !== $language ) {
                    return $language;
                }
            }
        }

        return null;
    }

    /**
     * Extract the Travely hotel id from the iframe route kept in ?path.
     *
     * Tour card: /tour-search/tours/<searchKey>/<hotelId>[/<tourKey>]. The
     * search key expires, so only the hotel id is used.
     * Best tour: /tour-search/best/<bestId>?filter[hotelIds]=<hotelId>&...;
     * the booking application adds the best tour filters to the route.
     *
     * @since 1.2.0
     * @param string $path Value of the ?path query parameter.
     * @return int Hotel id, or 0 when the route does not point to exactly one hotel.
     */
    public static function parse_hotel_id( $path ) {
        if ( ! is_string( $path ) || '' === $path ) {
            return 0;
        }

        $fragment = explode( '#', $path, 2 );
        $parts    = explode( '?', $fragment[0], 2 );
        $route    = $parts[0];
        $query    = isset( $parts[1] ) ? $parts[1] : '';

        if ( preg_match( '#^/tour-search/tours/[^/]+/(\d{1,9})(?:/|$)#', $route, $matches ) ) {
            return self::positive_id( $matches[1] );
        }

        // parse_str() keeps only the last of repeated keys, so the query is
        // split by hand to notice several hotels.
        $ids = array();

        foreach ( explode( '&', $query ) as $pair ) {
            if ( '' === $pair ) {
                continue;
            }

            $pair = explode( '=', $pair, 2 );

            if ( ! preg_match( '/^filter\[hotelIds\](?:\[\d*\])?$/', urldecode( $pair[0] ) ) ) {
                continue;
            }

            $value = isset( $pair[1] ) ? urldecode( $pair[1] ) : '';
            $id    = preg_match( '/^\d{1,9}$/', $value ) ? self::positive_id( $value ) : 0;

            if ( 0 === $id ) {
                return 0;
            }

            $ids[ $id ] = true;
        }

        return 1 === count( $ids ) ? (int) key( $ids ) : 0;
    }

    /**
     * Build the title: hotel name and stars first, so that truncation cuts the place, not the name.
     *
     * @since 1.2.0
     * @param array $hotel name, stars, region, country.
     * @return string For example "Sunrise Remal Beach 5* — Sharm-El-Sheikh, Egypt".
     */
    public static function build_title( $hotel ) {
        $title = trim( $hotel['name'] . ' ' . $hotel['stars'] );
        $place = self::build_place( $hotel );

        return '' === $place ? $title : $title . ' — ' . $place;
    }

    /**
     * Build the place: resort and country, without empty or repeated parts.
     *
     * @since 1.2.0
     * @param array $hotel region, country.
     * @return string
     */
    public static function build_place( $hotel ) {
        $parts = array();

        foreach ( array( $hotel['region'], $hotel['country'] ) as $part ) {
            if ( '' !== $part && ! in_array( $part, $parts, true ) ) {
                $parts[] = $part;
            }
        }

        return implode( ', ', $parts );
    }

    /**
     * Build the description: the first sentence of the hotel description.
     *
     * A first sentence longer than the limit is cut at a word boundary.
     *
     * @since 1.2.0
     * @param string $html  Hotel description, HTML.
     * @param int    $limit Maximum length, in characters.
     * @return string
     */
    public static function build_excerpt( $html, $limit ) {
        $text = self::plain_text( $html );

        if ( '' === $text ) {
            return '';
        }

        // The first sentence ends with punctuation followed by a space or the
        // end of the text. The minimum length skips leading abbreviations.
        if ( preg_match( '/^(.{20,}?[.!?…])(?:\s|$)/su', $text, $matches ) ) {
            $text = $matches[1];
        }

        if ( mb_strlen( $text, 'UTF-8' ) <= $limit ) {
            return $text;
        }

        $cut     = mb_substr( $text, 0, $limit - 1, 'UTF-8' );
        $trimmed = preg_replace( '/\s+\S*$/u', '', $cut );

        if ( is_string( $trimmed ) && '' !== $trimmed ) {
            $cut = $trimmed;
        }

        // rtrim() works on bytes and would break multibyte letters here.
        $trimmed = preg_replace( '/[\s,;:\-–—]+$/u', '', $cut );

        return ( is_string( $trimmed ) ? $trimmed : $cut ) . '…';
    }

    /**
     * Turn description HTML into a single line of plain text.
     *
     * @since 1.2.0
     * @param string $html HTML.
     * @return string
     */
    private static function plain_text( $html ) {
        $html = (string) $html;

        // Keep words of neighbouring block elements apart.
        $html = preg_replace( '#<(?:br|/p|/li|/div|/h[1-6])\b[^>]*>#i', ' ', $html );
        $text = html_entity_decode( strip_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text = preg_replace( '/[\s\x{00A0}]+/u', ' ', $text );

        return trim( (string) $text );
    }

    /**
     * Get the URL of the wide variant of a Travely media image.
     *
     * The media server stores it next to the original with the "-1.95" suffix.
     *
     * @since 1.2.0
     * @param string $url Original image URL.
     * @return string Variant URL, or an empty string when the URL has no file extension.
     */
    public static function variant_url( $url ) {
        if ( ! preg_match( '#^([^?\#]*/[^/?\#]+)(\.[a-z0-9]{2,5})((?:[?\#].*)?)$#i', (string) $url, $matches ) ) {
            return '';
        }

        if ( '-1.95' === substr( $matches[1], -5 ) ) {
            return '';
        }

        return $matches[1] . '-1.95' . $matches[2] . $matches[3];
    }

    /**
     * Remove Open Graph meta tags from HTML.
     *
     * Only <meta> tags whose property (or, incorrectly, name) starts with
     * "og:" are removed. Twitter tags, JSON-LD and the canonical link stay.
     *
     * @since 1.2.0
     * @param string $html wp_head output.
     * @return string
     */
    public static function strip_og_tags( $html ) {
        $stripped = preg_replace(
            '/<meta\b[^>]*?\b(?:property|name)\s*=\s*(["\']?)og:[^>]*>[ \t]*(?:\r?\n)?/i',
            '',
            (string) $html
        );

        return is_string( $stripped ) ? $stripped : (string) $html;
    }

    /**
     * Build a failed lookup.
     *
     * @since 1.2.0
     * @param string $reason What went wrong, for the debug log and the cached entry.
     * @param string $scope  What the failure is remembered for: 'site' (the API
     *                       did not answer), 'hotel' (a hotel the API knows) or
     *                       'request' (nothing is stored).
     * @return array
     */
    private static function failure( $reason, $scope ) {
        return array(
            'ok'     => false,
            'reason' => $reason,
            'scope'  => $scope,
        );
    }

    /**
     * Count one uncached lookup against the per-minute limit of the site.
     *
     * The count is approximate under concurrent requests, which is enough to
     * bound the load.
     *
     * @since 1.2.0
     * @return bool Whether the lookup may run.
     */
    private static function take_lookup_budget() {
        $minute = (int) floor( time() / 60 );
        $budget = get_transient( self::BUDGET_KEY );

        if ( ! is_array( $budget ) || ! isset( $budget['minute'], $budget['count'] ) || (int) $budget['minute'] !== $minute ) {
            $budget = array(
                'minute' => $minute,
                'count'  => 0,
            );
        }

        if ( $budget['count'] >= self::LOOKUPS_PER_MINUTE ) {
            return false;
        }

        $budget['count']++;
        set_transient( self::BUDGET_KEY, $budget, 2 * MINUTE_IN_SECONDS );

        return true;
    }

    /**
     * Read a response header as a string.
     *
     * @since 1.2.0
     * @param array  $response HTTP API response.
     * @param string $name     Header name.
     * @return string
     */
    private static function header_text( $response, $name ) {
        $value = wp_remote_retrieve_header( $response, $name );

        return is_array( $value ) ? (string) reset( $value ) : (string) $value;
    }

    /**
     * Shorten a response body to one line for the log.
     *
     * @since 1.2.0
     * @param string $text Response body.
     * @return string
     */
    private static function snippet( $text ) {
        $text = preg_replace( '/\s+/', ' ', (string) $text );

        return substr( trim( (string) $text ), 0, 200 );
    }

    /**
     * Read a nested string value.
     *
     * @since 1.2.0
     * @param array $data Decoded JSON.
     * @param array $keys Path of keys.
     * @return string Trimmed value, or an empty string.
     */
    private static function text_at( $data, $keys ) {
        foreach ( $keys as $key ) {
            if ( ! is_array( $data ) || ! isset( $data[ $key ] ) ) {
                return '';
            }

            $data = $data[ $key ];
        }

        return is_string( $data ) ? trim( $data ) : '';
    }

    /**
     * Convert a digit string to a positive id.
     *
     * @since 1.2.0
     * @param string $value Digits.
     * @return int Id, or 0.
     */
    private static function positive_id( $value ) {
        $id = (int) $value;

        return $id > 0 ? $id : 0;
    }

    /**
     * Log why the tags were not printed or were printed incomplete. Only with WP_DEBUG.
     *
     * @since 1.2.0
     * @param string $message Message.
     */
    private static function log( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Travely Widget Open Graph: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }
}
