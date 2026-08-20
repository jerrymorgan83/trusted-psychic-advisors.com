<?php
/**
 * Featured advisor card (homepage hero). Every value is read straight off
 * the psychic post's own postmeta -- nothing here is a firsthand claim
 * about the advisor's ability.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$post_id = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;

if ( ! $post_id ) {
	?>
	<article class="tpa-entry">
		<div class="tpa-entry__head">
			<strong><?php esc_html_e( 'Top-ranked profile', 'tpa' ); ?></strong>
			<span><?php esc_html_e( 'From advisor profiles', 'tpa' ); ?></span>
		</div>
		<p class="tpa-body" style="padding-top:18px;"><?php esc_html_e( 'No advisor profiles are available yet.', 'tpa' ); ?></p>
	</article>
	<?php
	return;
}

$name      = get_the_title( $post_id );
$rating    = (float) get_post_meta( $post_id, '_rating', true );
$ratings   = (int) get_post_meta( $post_id, '_total_ratings', true );
$readings  = (int) get_post_meta( $post_id, '_total_readings', true );
$price     = (float) get_post_meta( $post_id, '_price_per_minute', true );
$specialty = tpa_first_specialty_name( $post_id );
$link      = get_permalink( $post_id );
$photos    = json_decode( get_post_meta( $post_id, '_photo_urls', true ) ?: '[]', true );
$photo     = ! empty( $photos ) && is_array( $photos ) ? $photos[0] : '';

$score = $rating
	? sprintf(
		/* translators: 1: rating out of 5, 2: number of published ratings */
		_n( '%1$s out of 5, from %2$s published rating', '%1$s out of 5, from %2$s published ratings', max( 1, $ratings ), 'tpa' ),
		number_format( $rating, 1 ),
		number_format( $ratings )
	)
	: __( 'Not yet rated', 'tpa' );

$cells = array(
	array( __( 'Rating', 'tpa' ), $rating ? number_format( $rating, 1 ) . ' / 5' : __( 'Not yet rated', 'tpa' ) ),
	array( __( 'Price per minute', 'tpa' ), $price ? '$' . number_format( $price, 2 ) . '/min' : __( 'Not listed', 'tpa' ) ),
	array( __( 'Specialty', 'tpa' ), $specialty ? $specialty : __( 'Not listed', 'tpa' ) ),
	array( __( 'Total readings', 'tpa' ), $readings ? number_format( $readings ) : __( 'Not listed', 'tpa' ) ),
);
?>
<article class="tpa-entry">
	<div class="tpa-entry__head">
		<strong><?php esc_html_e( 'Top-ranked profile', 'tpa' ); ?></strong>
		<span><?php esc_html_e( 'From advisor profiles', 'tpa' ); ?></span>
	</div>

	<div class="tpa-entry__id">
		<?php if ( $photo ) : ?>
			<img class="tpa-logo tpa-logo--lg" src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy">
		<?php else : ?>
			<div class="tpa-logo tpa-logo--lg"><?php echo esc_html( mb_strtoupper( mb_substr( $name, 0, 1 ) ) ); ?></div>
		<?php endif; ?>
		<div style="display:flex;flex-direction:column;gap:6px;">
			<span class="tpa-entry__name"><?php echo esc_html( $name ); ?></span>
			<span class="tpa-entry__score"><?php echo esc_html( $score ); ?></span>
		</div>
	</div>

	<div class="tpa-grid2">
		<?php foreach ( $cells as $cell ) : ?>
			<div>
				<span class="tpa-label"><?php echo esc_html( $cell[0] ); ?></span>
				<b><?php echo esc_html( $cell[1] ); ?></b>
			</div>
		<?php endforeach; ?>
	</div>

	<a class="tpa-btn tpa-btn--ink tpa-btn--block tpa-entry__cta" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Read the full profile', 'tpa' ); ?></a>
	<p class="tpa-entry__disclosure"><?php echo esc_html( tpa_copy( 'tpa_disclosure', "Figures come from each advisor's published profile. We may earn a commission. It does not change the listing order." ) ); ?></p>
	<?php $source_line = tpa_psychic_source_line( $post_id ); ?>
	<?php if ( $source_line ) : ?>
		<p class="tpa-note" style="text-align:center;margin-top:6px;"><?php echo wp_kses_post( $source_line ); ?></p>
	<?php endif; ?>
</article>
