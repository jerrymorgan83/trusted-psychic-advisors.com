<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Custom Post Type: psychic ─────────────────────────────────── */
function tpad_register_psychic_cpt() {
    register_post_type( 'psychic', [
        'labels' => [
            'name'          => 'Psychics',
            'singular_name' => 'Psychic',
            'add_new_item'  => 'Add New Psychic',
            'edit_item'     => 'Edit Psychic',
            'view_item'     => 'View Psychic',
            'search_items'  => 'Search Psychics',
            'not_found'     => 'No psychics found',
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => [ 'slug' => 'psychics', 'with_front' => false ],
        'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-visibility',
    ]);

    register_taxonomy( 'psychic_specialty', 'psychic', [
        'labels' => [
            'name'          => 'Specialties',
            'singular_name' => 'Specialty',
            'search_items'  => 'Search Specialties',
            'all_items'     => 'All Specialties',
        ],
        'public'       => true,
        'hierarchical' => false,
        'rewrite'      => [ 'slug' => 'specialty', 'with_front' => false ],
        'show_in_rest' => true,
    ]);

    register_taxonomy( 'psychic_skill', 'psychic', [
        'labels' => [
            'name'          => 'Skills',
            'singular_name' => 'Skill',
            'search_items'  => 'Search Skills',
            'all_items'     => 'All Skills',
        ],
        'public'       => true,
        'hierarchical' => false,
        'rewrite'      => [ 'slug' => 'skill', 'with_front' => false ],
        'show_in_rest' => true,
    ]);
}
add_action( 'init', 'tpad_register_psychic_cpt' );

/* ── Meta fields ───────────────────────────────────────────────── */
function tpad_register_psychic_meta() {
    $fields = [
        '_rating'              => 'number',
        '_total_ratings'       => 'integer',
        '_total_readings'      => 'integer',
        '_price_per_minute'    => 'number',
        '_intro_offer'         => 'string',
        '_availability'        => 'string',
        '_source_sites'        => 'string',
        '_source_urls'         => 'string',
        '_languages'           => 'string',
        '_tools_used'          => 'string',
        '_communication_style' => 'string',
        '_years_experience'    => 'integer',
        '_member_since'        => 'string',
        '_reviews_json'        => 'string',
        '_photo_urls'          => 'string',
    ];
    foreach ( $fields as $key => $type ) {
        register_post_meta( 'psychic', $key, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ]);
    }
}
add_action( 'init', 'tpad_register_psychic_meta' );

/* ── Posts per page for psychic archives ───────────────────────── */
function tpad_psychic_posts_per_page( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;
    if ( $query->is_post_type_archive( 'psychic' ) || $query->is_tax( 'psychic_specialty' ) || $query->is_tax( 'psychic_skill' ) ) {
        $query->set( 'posts_per_page', 24 );
        /* === BEGIN catalog-filter default-order patch (tpad) — wp-net-control rollout === */
        // No ?sort= at all -- order by the precomputed _bpr_rank meta
        // (inc/catalog-index.php), matching catalog-filter.js's default
        // "Top Rated" order instead of WP's post_date DESC default.
        // See best-psychic-ratingsnet/bpr-theme's cpt-psychic.php for the
        // full reasoning (ported via wp-net-control rollout).
        if ( ! isset( $_GET['sort'] ) ) {
            $query->set( 'meta_query', array(
                'relation'    => 'OR',
                'tpad_rank' => array(
                    'key'     => '_bpr_rank',
                    'compare' => 'EXISTS',
                    'type'    => 'NUMERIC',
                ),
                array(
                    'key'     => '_bpr_rank',
                    'compare' => 'NOT EXISTS',
                ),
            ) );
            $query->set( 'orderby', array( 'tpad_rank' => 'DESC', 'ID' => 'ASC' ) );
            return;
        }
        /* === END catalog-filter default-order patch (tpad) — wp-net-control rollout === */

        $orderby = sanitize_text_field( $_GET['sort'] );
        switch ( $orderby ) {
            case 'price_low':
                $query->set( 'meta_key', '_price_per_minute' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'ASC' );
                break;
            case 'price_high':
                $query->set( 'meta_key', '_price_per_minute' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'DESC' );
                break;
            case 'reviews':
                $query->set( 'meta_key', '_total_ratings' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'DESC' );
                break;
            case 'name':
                $query->set( 'orderby', 'title' );
                $query->set( 'order', 'ASC' );
                break;
            default: // rating
                $query->set( 'meta_key', '_rating' );
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'order', 'DESC' );
                break;
        }
    }
}
add_action( 'pre_get_posts', 'tpad_psychic_posts_per_page' );
