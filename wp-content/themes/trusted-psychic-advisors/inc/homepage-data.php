<?php
/**
 * Homepage data helpers, run against the real `psychic` catalog (12,900+
 * published advisor profiles). Nothing here invents a figure: every number
 * is a straight count or a straight read of postmeta/taxonomy data written
 * by the psychic-import pipeline and inc/catalog-index.php.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Homepage aggregate counts: total published advisor profiles, total
 * specialty terms in use, and total distinct third-party sites the
 * catalog was aggregated from. Cached for 12 hours -- the "sources" figure
 * in particular has to scan every profile's _source_sites list once, and
 * this homepage sits in front of a 12,900+ row catalog and gets statically
 * exported, so it must never run that on a live request.
 */
function tpa_homepage_stats() {
	$cached = get_transient( 'tpa_homepage_stats' );
	if ( is_array( $cached ) && isset( $cached['advisors'], $cached['specialties'], $cached['sources'] ) ) {
		return $cached;
	}

	global $wpdb;

	$advisors = (int) wp_count_posts( 'psychic' )->publish;

	$specialty_count = (int) wp_count_terms( array(
		'taxonomy'   => 'psychic_specialty',
		'hide_empty' => true,
	) );

	// _source_sites is a small JSON array per profile (one entry per network
	// it was gathered from, e.g. ["bitwine.com"]). Read the distinct raw
	// values once -- there are only a few dozen distinct combinations across
	// the whole catalog -- and flatten them into a set of real domains.
	$raw_lists = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_source_sites'
		   AND p.post_type = 'psychic' AND p.post_status = 'publish'"
	);

	$sources = array();
	foreach ( (array) $raw_lists as $raw ) {
		$list = json_decode( $raw, true );
		if ( is_array( $list ) ) {
			foreach ( $list as $site ) {
				$site = trim( (string) $site );
				if ( $site !== '' ) {
					$sources[ $site ] = true;
				}
			}
		}
	}

	$stats = array(
		'advisors'    => $advisors,
		'specialties' => $specialty_count,
		'sources'     => count( $sources ),
	);

	set_transient( 'tpa_homepage_stats', $stats, 12 * HOUR_IN_SECONDS );

	return $stats;
}

/**
 * Top advisor IDs for the homepage hero card and comparison table, ranked
 * by the catalog's own _bpr_rank score (see tpad_catalog_compute_rank() in
 * inc/catalog-index.php: profiles with at least one published review
 * always outrank ones with none, then higher published ratings win, then
 * higher review counts win). Falls back to ordering by the raw published
 * rating for the rare profile that has no rank meta yet.
 *
 * Bounded and cheap by design: ids only, no found-rows count, cached for
 * 12 hours since the homepage does not need this ranking to update more
 * often than that.
 */
function tpa_top_psychics( $limit = 5 ) {
	$limit = max( 1, (int) $limit );
	$key   = 'tpa_top_psychics_' . $limit;

	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	// Ordered on _bpr_rank alone: every published profile carries that meta
	// (written by tpad_catalog_rebuild_index), and a named-clause OR meta_query
	// does not order reliably, so keep this a single indexed meta sort with a
	// stable ID tiebreak.
	$query = new WP_Query( array(
		'post_type'              => 'psychic',
		'post_status'            => 'publish',
		'posts_per_page'         => $limit,
		'no_found_rows'          => true,
		'fields'                 => 'ids',
		'update_post_term_cache' => false,
		'meta_key'               => '_bpr_rank',
		'orderby'                => array( 'meta_value_num' => 'DESC', 'ID' => 'ASC' ),
	) );

	$ids = $query->posts;

	// Rare fallback: no ranked profiles yet (index never built). Order by the
	// raw published rating instead so the homepage still shows real advisors.
	if ( empty( $ids ) ) {
		$fallback = new WP_Query( array(
			'post_type'              => 'psychic',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'no_found_rows'          => true,
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
			'meta_key'               => '_rating',
			'orderby'                => array( 'meta_value_num' => 'DESC', 'ID' => 'ASC' ),
		) );
		$ids = $fallback->posts;
	}


	set_transient( $key, $ids, 12 * HOUR_IN_SECONDS );

	return $ids;
}

/**
 * Top psychic_specialty terms for the homepage "reading types" section.
 *
 * psychic_specialty is 3,800+ terms of scraped free text: ALL-CAPS mixed
 * with Title Case, a few wrapped in literal quote marks, several generic
 * scraped column-labels that are not reading types at all ("Reading
 * style", "PSYCHIC"), and near-duplicate concepts ("LOVE" / "LOVE &
 * RELATIONSHIPS" / "RELATIONSHIPS"). This pulls well past $number so that,
 * after tpa_is_reading_type_noise() drops the column-labels and the
 * concept-key de-dupe below collapses near-duplicates (keeping whichever
 * survivor has the highest count, since candidates already arrive ordered
 * by count DESC), $number real, distinct reading types remain. Cached for
 * 12 hours, same pattern as tpa_top_psychics().
 */
