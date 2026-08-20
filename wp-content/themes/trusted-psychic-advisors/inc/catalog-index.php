<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Static catalog index generator.
 *
 * Produces a single compact JSON file describing every published `psychic`
 * post, so the client-side sort/filter engine (assets/js/catalog-filter.js)
 * can run entirely in the browser against a static export — no PHP, no
 * WP REST, no admin-ajax at request time.
 *
 * Output:
 *   wp-content/uploads/catalog/index-{version}.json   (immutable, versioned)
 *   wp-content/uploads/catalog/index.json              (stable copy, same bytes)
 */

define( 'TPAD_CATALOG_STALE_OPTION', 'tpad_catalog_index_stale' );
define( 'TPAD_CATALOG_VER_OPTION',   'tpad_catalog_index_ver' );
define( 'TPAD_CATALOG_LOCK_KEY',     'tpad_catalog_rebuild_lock' );
define( 'TPAD_CATALOG_IMAGE_BASE',   '/wp-content/uploads/psychics/' );
define( 'TPAD_CATALOG_PERMALINK_BASE', '/psychics/' );

/*
 * Rank-scalar constants for _bpr_rank (see tpad_catalog_compute_rank()
 * below). Sized against the real dataset, checked 2026-08-04:
 *   - max _total_ratings network-wide == 988,372
 *   - rating is always rounded to 1 decimal (0.0 .. 5.0 in steps of 0.1)
 *     before this formula ever sees it (same rounding the JSON index /
 *     catalog-filter.js use), so "rating_tenths" ranges 0..50.
 * RATING_MULTIPLIER must exceed the largest possible review-count value
 * so that even the smallest rating step (1 tenth) outweighs ANY review-
 * count difference. REVIEWED_OFFSET must exceed the largest possible
 * zero-review scalar (50 * RATING_MULTIPLIER) so that EVERY reviewed
 * advisor outranks EVERY zero-review advisor. See tpad_catalog_compute_rank()
 * for the full proof.
 */
define( 'TPAD_RANK_RATING_MULTIPLIER', 10000000 );  // 1e7  (> 988,372)
define( 'TPAD_RANK_REVIEWED_OFFSET',   1000000000 ); // 1e9 (> 50 * 1e7)

/* ── Paths / URLs ──────────────────────────────────────────────── */

function tpad_catalog_index_dir() {
    $uploads = wp_upload_dir();
    return trailingslashit( $uploads['basedir'] ) . 'catalog';
}

function tpad_catalog_index_stable_path() {
    return trailingslashit( tpad_catalog_index_dir() ) . 'index.json';
}

function tpad_catalog_index_versioned_path( $version ) {
    return trailingslashit( tpad_catalog_index_dir() ) . 'index-' . $version . '.json';
}

function tpad_catalog_index_url() {
    return home_url( '/wp-content/uploads/catalog/index.json' );
}

/* ── Version stamp ─────────────────────────────────────────────── */

/**
 * Short hash derived from the state of the psychic catalog. Cheap: three
 * aggregate queries, no per-post work. Changes whenever a psychic is
 * added/removed/edited or a specialty/skill term's membership changes.
 */
function tpad_catalog_index_version() {
    global $wpdb;

    $count = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psychic' AND post_status = 'publish'"
    );
    $max_modified = (string) $wpdb->get_var(
        "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_type = 'psychic' AND post_status = 'publish'"
    );
    $max_tax_count = (int) $wpdb->get_var(
        "SELECT MAX(count) FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ('psychic_specialty','psychic_skill')"
    );

    return substr( md5( $count . '|' . $max_modified . '|' . $max_tax_count ), 0, 10 );
}

/* ── Staleness tracking ────────────────────────────────────────── */

/**
 * Cheap "something might have changed" flag. Hooked to writes that can
 * affect the index; does no querying itself, so it is safe to call on
 * every save.
 */
function tpad_catalog_flag_stale() {
    update_option( TPAD_CATALOG_STALE_OPTION, 1, false );
}
add_action( 'save_post_psychic', 'tpad_catalog_flag_stale' );

function tpad_catalog_flag_stale_on_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
    if ( in_array( $taxonomy, array( 'psychic_specialty', 'psychic_skill' ), true ) ) {
        tpad_catalog_flag_stale();
    }
}
add_action( 'set_object_terms', 'tpad_catalog_flag_stale_on_terms', 10, 4 );

