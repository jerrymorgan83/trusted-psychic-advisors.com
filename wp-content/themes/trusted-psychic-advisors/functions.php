<?php
/**
 * Trusted Psychic Advisors theme setup.
 * Every directory field here is a value the network publishes itself.
 * Nothing in this theme stores a firsthand test result.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'TPA_VERSION', '1.4.0' );

function tpa_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array(
		'primary'         => __( 'Primary', 'tpa' ),
		'footer_networks' => __( 'Footer: directory', 'tpa' ),
		'footer_types'    => __( 'Footer: reading types', 'tpa' ),
		'footer_about'    => __( 'Footer: about', 'tpa' ),
		'footer'          => __( 'Footer Menu (legacy)', 'tpa' ),
	) );
}
add_action( 'after_setup_theme', 'tpa_setup' );

/**
 * Front-end asset stack.
 *
 * Load order matters: the ported psychic-catalog design (tokens.css,
 * components.css, catalog.css -- carried over from the old tpad-theme)
 * must lose the cascade to this theme's own style.css on any selector
 * collision. Rather than relying on enqueue call order (fragile -- any
 * later re-ordering of these calls would silently flip the winner),
 * tpa-style explicitly DEPENDS on the ported handles, which forces
 * WordPress to print it after them in <head> regardless of the order
 * these wp_enqueue_style() calls appear in below.
 */
function tpa_assets() {
	wp_enqueue_style( 'tpa-fonts', 'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,300..700;1,8..60,400&display=swap', array(), null );

	// Ported catalog design tokens/components -- must print BEFORE tpa-style.
	wp_enqueue_style( 'tpad-tokens', get_template_directory_uri() . '/assets/css/tokens.css', array(), TPA_VERSION );
	wp_enqueue_style( 'tpad-components', get_template_directory_uri() . '/assets/css/components.css', array( 'tpad-tokens' ), TPA_VERSION );
	wp_enqueue_style( 'tpad-catalog', get_template_directory_uri() . '/assets/css/catalog.css', array( 'tpad-tokens' ), TPA_VERSION );

	// New design system, wins the cascade: depends on the ported stack
	// above so it is always printed last in <head>.
	wp_enqueue_style( 'tpa-style', get_stylesheet_uri(), array( 'tpa-fonts', 'tpad-tokens', 'tpad-components', 'tpad-catalog' ), TPA_VERSION );

	wp_enqueue_script( 'tpa-nav', get_template_directory_uri() . '/assets/js/nav.js', array(), TPA_VERSION, true );

	// Ported from tpad-theme: global mobile-menu/sticky-header/FAQ/anchor
	// helpers. Its selectors target old-theme ids that don't exist in this
	// theme's header/footer, so every block inside is null-guarded and it
	// is safe to enqueue globally.
	wp_enqueue_script( 'tpad-main', get_template_directory_uri() . '/assets/js/main.js', array(), TPA_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'tpa_assets' );

/**
 * Catalog-only sort/filter assets. Gated to the exact three views that use
 * the toolbar (template-parts/catalog-toolbar.php), versioned by filemtime
 * so an edit to either file busts cache immediately without a manual
 * TPA_VERSION bump.
 */
function tpad_catalog_filter_enqueue() {
	if ( is_post_type_archive( 'psychic' ) || is_tax( array( 'psychic_specialty', 'psychic_skill' ) ) ) {
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		$css = $dir . '/assets/css/catalog-filter.css';
		$js  = $dir . '/assets/js/catalog-filter.js';
		wp_enqueue_style( 'tpad-catalog-filter', $uri . '/assets/css/catalog-filter.css', array( 'tpad-tokens' ), file_exists( $css ) ? filemtime( $css ) : '1.0.0' );
		wp_enqueue_script( 'tpad-catalog-filter', $uri . '/assets/js/catalog-filter.js', array(), file_exists( $js ) ? filemtime( $js ) : '1.0.0', true );
	}
}
add_action( 'wp_enqueue_scripts', 'tpad_catalog_filter_enqueue', 20 );

require_once get_template_directory() . '/inc/network-cpt.php';
require_once get_template_directory() . '/inc/customizer.php';

/* === Ported from tpad-theme: psychic directory (CPT, catalog index, reviews) === */
require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/cpt-psychic.php';
require_once get_template_directory() . '/inc/reviews.php';
require_once get_template_directory() . '/inc/catalog-index.php';

/**
 * Homepage data: real advisor-count/specialty-count/source-count stats and
 * the top-ranked-advisor queries that drive the hero card and the
 * comparison table on front-page.php. See inc/homepage-data.php.
 */
require_once get_template_directory() . '/inc/homepage-data.php';

/**
 * Strips scraped `window.<var>=...` blobs and stray <script> tags out of
 * imported psychic-post content. Ported verbatim from tpad-theme.
 */
function tpad_clean_psychic_content( $content ) {
	if ( get_post_type() !== 'psychic' ) return $content;
	$content = preg_replace( '/window\.\w+\s*=\s*["{].*?["}];?\s*/s', '', $content );
	$content = preg_replace( '/<script[^>]*>.*?<\/script>/si', '', $content );
	return trim( $content );
}
add_filter( 'the_content', 'tpad_clean_psychic_content', 5 );
/* === End ported psychic directory wiring === */

/** Escape helper for a published field, with an obvious placeholder fallback. */
function tpa_field( $post_id, $key, $placeholder = '' ) {
	$value = get_post_meta( $post_id, $key, true );
	return $value !== '' ? $value : $placeholder;
}

/** Source attribution line: link plus the date the source page was read. */
function tpa_source_line( $post_id ) {
	$url  = get_post_meta( $post_id, 'tpa_source_url', true );
	$date = get_post_meta( $post_id, 'tpa_source_date', true );
	if ( ! $url && ! $date ) { return ''; }
	$label = $date ? sprintf( __( 'Source page read %s', 'tpa' ), esc_html( $date ) ) : __( 'Source page', 'tpa' );
	return $url ? '<a href="' . esc_url( $url ) . '" rel="nofollow noopener">' . $label . '</a>' : $label;
}
