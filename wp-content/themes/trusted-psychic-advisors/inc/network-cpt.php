<?php
/**
 * Network custom post type and its published-terms meta box.
 * Field names state their own provenance: each holds what the network publishes.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function tpa_register_network_cpt() {
	register_post_type( 'tpa_network', array(
		'labels' => array(
			'name'          => __( 'Networks', 'tpa' ),
			'singular_name' => __( 'Network', 'tpa' ),
			'add_new_item'  => __( 'Add network', 'tpa' ),
			'edit_item'     => __( 'Edit network', 'tpa' ),
		),
		'public'       => true,
		'menu_icon'    => 'dashicons-list-view',
		'hierarchical' => false,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'excerpt' ),
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'networks' ),
		'show_in_rest' => true,
	) );

	register_taxonomy( 'tpa_reading_type', 'tpa_network', array(
		'labels' => array(
			'name'          => __( 'Reading types', 'tpa' ),
			'singular_name' => __( 'Reading type', 'tpa' ),
		),
		'public'       => true,
		'hierarchical' => true,
		'rewrite'      => array( 'slug' => 'reading-type' ),
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'tpa_register_network_cpt' );

function tpa_network_fields() {
	return array(
		'tpa_published_rate'    => array( __( 'Published rate per minute', 'tpa' ), 'published rate per minute' ),
		'tpa_published_offer'   => array( __( 'Published intro offer', 'tpa' ), 'published intro offer' ),
		'tpa_refund_policy'     => array( __( 'Stated refund policy', 'tpa' ), 'as stated in network terms' ),
		'tpa_advisor_count'     => array( __( 'Public advisor count', 'tpa' ), 'count published by network' ),
		'tpa_published_score'   => array( __( "Network's own published review score", 'tpa' ), 'published score' ),
		'tpa_our_score'         => array( __( 'Our score from published terms and public review data', 'tpa' ), '' ),
		'tpa_source_url'        => array( __( 'Source URL on the network site', 'tpa' ), '' ),
		'tpa_source_date'       => array( __( 'Date the source page was read', 'tpa' ), '' ),
		'tpa_affiliate_url'     => array( __( 'Outbound link', 'tpa' ), '' ),
	);
}

function tpa_network_meta_box() {
	add_meta_box( 'tpa_published_terms', __( 'Published terms', 'tpa' ), 'tpa_network_meta_box_html', 'tpa_network', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'tpa_network_meta_box' );

function tpa_network_meta_box_html( $post ) {
	wp_nonce_field( 'tpa_save_network', 'tpa_network_nonce' );
	echo '<p style="margin:0 0 14px;color:#666;">' . esc_html__( 'Record only what the network states on its own pages. Add the source URL and the date you read it.', 'tpa' ) . '</p>';
	echo '<table class="form-table"><tbody>';
	foreach ( tpa_network_fields() as $key => $meta ) {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><input type="text" class="widefat" id="%1$s" name="%1$s" value="%3$s" placeholder="%4$s" /></td></tr>',
			esc_attr( $key ),
			esc_html( $meta[0] ),
			esc_attr( get_post_meta( $post->ID, $key, true ) ),
			esc_attr( $meta[1] )
		);
	}
	echo '</tbody></table>';
}

function tpa_save_network( $post_id ) {
	if ( ! isset( $_POST['tpa_network_nonce'] ) || ! wp_verify_nonce( $_POST['tpa_network_nonce'], 'tpa_save_network' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
	foreach ( array_keys( tpa_network_fields() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) { continue; }
		$value = wp_unslash( $_POST[ $key ] );
		$value = in_array( $key, array( 'tpa_source_url', 'tpa_affiliate_url' ), true ) ? esc_url_raw( $value ) : sanitize_text_field( $value );
		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_tpa_network', 'tpa_save_network' );