function tpad_catalog_flag_stale_on_delete( $postid, $post = null ) {
    $post_type = $post && isset( $post->post_type ) ? $post->post_type : get_post_type( $postid );
    if ( 'psychic' === $post_type ) {
        tpad_catalog_flag_stale();
    }
}
add_action( 'deleted_post', 'tpad_catalog_flag_stale_on_delete', 10, 2 );

/**
 * Cheap freshness check, safe to call on every front-end page load.
 *
 * Fast path (the common case): no stale flag set and the file exists —
 * a single option read + file_exists(), no SQL.
 *
 * Slow path (only when something flagged a possible change, or the
 * stored version is missing): recompute the version stamp (3 aggregate
 * queries) and compare against the stored option.
 */
function tpad_catalog_index_is_fresh() {
    if ( ! file_exists( tpad_catalog_index_stable_path() ) ) {
        return false;
    }

    $stored_ver = get_option( TPAD_CATALOG_VER_OPTION );
    $stale_flag = get_option( TPAD_CATALOG_STALE_OPTION );

    if ( ! $stale_flag && $stored_ver ) {
        return true;
    }

    $current_ver = tpad_catalog_index_version();
    if ( $stored_ver && $current_ver === $stored_ver ) {
        delete_option( TPAD_CATALOG_STALE_OPTION );
        return true;
    }

    return false;
}

/**
 * Wired to front-end page loads on the catalog templates. Does nothing
 * unless the cheap freshness check above says a rebuild is warranted.
 */
function tpad_catalog_ensure_index_fresh() {
    if ( tpad_catalog_index_is_fresh() ) {
        return;
    }
    tpad_catalog_rebuild_index();
}

function tpad_catalog_maybe_ensure_fresh_on_request() {
    if ( is_admin() ) {
        return;
    }
    if ( is_post_type_archive( 'psychic' ) || is_tax( 'psychic_specialty' ) || is_tax( 'psychic_skill' ) ) {
        tpad_catalog_ensure_index_fresh();
    }
}
add_action( 'template_redirect', 'tpad_catalog_maybe_ensure_fresh_on_request' );

/* ── Atomic write helper ───────────────────────────────────────── */

function tpad_catalog_write_atomic( $path, $contents ) {
    $tmp = $path . '.tmp-' . wp_generate_password( 8, false, false );
    $bytes = @file_put_contents( $tmp, $contents );
    if ( false === $bytes ) {
        return false;
    }
    if ( ! @rename( $tmp, $path ) ) {
        @unlink( $tmp );
        return false;
    }
    return true;
}

/* ── Rebuild entry point (also callable via `wp eval`) ───────────── */

/**
 * Real rebuild routine. Idempotent: if the index is already fresh it
 * returns immediately without touching the database beyond the cheap
 * freshness check. Guarded by a short transient lock so rapid/concurrent
 * triggers (multiple saves in a row, a save racing a scheduled hit)
 * can't stack overlapping rebuilds.
 *
 * @param bool $force Rebuild even if the freshness check says it's current.
 * @return array Result info (rebuilt: bool, reason/version/records).
 */
function tpad_catalog_rebuild_index( $force = false ) {
    if ( ! $force && tpad_catalog_index_is_fresh() ) {
        return array( 'rebuilt' => false, 'reason' => 'fresh' );
    }

    if ( false !== get_transient( TPAD_CATALOG_LOCK_KEY ) ) {
        return array( 'rebuilt' => false, 'reason' => 'locked' );
    }
    set_transient( TPAD_CATALOG_LOCK_KEY, 1, 2 * MINUTE_IN_SECONDS );

    try {
        $version = tpad_catalog_index_version();
        $dir = tpad_catalog_index_dir();
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }

        $data = tpad_catalog_build_index_data( $version );
        $json = wp_json_encode( $data );

        if ( false === $json ) {
            delete_transient( TPAD_CATALOG_LOCK_KEY );
            return array( 'rebuilt' => false, 'reason' => 'json_encode_failed' );
        }

        $ok_versioned = tpad_catalog_write_atomic( tpad_catalog_index_versioned_path( $version ), $json );
        $ok_stable    = tpad_catalog_write_atomic( tpad_catalog_index_stable_path(), $json );

        if ( ! $ok_versioned || ! $ok_stable ) {
            delete_transient( TPAD_CATALOG_LOCK_KEY );
            return array( 'rebuilt' => false, 'reason' => 'write_failed' );
        }

        update_option( TPAD_CATALOG_VER_OPTION, $version, false );
        delete_option( TPAD_CATALOG_STALE_OPTION );

        tpad_catalog_register_with_simply_static();

        delete_transient( TPAD_CATALOG_LOCK_KEY );

        return array( 'rebuilt' => true, 'version' => $version, 'records' => count( $data['p'] ) );
    } catch ( Throwable $e ) {
        delete_transient( TPAD_CATALOG_LOCK_KEY );
        error_log( 'tpad_catalog_rebuild_index: ' . $e->getMessage() );
        return array( 'rebuilt' => false, 'reason' => 'exception', 'message' => $e->getMessage() );
    }
}