function tpa_top_specialty_terms( $number = 8 ) {
	$number = max( 1, (int) $number );
	$key    = 'tpa_top_specialty_terms_' . $number;

	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$candidates = get_terms( array(
		'taxonomy'   => 'psychic_specialty',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 40,
	) );

	$terms = array();

	if ( ! is_wp_error( $candidates ) ) {
		$seen_keys = array();

		foreach ( $candidates as $term ) {
			$label = tpa_clean_term_name( $term->name );

			if ( '' === $label || tpa_is_reading_type_noise( $label ) ) {
				continue;
			}

			$concept_key = tpa_reading_type_concept_key( $label );

			if ( isset( $seen_keys[ $concept_key ] ) ) {
				continue; // A higher-count term already claimed this concept.
			}

			$seen_keys[ $concept_key ] = true;
			$terms[]                  = $term;

			if ( count( $terms ) >= $number ) {
				break;
			}
		}
	}

	set_transient( $key, $terms, 12 * HOUR_IN_SECONDS );

	return $terms;
}

/** Top psychic_skill terms for the homepage skills strip. */
function tpa_top_skill_terms( $number = 6 ) {
	$terms = get_terms( array(
		'taxonomy'   => 'psychic_skill',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => $number,
	) );

	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * First specialty term for one advisor, cleaned for display, or '' when
 * the profile carries no specialty term (about 39% of the catalog).
 */
function tpa_first_specialty_name( $post_id ) {
	$terms = get_the_terms( $post_id, 'psychic_specialty' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return tpa_clean_term_name( $terms[0]->name );
}

/**
 * Normalizes a scraped taxonomy term name for display: decodes HTML
 * entities (so `&amp;` never reaches the page as literal text), strips
 * stray straight/curly quote marks (e.g. `"Tarot"`), collapses whitespace,
 * and rewrites an ALL-CAPS scraped label ("DREAM INTERPRETATION") into
 * Title Case. A term that already mixes upper and lower case (e.g.
 * "Destiny & Life Path") is left exactly as scraped -- only the
 * shouting-caps case gets rewritten. Never renames the term itself or
 * changes what a link to it points at, only the text shown for it. Used
 * for the "reading types" grid, the psychic_skill pills, and the
 * specialty subtext under each advisor name in the comparison rows.
 */
function tpa_clean_term_name( $name ) {
	$name = html_entity_decode( (string) $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$name = str_replace( array( '"', "'", "\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x98", "\xE2\x80\x99" ), '', $name );
	$name = trim( preg_replace( '/\s+/', ' ', $name ) );

	if ( '' !== $name && tpa_label_is_all_caps( $name ) ) {
		$words = explode( ' ', $name );
		foreach ( $words as $i => $word ) {
			$words[ $i ] = tpa_title_case_word( $word, 0 === $i );
		}
		$name = implode( ' ', $words );
	}

	return $name;
}

/** True when $s contains at least one letter and no lowercase letters -- i.e. shouting caps. */
function tpa_label_is_all_caps( $s ) {
	return (bool) preg_match( '/\p{L}/u', $s ) && ! preg_match( '/\p{Ll}/u', $s );
}

/**
 * Title-cases one word of an ALL-CAPS label. `&` passes through untouched;
 * a short joining word ("and", "of", "the"...) stays lowercase unless it
 * opens the label, so a converted label reads "Money & Finance" rather
 * than "Money & Finance" mangled into "Money And Finance"-style noise.
 */
function tpa_title_case_word( $word, $is_first ) {
	if ( '&' === $word ) {
		return '&';
	}

	$lower = mb_strtolower( $word, 'UTF-8' );

	static $joiners = array( 'and', 'or', 'of', 'the', 'in', 'for', 'with', 'to', 'a', 'an', 'on', 'at', 'by' );

	if ( ! $is_first && in_array( $lower, $joiners, true ) ) {
		return $lower;
	}

	return mb_strtoupper( mb_substr( $lower, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $lower, 1, null, 'UTF-8' );
}

/**
 * Scraped psychic_specialty terms include a handful of generic
 * column-labels lifted from the source sites' own tables -- not real
 * reading types -- that would otherwise clutter the homepage "reading
 * types" grid. Matched case-insensitively against the NORMALIZED label
 * (post tpa_clean_term_name), so this list stays untouched by case
 * variants like "Psychic" vs "PSYCHIC".
 */
function tpa_is_reading_type_noise( $normalized_label ) {
	static $noise = array(
		'reading style',    // a source site's table-column header, not a specialty
		'psychic',          // describes every advisor in the catalog, not a reading type
		'psychic readings', // ditto
		'guidance',         // too generic to name an actual reading type
	);

	return in_array( mb_strtolower( $normalized_label, 'UTF-8' ), $noise, true );
}

/**
 * Singularizes one English word with a small set of common suffix rules.
 * Good enough for the scraped specialty vocabulary; not a full stemmer.
 */
function tpa_singularize_word( $word ) {
	$lower = mb_strtolower( $word, 'UTF-8' );
	$len   = mb_strlen( $lower, 'UTF-8' );

	if ( $len > 3 && 1 === preg_match( '/ies$/', $lower ) ) {
		return mb_substr( $lower, 0, -3, 'UTF-8' ) . 'y';
	}
	if ( 1 === preg_match( '/(ses|xes|zes|ches|shes)$/', $lower ) ) {
		return mb_substr( $lower, 0, -2, 'UTF-8' );
	}
	if ( $len > 3 && 's' === mb_substr( $lower, -1, 1, 'UTF-8' ) && 'ss' !== mb_substr( $lower, -2, 2, 'UTF-8' ) ) {
		return mb_substr( $lower, 0, -1, 'UTF-8' );
	}

	return $lower;
}

/**
 * Deterministic de-dupe key for the "reading types" grid: the singular,
 * lowercased form of a normalized label's first word. This is a general
 * rule, not a hand-written synonym table, and it catches both shapes seen
 * in the real data -- a plural/singular pair ("RELATIONSHIP" vs
 * "RELATIONSHIPS") and a short term that is just the leading noun of a
 * longer one ("LOVE" vs "LOVE & RELATIONSHIPS").
 */
function tpa_reading_type_concept_key( $normalized_label ) {
	$words = preg_split( '/[^\p{L}\p{N}]+/u', $normalized_label, -1, PREG_SPLIT_NO_EMPTY );

	if ( empty( $words ) ) {
		return mb_strtolower( $normalized_label, 'UTF-8' );
	}

	return tpa_singularize_word( $words[0] );
}

/**
 * Provenance line for one advisor profile: which public source page its
 * data was gathered from. Mirrors the network-CPT tpa_source_line() but
 * reads the psychic CPT's own _source_sites/_source_urls meta instead of
 * a tpa_source_url/tpa_source_date field that psychic posts don't have.
 */
function tpa_psychic_source_line( $post_id ) {
	$sites = json_decode( get_post_meta( $post_id, '_source_sites', true ) ?: '[]', true );
	$urls  = json_decode( get_post_meta( $post_id, '_source_urls', true ) ?: '[]', true );

	$site = ( is_array( $sites ) && ! empty( $sites[0] ) ) ? trim( (string) $sites[0] ) : '';
	$url  = ( is_array( $urls ) && ! empty( $urls[0] ) ) ? trim( (string) $urls[0] ) : '';

	if ( ! $site && ! $url ) {
		return '';
	}

	$label = $site
		? sprintf(
			/* translators: %s: source website domain */
			__( 'Profile data gathered from %s', 'tpa' ),
			esc_html( $site )
		)
		: __( 'Profile data gathered from a public source page', 'tpa' );

	return $url ? '<a href="' . esc_url( $url ) . '" rel="nofollow noopener">' . $label . '</a>' : $label;
}

/**
 * Footer column links for a menu location that has no menu assigned yet.
 * Everything here is a real archive URL built from taxonomy data that
 * already exists, so an unassigned menu leaves a useful column rather
 * than an empty one. Labels run through the same normalizer the homepage
 * uses, so scraped ALL-CAPS term names never reach the footer.
 */
function tpa_footer_fallback_links( $location ) {
	$links = array();

	if ( 'footer_networks' === $location ) {
		$archive = get_post_type_archive_link( 'psychic' );
		if ( $archive ) {
			$links[] = array( $archive, __( 'All advisor profiles', 'tpa' ) );
		}
		foreach ( tpa_top_specialty_terms( 4 ) as $term ) {
			$url = get_term_link( $term );
			if ( ! is_wp_error( $url ) ) {
				$links[] = array( $url, tpa_clean_term_name( $term->name ) );
			}
		}
	} elseif ( 'footer_types' === $location ) {
		foreach ( tpa_top_skill_terms( 5 ) as $term ) {
			$url = get_term_link( $term );
			if ( ! is_wp_error( $url ) ) {
				$links[] = array( $url, tpa_clean_term_name( $term->name ) );
			}
		}
	}

	if ( ! $links ) {
		return;
	}

	echo '<ul>';
	foreach ( $links as $link ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $link[0] ), esc_html( $link[1] ) );
	}
	echo '</ul>';
}
