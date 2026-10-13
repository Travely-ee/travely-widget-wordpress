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
 * The opened hotel is taken from the ?path query parameter: the widget writes
 * the iframe route there while a tour card is open, and its Share button puts
 * it into shared links, best tours included. The hotel's public description
 * is read from the Travely API.
 *
 * When our tags are printed, Open Graph tags printed by other wp_head
 * callbacks are removed (see finish_head() for the one case where this is
 * incomplete). Pages without our tags, including tour pages whose preview
 * could not be built, are left untouched.
 *
 * @since      1.2.0
 * @package    Travely_Widget
 * @subpackage Travely_Widget/public
 * @author     Travely Solutions OÜ <info@travely.ee>
 */
class Travely_Widget_Open_Graph {

    /**
     * Transient prefix. Bump the version segment when the structure of a
     * cached preview changes: entries under the old prefix are then ignored
     * and expire on their own.
     */
    const CACHE_PREFIX = 'travely_widget_og_v1_';

    /**
     * Site-wide transient, set for FAILURE_TTL when the hotel description
     * request gets no HTTP response (DNS, connection, TLS or timeout). It is
     * not cleared when the API recovers, and HTTP error statuses never set it.
     */
    const API_DOWN_KEY = self::CACHE_PREFIX . 'api_down';

    /**
     * Site-wide transient counting API lookups in the current minute.
     */
    const BUDGET_KEY = self::CACHE_PREFIX . 'budget';

    /**
     * Uncached hotel lookups allowed per minute for the whole site.
     *
     * The hotel id comes from the visitor's URL, so this bounds the outgoing
     * requests a crawl of random ids can cause.
     */
    const LOOKUPS_PER_MINUTE = 30;