/* ── Rank scalar (server/JS default-order parity) ────────────────── */

/**
 * Single-scalar "Top Rated" rank. Storing this in postmeta lets a plain
 * WP_Query `orderby => meta_value_num DESC` (see inc/cpt-psychic.php's
 * tpad_psychic_posts_per_page(), the archive default when no ?sort= is on
 * the URL) reproduce catalog-filter.js's compareTopRated() exactly:
 *
 *   1. every advisor with >=1 review outranks every advisor with 0
 *      reviews, regardless of either one's rating value;
 *   2. among reviewed advisors, higher rating always wins;
 *   3. a rating tie among reviewed advisors is broken by review count
 *      (more reviews ranks higher).
 *
 * IMPORTANT: `$rating_rounded` must be the value already rounded to 1
 * decimal -- the same rounding used to build the `p` records below and
 * the same value catalog-filter.js reads from the JSON index -- NOT the
 * raw, high-precision `_rating` postmeta (observed with up to 15 decimal
 * digits in this dataset). Ranking on the raw value could separate two
 * records that the JSON index / JS engine treat as an exact tie (both
 * rounding to e.g. 5.0), breaking parity with the JS-computed order.
 *
 * Collision proof (dataset checked 2026-08-04: 12,977 published psychics,
 * max _total_ratings == 988,372, rating always a multiple of 0.1 in
 * [0.0, 5.0] by the time it reaches here):
 *   - Let rating_tenths = round(rating_rounded * 10), an integer 0..50.
 *   - TPAD_RANK_RATING_MULTIPLIER (1e7) exceeds the largest possible
 *     review count by ~10x. So for any two reviewed advisors with
 *     DIFFERENT rating_tenths (they differ by >=1), the rating term
 *     alone differs by >= 1e7, which is larger than the maximum possible
 *     review-count difference (988,372 < 1e7) -- rating always dominates
 *     review count, never the reverse. Same rating_tenths -> the rating
 *     terms cancel and review_count alone (a strictly increasing integer)
 *     breaks the tie, matching compareTopRated()'s secondary sort.
 *   - TPAD_RANK_REVIEWED_OFFSET (1e9) exceeds the largest possible
 *     zero-review scalar (rating_tenths=50 * 1e7 = 5e8) by 2x. So the
 *     SMALLEST possible reviewed-group scalar (rating_tenths=0,
 *     review_count=1 -> 1e9 + 1) is still larger than the LARGEST
 *     possible zero-review scalar (5e8) -- every reviewed advisor
 *     outranks every zero-review advisor, unconditionally.
 *
 * @param float $rating_rounded Rating already rounded to 1 decimal.
 * @param int   $n_ratings      Review count (`_total_ratings`).
 * @return int Rank; higher == "more Top Rated".
 */
function tpad_catalog_compute_rank( $rating_rounded, $n_ratings ) {
    $n_ratings     = max( 0, (int) $n_ratings );
    $has_reviews   = $n_ratings > 0 ? 1 : 0;
    $rating_tenths = (int) round( (float) $rating_rounded * 10 );

    return $has_reviews * TPAD_RANK_REVIEWED_OFFSET
         + $rating_tenths * TPAD_RANK_RATING_MULTIPLIER
         + $n_ratings;
}

/* ── Data assembly ─────────────────────────────────────────────── */

