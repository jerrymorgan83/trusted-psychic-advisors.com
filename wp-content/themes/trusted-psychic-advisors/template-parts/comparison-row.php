<?php
/**
 * One comparison-table row for a single advisor profile. Takes an explicit
 * post_id (no Loop dependency) so front-page.php can drive it straight
 * from tpa_top_psychics(). Collapses into a card below 900px.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$no      = isset( $args['no'] ) ? sprintf( '%02d', (int) $args['no'] ) : '01';
$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;

if ( ! $post_id ) {
	return;
}

$name      = get_the_title( $post_id );
$rating    = (float) get_post_meta( $post_id, '_rating', true );
$ratings   = (int) get_post_meta( $post_id, '_total_ratings', true );
$readings  = (int) get_post_meta( $post_id, '_total_readings', true );
$price     = (float) get_post_meta( $post_id, '_price_per_minute', true );
$sources   = json_decode( get_post_meta( $post_id, '_source_sites', true ) ?: '[]', true );
$source    = ( is_array( $sources ) && ! empty( $sources[0] ) ) ? trim( (string) $sources[0] ) : '';
$link      = get_permalink( $post_id );
$photos    = json_decode( get_post_meta( $post_id, '_photo_urls', true ) ?: '[]', true );
$photo     = ! empty( $photos ) && is_array( $photos ) ? $photos[0] : '';
$specialty = tpa_first_specialty_name( $post_id );

$rating_display  = $rating ? number_format( $rating, 1 ) : __( 'N/A', 'tpa' );
$reviews_display = $ratings
	? sprintf(
		/* translators: %s: number of published reviews */
		_n( '%s published review', '%s published reviews', $ratings, 'tpa' ),
		number_format( $ratings )
	)
	: __( 'No published reviews', 'tpa' );
$price_display    = $price ? '$' . number_format( $price, 2 ) . '/min' : __( 'Not listed', 'tpa' );
$readings_display = $readings ? number_format( $readings ) : __( 'N/A', 'tpa' );
$source_display   = $source ? $source : __( 'Not listed', 'tpa' );
?>
<div class="tpa-row">
	<span class="tpa-row__no"><?php echo esc_html( $no ); ?></span>

	<div class="tpa-row__network">
		<?php if ( $photo ) : ?>
			<img class="tpa-logo tpa-logo--md" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy">
		<?php else : ?>
			<div class="tpa-logo tpa-logo--md"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></div>
		<?php endif; ?>
		<div class="tpa-row__stack">
			<span><?php echo esc_html( $name ); ?></span>
			<small><?php echo esc_html( $specialty ? $specialty : __( 'Advisor profile', 'tpa' ) ); ?></small>
		</div>
	</div>

	<div class="tpa-row__stack">
		<span class="tpa-row__val"><span class="tpa-row__mobile tpa-label"><?php esc_html_e( 'Rating', 'tpa' ); ?></span><?php echo esc_html( $rating_display ); ?></span>
		<span class="tpa-row__src"><?php echo esc_html( $reviews_display ); ?></span>
	</div>

	<span class="tpa-row__val"><span class="tpa-row__mobile tpa-label"><?php esc_html_e( 'Price / min', 'tpa' ); ?></span><?php echo esc_html( $price_display ); ?></span>
	<span class="tpa-row__val"><span class="tpa-row__mobile tpa-label"><?php esc_html_e( 'Readings', 'tpa' ); ?></span><?php echo esc_html( $readings_display ); ?></span>
	<span class="tpa-row__val"><span class="tpa-row__mobile tpa-label"><?php esc_html_e( 'Source', 'tpa' ); ?></span><?php echo esc_html( $source_display ); ?></span>

	<div class="tpa-row__cta">
		<a class="tpa-btn tpa-btn--outline tpa-btn--sm" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View profile', 'tpa' ); ?></a>
	</div>
</div>
