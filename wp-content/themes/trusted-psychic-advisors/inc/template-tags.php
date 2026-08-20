<?php
if ( ! defined( 'ABSPATH' ) ) exit;



function tpad_icon( $name ) {
    $icons = [
        'star'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01z"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'check'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>',
        'users'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'clock'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>',
        'bar'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="12" width="4" height="9" rx="1"/><rect x="10" y="7" width="4" height="14" rx="1"/><rect x="17" y="3" width="4" height="18" rx="1"/></svg>',
        'send'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>',
        'plus'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'menu'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
        'x'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        'compass' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function tpad_stars( $count = 5, $max = 5 ) {
    $out = '';
    for ( $i = 0; $i < $max; $i++ ) {
        $fill = $i < $count ? 'var(--c-star)' : 'var(--c-border)';
        $out .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="' . $fill . '"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14l-5-4.87 6.91-1.01z"/></svg>';
    }
    return $out;
}

// ── Catalog helpers ──
function tpad_fallback_menu() {
    echo '<ul>';
    printf( '<li><a href="%s">Home</a></li>', esc_url( home_url('/') ) );
    printf( '<li><a href="%s">Psychics</a></li>', esc_url( home_url('/psychics/') ) );

    // Top specialties (filter out junk scraped terms)
    $specialties = get_terms([ 'taxonomy' => 'psychic_specialty', 'hide_empty' => true, 'number' => 20, 'orderby' => 'count', 'order' => 'DESC' ]);
    $shown = 0;
    if ( $specialties && ! is_wp_error( $specialties ) ) {
        foreach ( $specialties as $term ) {
            if ( $shown >= 5 ) break;
            // Skip junk terms
            if ( mb_strlen( $term->name ) > 30 || strpos( $term->name, '&nbsp' ) !== false || strpos( $term->name, "\xC2\xA0" ) !== false || strpos( $term->name, '›' ) !== false ) continue;
            printf( '<li><a href="%s">%s</a></li>', esc_url( get_term_link( $term ) ), esc_html( tpa_clean_term_name( $term->name ) ) );
            $shown++;
        }
    }

    printf( '<li><a href="%s">Blog</a></li>', esc_url( home_url('/blog/') ) );
    echo '</ul>';
}

function tpad_stars_precise( $rating, $max = 5 ) {
    $h = '<span class="stars-precise">';
    for ( $i = 1; $i <= $max; $i++ ) {
        if ( $i <= floor($rating) ) {
            $h .= '<svg class="star star--full" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        } elseif ( $i - $rating < 1 && $i - $rating > 0 ) {
            $pct = round( ($rating - floor($rating)) * 100 );
            $h .= '<svg class="star star--partial" viewBox="0 0 20 20"><defs><linearGradient id="half'.$i.'"><stop offset="'.$pct.'%" stop-color="var(--clr-accent)"/><stop offset="'.$pct.'%" stop-color="var(--clr-border)"/></linearGradient></defs><path fill="url(#half'.$i.')" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        } else {
            $h .= '<svg class="star star--empty" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        }
    }
    $h .= '</span>';
    return $h;
}

function tpad_rating_badge( $rating ) {
    if ( ! $rating ) return '';
    $cls = $rating >= 4.5 ? 'badge--gold' : ( $rating >= 4.0 ? 'badge--silver' : 'badge--bronze' );
    return '<span class="rating-badge '.$cls.'">'.number_format($rating,1).'</span>';
}

function tpad_price_tag( $price ) {
    if ( ! $price ) return '<span class="price-tag price-tag--na">N/A</span>';
    return '<span class="price-tag">$'.number_format($price,2).'/min</span>';
}

function tpad_availability_dot( $status ) {
    $cls = $status === 'online' ? 'avail--online' : ( $status === 'busy' ? 'avail--busy' : 'avail--offline' );
    $label = ucfirst( $status ?: 'unknown' );
    return '<span class="avail-dot '.$cls.'"><span class="avail-dot__circle"></span> '.$label.'</span>';
}

function tpad_breadcrumbs() {
    $h = '<nav class="breadcrumbs"><a href="'.home_url('/').'">Home</a>';
    if ( is_post_type_archive('psychic') ) {
        $h .= ' <span>/</span> <span>Psychics</span>';
    } elseif ( is_tax('psychic_specialty') || is_tax('psychic_skill') ) {
        $term = get_queried_object();
        $tax = is_tax('psychic_specialty') ? 'Specialties' : 'Skills';
        $h .= ' <span>/</span> <a href="'.get_post_type_archive_link('psychic').'">Psychics</a>';
        $h .= ' <span>/</span> <span>'.esc_html( tpa_clean_term_name( $term->name ) ).'</span>';
    } elseif ( is_singular('psychic') ) {
        $h .= ' <span>/</span> <a href="'.get_post_type_archive_link('psychic').'">Psychics</a>';
        $specs = get_the_terms( get_the_ID(), 'psychic_specialty' );
        if ( $specs && ! is_wp_error($specs) ) {
            $h .= ' <span>/</span> <a href="'.get_term_link($specs[0]).'">'.esc_html( tpa_clean_term_name( $specs[0]->name ) ).'</a>';
        }
        $h .= ' <span>/</span> <span>'.get_the_title().'</span>';
    }
    $h .= '</nav>';
    return $h;
}

/**
 * Source review bodies arrive with the scraper's own chrome glued to the
 * front of the text, e.g. "Helpful0User248293613 days ago . ChatAlways
 * amazing!". The digits of the user id and of the "days ago" figure run
 * together, so neither can be recovered reliably. Strip the whole prefix
 * and keep the contact channel, which is unambiguous, plus the body.
 *
 * @return array{channel:string,text:string}
 */
function tpad_parse_scraped_review( $text ) {
	$text    = trim( (string) $text );
	$channel = '';

	if ( preg_match( '/^Helpful\d+User\d+\s*days?\s*ago\s*[·.\-]?\s*(Chat|Call|Video|Phone|Message)?/iu', $text, $m ) ) {
		$channel = isset( $m[1] ) ? ucfirst( strtolower( $m[1] ) ) : '';
		$text    = trim( substr( $text, strlen( $m[0] ) ) );
	}

	return array( 'channel' => $channel, 'text' => $text );
}