/**
 * Builds the full index payload with a fixed, small number of SQL
 * round-trips (base posts, one pivoted postmeta query, one taxonomy
 * relationship query) — never WP_Query/get_post_meta per post.
 *
 * Also keeps `_bpr_rank` postmeta (see tpad_catalog_compute_rank() above)
 * in sync with the JSON index in this SAME pass, from the SAME $rating /
 * $n_ratings values used to build each record -- so the stored meta and
 * the JSON index can never drift apart. This is both the ongoing
 * maintenance path (fires whenever the index is rebuilt after a psychic
 * is added/edited/removed) and the one-off backfill path for existing
 * posts (call tpad_catalog_rebuild_index(true) once via `wp eval` to
 * populate _bpr_rank for every already-published psychic).
 */
function tpad_catalog_build_index_data( $version ) {
    global $wpdb;

    $image_pattern = '#^' . preg_quote( TPAD_CATALOG_IMAGE_BASE, '#' ) . '(\d+)\.jpg$#';

    $empty = array(
        'v'  => $version,
        'ib' => TPAD_CATALOG_IMAGE_BASE,
        'pb' => TPAD_CATALOG_PERMALINK_BASE,
        'sp' => array(),
        'sk' => array(),
        'lg' => array(),
        'tl' => array(),
        'p'  => array(),
    );

    // 1) Base post rows.
    $posts = $wpdb->get_results(
        "SELECT ID, post_title, post_name FROM {$wpdb->posts}
         WHERE post_type = 'psychic' AND post_status = 'publish'
         ORDER BY ID ASC"
    );
    if ( empty( $posts ) ) {
        return $empty;
    }

    // 2) One pivoted postmeta pass (conditional aggregation), joined
    //    against posts directly so we never build a giant IN() list.
    $meta_rows = $wpdb->get_results(
        "SELECT pm.post_id,
                MAX(CASE WHEN pm.meta_key = '_rating'           THEN pm.meta_value END) AS rating,
                MAX(CASE WHEN pm.meta_key = '_total_ratings'    THEN pm.meta_value END) AS total_ratings,
                MAX(CASE WHEN pm.meta_key = '_total_readings'   THEN pm.meta_value END) AS total_readings,
                MAX(CASE WHEN pm.meta_key = '_price_per_minute' THEN pm.meta_value END) AS price,
                MAX(CASE WHEN pm.meta_key = '_availability'     THEN pm.meta_value END) AS availability,
                MAX(CASE WHEN pm.meta_key = '_years_experience' THEN pm.meta_value END) AS years,
                MAX(CASE WHEN pm.meta_key = '_photo_urls'       THEN pm.meta_value END) AS photo_urls,
                MAX(CASE WHEN pm.meta_key = '_languages'        THEN pm.meta_value END) AS languages,
                MAX(CASE WHEN pm.meta_key = '_tools_used'       THEN pm.meta_value END) AS tools_used
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type = 'psychic' AND p.post_status = 'publish'
           AND pm.meta_key IN ('_rating','_total_ratings','_total_readings','_price_per_minute','_availability','_years_experience','_photo_urls','_languages','_tools_used')
         GROUP BY pm.post_id"
    );
    $meta_by_id = array();
    foreach ( $meta_rows as $row ) {
        $meta_by_id[ (int) $row->post_id ] = $row;
    }

    // 3) One taxonomy relationship pass for both taxonomies at once.
    $term_rows = $wpdb->get_results(
        "SELECT tr.object_id, tt.taxonomy, t.term_id, t.slug, t.name
         FROM {$wpdb->term_relationships} tr
         INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
         INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
         INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
         WHERE tt.taxonomy IN ('psychic_specialty','psychic_skill')
           AND p.post_type = 'psychic' AND p.post_status = 'publish'"
    );

    $sp_index  = array(); // term_id => sequential idx in $sp_list
    $sk_index  = array();
    $sp_list   = array();
    $sk_list   = array();
    $sp_counts = array(); // idx => number of published psychics carrying this term
    $sk_counts = array();
    $post_sp   = array(); // post_id => [idx, ...]
    $post_sk   = array();

    foreach ( $term_rows as $row ) {
        $pid = (int) $row->object_id;
        $tid = (int) $row->term_id;
        if ( 'psychic_specialty' === $row->taxonomy ) {
            if ( ! isset( $sp_index[ $tid ] ) ) {
                $sp_index[ $tid ] = count( $sp_list );
                $sp_list[] = array( $row->slug, tpa_clean_term_name( $row->name ) );
            }
            $idx = $sp_index[ $tid ];
            $post_sp[ $pid ][] = $idx;
            $sp_counts[ $idx ] = isset( $sp_counts[ $idx ] ) ? $sp_counts[ $idx ] + 1 : 1;
        } else {
            if ( ! isset( $sk_index[ $tid ] ) ) {
                $sk_index[ $tid ] = count( $sk_list );
                $sk_list[] = array( $row->slug, tpa_clean_term_name( $row->name ) );
            }
            $idx = $sk_index[ $tid ];
            $post_sk[ $pid ][] = $idx;
            $sk_counts[ $idx ] = isset( $sk_counts[ $idx ] ) ? $sk_counts[ $idx ] + 1 : 1;
        }
    }

    // Append the published-post count as a 3rd tuple element -- [slug, name, count].
    // Drives the specialty/skill picker UX (fillMultiSelect in catalog-filter.js):
    // specialties below a minimum-post threshold are hidden, everything is
    // ordered by count, and the count is shown in the option label.
    foreach ( $sp_list as $idx => &$sp_entry ) {
        $sp_entry[] = isset( $sp_counts[ $idx ] ) ? $sp_counts[ $idx ] : 0;
    }
    unset( $sp_entry );
    foreach ( $sk_list as $idx => &$sk_entry ) {
        $sk_entry[] = isset( $sk_counts[ $idx ] ) ? $sk_counts[ $idx ] : 0;
    }
    unset( $sk_entry );

    // 4) Assemble records; languages/tools dictionaries built on the fly.
    $lg_index = array();
    $lg_list  = array();
    $tl_index = array();
    $tl_list  = array();
    $records  = array();

    foreach ( $posts as $post ) {
        $pid = (int) $post->ID;
        $m   = isset( $meta_by_id[ $pid ] ) ? $meta_by_id[ $pid ] : null;

        $rating     = $m && $m->rating !== null ? round( (float) $m->rating, 1 ) : 0.0;
        $n_ratings  = $m ? (int) $m->total_ratings : 0;
        $n_readings = $m ? (int) $m->total_readings : 0;
        $price      = $m && $m->price !== null ? round( (float) $m->price, 2 ) : 0.0;
        $online     = ( $m && 'online' === $m->availability ) ? 1 : 0;
        $years      = $m ? (int) $m->years : 0;

        // Keep the server-side default-sort meta in lockstep with the
        // exact $rating/$n_ratings values this same pass just used to
        // build the JSON record below -- see tpad_catalog_compute_rank().
        update_post_meta( $pid, '_bpr_rank', tpad_catalog_compute_rank( $rating, $n_ratings ) );

        // Image: numeric-under-jpg pattern -> int id; otherwise the raw
        // URL string; nothing usable -> 0.
        $img = 0;
        if ( $m && ! empty( $m->photo_urls ) ) {
            $photos = json_decode( $m->photo_urls, true );
            if ( is_array( $photos ) && ! empty( $photos ) && is_string( $photos[0] ) && '' !== $photos[0] ) {
                $first = $photos[0];
                if ( preg_match( $image_pattern, $first, $mm ) ) {
                    $img = (int) $mm[1];
                } else {
                    $img = $first;
                }
            }
        }

        $lg_ids = array();
        if ( $m && ! empty( $m->languages ) ) {
            $langs = json_decode( $m->languages, true );
            if ( is_array( $langs ) ) {
                foreach ( $langs as $lang ) {
                    if ( ! is_string( $lang ) || '' === $lang ) continue;
                    if ( ! isset( $lg_index[ $lang ] ) ) {
                        $lg_index[ $lang ] = count( $lg_list );
                        $lg_list[] = $lang;
                    }
                    $lg_ids[] = $lg_index[ $lang ];
                }
            }
        }

        $tl_ids = array();
        if ( $m && ! empty( $m->tools_used ) ) {
            $tools = json_decode( $m->tools_used, true );
            if ( is_array( $tools ) ) {
                foreach ( $tools as $tool ) {
                    if ( ! is_string( $tool ) || '' === $tool ) continue;
                    if ( ! isset( $tl_index[ $tool ] ) ) {
                        $tl_index[ $tool ] = count( $tl_list );
                        $tl_list[] = $tool;
                    }
                    $tl_ids[] = $tl_index[ $tool ];
                }
            }
        }

        $records[] = array(
            $post->post_name,
            // Raw, unfiltered DB title -- NOT get_the_title(), which runs
            // the post through wptexturize() and returns straight quotes/
            // dashes/ellipses already converted to literal HTML entity
            // text (e.g. "Mo & Angel Guides" -> "Mo &#038; Angel Guides",
            // "Romina's..." -> "Romina&#8217;s..."). This JSON is consumed
            // by catalog-filter.js as PLAIN TEXT data -- search does a
            // plain substring match against it, and cardHtml() runs the
            // value through its OWN escHtml() before inserting into
            // innerHTML. Feeding it an already-entity-encoded string
            // breaks BOTH: the search box can never match the apostrophe/
            // dash a visitor actually types, and escHtml() re-escapes the
            // literal "&" in "&#8217;" into "&amp;#8217;", so the card
            // visibly renders the raw entity text ("Romina&#8217;s...")
            // instead of an apostrophe. Verified live on site 42
            // (psychic-reviews-onlinecom): 150+ pilot titles and every
            // titled with a typographic character were affected network-
            // wide (byte-identical file on every ported site).
            $post->post_title,
            $rating,
            $n_ratings,
            $n_readings,
            $price,
            $online,
            $years,
            $img,
            isset( $post_sp[ $pid ] ) ? $post_sp[ $pid ] : array(),
            isset( $post_sk[ $pid ] ) ? $post_sk[ $pid ] : array(),
            $lg_ids,
            $tl_ids,
        );
    }

    return array(
        'v'  => $version,
        'ib' => TPAD_CATALOG_IMAGE_BASE,
        'pb' => TPAD_CATALOG_PERMALINK_BASE,
        'sp' => $sp_list,
        'sk' => $sk_list,
        'lg' => $lg_list,
        'tl' => $tl_list,
        'p'  => $records,
    );
}

