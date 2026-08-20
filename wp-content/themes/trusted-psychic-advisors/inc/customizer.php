<?php
/**
 * Customizer settings for the copy that carries a compliance meaning.
 * Defaults repeat the approved wording: no testing claims, no invented
 * figures -- everything traces back to what an advisor's own public
 * profile page states.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Single source of truth for the copy defaults. tpa_copy() reads this, so a
 * template that passes a stale inline fallback can never override the
 * approved wording.
 */
function tpa_copy_defaults() {
	return array(

		'tpa_banner_text'   => array( __( 'Top banner', 'tpa' ), 'Comparison built from published ratings, published rates and public advisor profiles.' ),
		'tpa_banner_link'   => array( __( 'Top banner link label', 'tpa' ), 'See the table' ),
		'tpa_banner_url'    => array( __( 'Top banner link URL', 'tpa' ), '#comparison' ),
		'tpa_tagline'       => array( __( 'Header tagline', 'tpa' ), 'A directory of published advisor profiles' ),
		'tpa_hero_kicker'   => array( __( 'Hero kicker', 'tpa' ), 'Aggregated from public advisor profiles' ),
		'tpa_hero_title'    => array( __( 'Hero title', 'tpa' ), 'Compare psychic advisors on what their profiles show.' ),
		'tpa_hero_lede'     => array( __( 'Hero paragraph', 'tpa' ), 'Ratings, review volume, per-minute rates and reading specialties, pulled from thousands of public advisor profiles and set side by side. Every advisor links back to the profile the figures came from.' ),
		'tpa_disclosure'    => array( __( 'Advertiser disclosure', 'tpa' ), "Figures come from each advisor's published profile. We may earn a commission. It does not change the listing order." ),
		'tpa_legal'         => array( __( 'Footer legal', 'tpa' ), "For entertainment. Readings are not a substitute for medical, legal or financial advice. Users must be 18 or over. Figures on this site repeat what each advisor's published profile states and may be out of date. Some links earn us a commission at no cost to you." ),
	);
}

function tpa_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'tpa_copy', array(
		'title'       => __( 'Directory copy', 'tpa' ),
		'priority'    => 30,
		'description' => __( "Keep claims limited to what each advisor's published profile states.", 'tpa' ),
	) );

	$fields = tpa_copy_defaults();


	foreach ( $fields as $key => $field ) {
		$wp_customize->add_setting( $key, array(
			'default'           => $field[1],
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'refresh',
		) );
		$wp_customize->add_control( $key, array(
			'label'   => $field[0],
			'section' => 'tpa_copy',
			'type'    => 'textarea',
		) );
	}

	$wp_customize->add_setting( 'tpa_show_banner', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
	$wp_customize->add_control( 'tpa_show_banner', array( 'label' => __( 'Show top banner', 'tpa' ), 'section' => 'tpa_copy', 'type' => 'checkbox' ) );

	$wp_customize->add_setting( 'tpa_show_strip', array( 'default' => true, 'sanitize_callback' => 'wp_validate_boolean' ) );
	$wp_customize->add_control( 'tpa_show_strip', array( 'label' => __( 'Show network logo strip', 'tpa' ), 'section' => 'tpa_copy', 'type' => 'checkbox' ) );
}
add_action( 'customize_register', 'tpa_customize_register' );

function tpa_copy( $key, $fallback = '' ) {
	$defaults = tpa_copy_defaults();
	if ( isset( $defaults[ $key ][1] ) && $defaults[ $key ][1] !== '' ) {
		$fallback = $defaults[ $key ][1];
	}
	$value = get_theme_mod( $key, $fallback );
	return $value !== '' ? $value : $fallback;
}