    /**
     * How long a result worth retrying soon is cached, in seconds: the
     * site-wide API_DOWN_KEY flag, a returned hotel without a name or a usable
     * photo, and a preview whose image the media server did not confirm.
     * Other failed lookups are not cached at all, see get_preview().
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
     * Bytes downloaded from the start of an image file to read its pixel size.
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
     * Level of our output buffer while finish_head() may still close it; 0
     * before buffering starts and after finish_head() has run.
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
     * Runs before the other wp_head callbacks (registered at PHP_INT_MIN).
     * When no tags are prepared, nothing is buffered and the page renders as
     * before.
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
     * Runs after the other wp_head callbacks (registered at PHP_INT_MAX). Our
     * buffer is closed only when it is still on top (same level and handler
     * name); closing anything else would end another callback's buffer. Two
     * cases are left as they are:
     * - another callback opened a buffer on top of ours: ours stays open and
     *   is filtered when PHP flushes it at the end of the page;
     * - another callback already closed ours: output up to then was filtered,
     *   but Open Graph tags printed after that are not removed.
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
                    'another wp_head callback changed the output buffers (level %d, expected %d, top handler %s); our buffer was not closed here',
                    ob_get_level(),
                    $this->buffer_level,
                    isset( $status['name'] ) ? $status['name'] : 'none'
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
        // enable the tags with this filter. The widget's language attribute
        // is not visible then, so the language is resolved as for "auto".
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
     * Read the raw ?path query parameter (unslashed, not sanitized).
     *
     * The value is only matched against strict patterns. It reaches the page
     * only as part of og:url (the full request URI), which render_tags()
     * escapes with esc_url().
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
     * Build og:url: scheme, host and port from home_url(), then the request
     * path and query as the visitor sent them. The host is not read from the
     * request headers, so a forged Host header cannot change og:url.
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
     * The hotel id and the language come from the visitor's URL, so only
     * hotels the API returned are stored per id; entries are bounded by the
     * catalog times the four languages:
     * - a complete preview is kept for a month;
     * - a preview whose image the media server did not confirm (see
     *   pick_image()), and a returned hotel without a name or a usable photo,
     *   are kept for FAILURE_TTL;
     * - a 404 (unpublished or unknown hotel), any other HTTP status and an
     *   unexpected body are not stored; the next request asks again.
     * When the API request gets no HTTP response, API_DOWN_KEY stops all
     * uncached lookups on the site for FAILURE_TTL, so an outage costs about
     * one timeout per FAILURE_TTL rather than one per hotel; cached previews
     * keep working. Uncached lookups are limited to LOOKUPS_PER_MINUTE for the
     * whole site. Each one can hold up wp_head for API_TIMEOUT plus two
     * IMAGE_TIMEOUTs.
     *
     * @since 1.2.0
     * @param int    $hotel_id Travely hotel id.
     * @param string $language Travely language code.
     * @return array Preview, empty when the hotel cannot be described.
     */
    private function get_preview( $hotel_id, $language ) {
        $key     = self::CACHE_PREFIX . $hotel_id . '_' . $language;
        $subject = sprintf( 'hotel %d (%s)', $hotel_id, $language );
        $cached  = get_transient( $key );

        if ( is_array( $cached ) ) {
            if ( empty( $cached['ok'] ) ) {
                self::log( $subject . ', cached failure: ' . ( isset( $cached['reason'] ) ? $cached['reason'] : 'unknown' ) );

                return array();
            }

            return $cached;
        }

        $api_down = get_transient( self::API_DOWN_KEY );

        if ( false !== $api_down ) {
            self::log( $subject . ' skipped, the Travely API did not answer recently: ' . $api_down );

            return array();
        }

        if ( ! self::take_lookup_budget() ) {
            self::log( sprintf( '%s skipped, more than %d lookups in this minute', $subject, self::LOOKUPS_PER_MINUTE ) );

            return array();
        }

        $preview = $this->fetch_preview( $hotel_id, $language );

        if ( empty( $preview['ok'] ) ) {
            self::log( $subject . ': ' . $preview['reason'] );

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
     * The endpoint allows anonymous calls and hotel descriptions are not per
     * agency, so the agency API key is not sent. Anonymous callers get only
     * published hotels; the rest answer 404.
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
            // No HTTP response (DNS, connection, TLS or timeout) says the API
            // is unreachable rather than anything about this hotel, so it is
            // remembered once for the whole site. Unknown ids get a fast 404
            // and cannot set it; only a timeout on a real hotel could.
            return self::failure( sprintf( 'GET %s failed: %s', $url, $response->get_error_message() ), 'site' );
        }

        $status = (int) wp_remote_retrieve_response_code( $response );

        if ( 404 === $status ) {
            // Not published: the description is not shown (no text, no main
            // photo or fewer than five photos), the country is hidden, or the
            // id has no description.
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
            // The original was rejected (missing, not an image or too large).
            // A card with a broken image would be worse than the page's usual
            // preview, which stays because nothing is printed.
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
     * and usually smaller than the original. Not every photo has one, and a
     * variant can even exceed MAX_IMAGE_BYTES when its original does not, so
     * the original is the next candidate when the variant is missing,
     * rejected or unreachable.
     *
     * Only a checked and accepted candidate is used, with one exception: when
     * the original is unreachable, it is used unchecked and without a size.
     * An original the server rejected is never used.
     *
     * @since 1.2.0
     * @param string $original_url URL of the main hotel photo.
     * @return array|null url, width, height (empty when unknown) and degraded
     *                    (true when any candidate was unreachable, so the
     *                    preview is cached only for FAILURE_TTL); null when
     *                    the original was rejected and no variant was accepted.
     */
    private function pick_image( $original_url ) {
        $degraded = false;

        // The original is always the last candidate, so $probe holds its
        // result after the loop.
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

        if ( 'unreachable' !== $probe['state'] ) {
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
     *               'unusable' (a 4xx other than 408 and 429, not an image,
     *               or larger than MAX_IMAGE_BYTES) or 'unreachable' (no
     *               response or any other status, such as 5xx, 408 or 429),
     *               both with reason.
     */
    private function probe_image( $url ) {
        $response = wp_remote_get(
            $url,
            array(
                'timeout'             => self::IMAGE_TIMEOUT,
                // limit_response_size only discards bytes past the limit;
                // WordPress still downloads the whole file. The Range header
                // keeps the transfer short. The limit only bounds memory on
                // servers that ignore Range.
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
                'reason' => sprintf( 'too large for Facebook: %.1f MB, the limit is %d MB', $total / MB_IN_BYTES, self::MAX_IMAGE_BYTES / MB_IN_BYTES ),
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
            $atts = preg_match( '/' . get_shortcode_regex( array( 'travely-widget-results' ) ) . '/', $content, $matches )
                ? shortcode_parse_atts( $matches[3] )
                : array();

            return self::language_attribute( $atts );
        }

        if ( has_block( 'travely/widget-results', $content ) ) {
            $language = self::find_block_language( parse_blocks( $content ) );

            return null === $language ? 'auto' : $language;
        }

        return null;
    }

    /**
     * Read the language attribute of a shortcode or block.
     *
     * @since 1.2.0
     * @param mixed $attributes Shortcode or block attributes.
     * @return string The attribute, or 'auto' when it is not set.
     */
    private static function language_attribute( $attributes ) {
        return ( is_array( $attributes ) && isset( $attributes['language'] ) && is_string( $attributes['language'] ) )
            ? $attributes['language']
            : 'auto';
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
                return self::language_attribute( isset( $block['attrs'] ) ? $block['attrs'] : null );
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
     * Any other route counts only when its query names exactly one hotel in
     * filter[hotelIds] (repeats of the same id are allowed). This is how best
     * tours arrive: the booking application adds the best tour filters to
     * /tour-search/best/<bestId>, and the Share button copies them into the
     * link. The host address bar of a best tour keeps the bare route, which
     * gives 0.
     *
     * @since 1.2.0
     * @param string $path Value of the ?path query parameter.
     * @return int Hotel id, or 0 when the route does not point to exactly one hotel.
     */
    public static function parse_hotel_id( $path ) {
        if ( ! is_string( $path ) || '' === $path ) {
            return 0;
        }

        $without_hash          = explode( '#', $path, 2 );
        list( $route, $query ) = array_pad( explode( '?', $without_hash[0], 2 ), 2, '' );

        if ( preg_match( '#^/tour-search/tours/[^/]+/(\d{1,9})(?:/|$)#', $route, $matches ) ) {
            return (int) $matches[1];
        }

        // parse_str() keeps only the last of repeated keys, so the query is
        // split by hand to notice several hotels.
        $hotel_id = 0;

        foreach ( explode( '&', $query ) as $pair ) {
            list( $name, $value ) = array_pad( explode( '=', $pair, 2 ), 2, '' );

            if ( ! preg_match( '/^filter\[hotelIds\](?:\[\d*\])?$/', urldecode( $name ) ) ) {
                continue;
            }

            $value = urldecode( $value );
            $id    = preg_match( '/^\d{1,9}$/', $value ) ? (int) $value : 0;

            if ( 0 === $id || ( 0 !== $hotel_id && $id !== $hotel_id ) ) {
                return 0;
            }

            $hotel_id = $id;
        }

        return $hotel_id;
    }

    /**
     * Build the title: hotel name and stars first, so that when Facebook or a
     * messenger shortens a long title, it cuts the place, not the name.
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
     * When no sentence end is found, or the sentence is longer than the limit,
     * the text is cut at a word boundary and ends with '…'.
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
     * @return string Variant URL, or an empty string when the URL has no file
     *                extension or already is the variant.
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
     * The window is the clock minute. Reading and writing the count is not
     * atomic, so concurrent requests can overshoot the limit (at worst by the
     * number of PHP workers); the load stays bounded.
     *
     * @since 1.2.0
     * @return bool Whether the lookup may run.
     */
    private static function take_lookup_budget() {
        $minute = intdiv( time(), 60 );
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
     * Write a diagnostic message to the PHP error log. Only with WP_DEBUG.
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