/* ── Simply Static wiring ──────────────────────────────────────── */

/**
 * Defensively add the index JSON URL to Simply Static's extra-URLs list
 * so a static crawl always picks it up even though nothing on the page
 * links to it with a plain <a href>. Discovers the real option shape at
 * runtime instead of assuming one — Simply Static keeps all its settings
 * in a single serialized option (observed name: "simply-static"), and
 * `additional_urls` may be a newline-delimited string (its current form
 * here) or, in other versions, an array. Never let a shape surprise here
 * break the index rebuild.
 */
function tpad_catalog_register_with_simply_static() {
    global $wpdb;

    try {
        $row = $wpdb->get_row(
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_name LIKE 'simply-static%'
             ORDER BY LENGTH(option_value) DESC LIMIT 1"
        );
        if ( ! $row ) {
            return; // Simply Static not present/configured — nothing to do.
        }

        $settings = maybe_unserialize( $row->option_value );
        if ( ! is_array( $settings ) || ! array_key_exists( 'additional_urls', $settings ) ) {
            return; // Unexpected shape — don't guess.
        }

        $index_url = tpad_catalog_index_url();
        $current   = $settings['additional_urls'];
        $changed   = false;

        if ( is_array( $current ) ) {
            if ( ! in_array( $index_url, $current, true ) ) {
                $current[] = $index_url;
                $settings['additional_urls'] = $current;
                $changed = true;
            }
        } else {
            $str   = (string) $current;
            $lines = '' === trim( $str ) ? array() : preg_split( '/\r\n|\r|\n/', $str );
            $lines = array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
            if ( ! in_array( $index_url, $lines, true ) ) {
                $lines[] = $index_url;
                $settings['additional_urls'] = implode( "\n", $lines );
                $changed = true;
            }
        }

        if ( $changed ) {
            update_option( $row->option_name, $settings );
        }
    } catch ( Throwable $e ) {
        error_log( 'tpad_catalog_register_with_simply_static: ' . $e->getMessage() );
    }
}

/* ── <head> preload hint ──────────────────────────────────────── */

function tpad_catalog_preload_link() {
    if ( is_admin() ) {
        return;
    }
    if ( ! ( is_post_type_archive( 'psychic' ) || is_tax( 'psychic_specialty' ) || is_tax( 'psychic_skill' ) ) ) {
        return;
    }
    printf(
        '<link rel="preload" as="fetch" crossorigin href="%s">' . "\n",
        esc_url( tpad_catalog_index_url() )
    );
}
add_action( 'wp_head', 'tpad_catalog_preload_link' );
