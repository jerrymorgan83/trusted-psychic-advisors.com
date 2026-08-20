<?php
/**
 * Single psychic profile: matches the new tpa-* design system. The header
 * reuses the homepage's entry-card key/value grid pattern (.tpa-entry /
 * .tpa-entry__id / .tpa-grid2) so the profile's stat block looks like the
 * same component, not a new one. Every value below is read straight off
 * the psychic post's own postmeta -- nothing here is a firsthand claim
 * about the advisor.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$pid      = get_the_ID();
$rating   = (float) get_post_meta( $pid, '_rating', true );
$ratings  = (int) get_post_meta( $pid, '_total_ratings', true );
$readings = (int) get_post_meta( $pid, '_total_readings', true );
$price    = (float) get_post_meta( $pid, '_price_per_minute', true );
$avail    = get_post_meta( $pid, '_availability', true ) ?: 'unknown';
$intro    = get_post_meta( $pid, '_intro_offer', true );
// Strip scraped script artifacts from intro offer
if ( $intro && ( strpos( $intro, 'window.' ) !== false || strpos( $intro, '<script' ) !== false ) ) {
    $intro = '';
}
$years    = (int) get_post_meta( $pid, '_years_experience', true );
$since    = get_post_meta( $pid, '_member_since', true );
$comm     = get_post_meta( $pid, '_communication_style', true );
$langs    = json_decode( get_post_meta( $pid, '_languages', true ) ?: '[]', true );
$tools    = json_decode( get_post_meta( $pid, '_tools_used', true ) ?: '[]', true );
$sources  = json_decode( get_post_meta( $pid, '_source_sites', true ) ?: '[]', true );
$src_urls = json_decode( get_post_meta( $pid, '_source_urls', true ) ?: '[]', true );
$photos   = json_decode( get_post_meta( $pid, '_photo_urls', true ) ?: '[]', true );
$reviews  = json_decode( get_post_meta( $pid, '_reviews_json', true ) ?: '[]', true );
$specs_t  = get_the_terms( $pid, 'psychic_specialty' );
$skills_t = get_the_terms( $pid, 'psychic_skill' );
$img_url  = ! empty( $photos ) ? $photos[0] : '';

$score_text = $rating
	? sprintf(
		/* translators: 1: rating out of 5, 2: number of published ratings */
		_n( '%1$s out of 5, from %2$s published rating', '%1$s out of 5, from %2$s published ratings', max( 1, $ratings ), 'tpa' ),
		number_format( $rating, 1 ),
		number_format( $ratings )
	)
	: __( 'Not yet rated', 'tpa' );
?>

<section class="tpa-hero">
	<div class="tpa-hero__col">
		<?php echo tpad_breadcrumbs(); ?>
		<div class="tpa-hero__kicker tpa-eyebrow tpa-eyebrow--accent">
			<i></i><span><?php esc_html_e( 'Advisor profile', 'tpa' ); ?></span>
		</div>
		<h1 class="tpa-h1 tpa-profile__name"><?php the_title(); ?></h1>
		<div class="tpa-profile__status">
			<?php echo tpad_availability_dot( $avail ); ?>
		</div>

		<?php if ( ( $specs_t && ! is_wp_error( $specs_t ) ) || ( $skills_t && ! is_wp_error( $skills_t ) ) ) : ?>
		<div class="tpa-strip__skills tpa-profile__tags">
			<?php if ( $specs_t && ! is_wp_error( $specs_t ) ) : foreach ( $specs_t as $st ) : ?>
				<a class="tpa-skill-pill" href="<?php echo esc_url( get_term_link( $st ) ); ?>"><?php echo esc_html( tpa_clean_term_name( $st->name ) ); ?></a>
			<?php endforeach; endif; ?>
			<?php if ( $skills_t && ! is_wp_error( $skills_t ) ) : foreach ( $skills_t as $sk ) : ?>
				<a class="tpa-skill-pill" href="<?php echo esc_url( get_term_link( $sk ) ); ?>"><?php echo esc_html( tpa_clean_term_name( $sk->name ) ); ?></a>
			<?php endforeach; endif; ?>
		</div>
		<?php endif; ?>
	</div>

	<article class="tpa-entry">
		<div class="tpa-entry__head">
			<strong><?php esc_html_e( 'Profile summary', 'tpa' ); ?></strong>
			<span><?php esc_html_e( 'From advisor profile', 'tpa' ); ?></span>
		</div>

		<div class="tpa-entry__id">
			<?php if ( $img_url ) : ?>
				<img class="tpa-logo tpa-logo--lg" src="<?php echo esc_url( $img_url ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
			<?php else : ?>
				<div class="tpa-logo tpa-logo--lg"><?php echo esc_html( mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) ) ); ?></div>
			<?php endif; ?>
			<div style="display:flex;flex-direction:column;gap:6px;">
				<span class="tpa-entry__name"><?php the_title(); ?></span>
				<span class="tpa-entry__score"><?php echo esc_html( $score_text ); ?></span>
			</div>
		</div>

		<div class="tpa-grid2">
			<div>
				<span class="tpa-label"><?php esc_html_e( 'Price per minute', 'tpa' ); ?></span>
				<b><?php echo $price ? '$' . number_format( $price, 2 ) . '/min' : esc_html__( 'Not listed', 'tpa' ); ?></b>
			</div>
			<div>
				<span class="tpa-label"><?php esc_html_e( 'Total readings', 'tpa' ); ?></span>
				<b><?php echo $readings ? esc_html( number_format( $readings ) ) : esc_html__( 'Not listed', 'tpa' ); ?></b>
			</div>
			<div>
				<span class="tpa-label"><?php esc_html_e( 'Years experience', 'tpa' ); ?></span>
				<b><?php echo $years ? esc_html( $years . '+ years' ) : esc_html__( 'Not listed', 'tpa' ); ?></b>
			</div>
			<div>
				<span class="tpa-label"><?php esc_html_e( 'Member since', 'tpa' ); ?></span>
				<b><?php echo $since ? esc_html( $since ) : esc_html__( 'Not listed', 'tpa' ); ?></b>
			</div>
		</div>

		<?php if ( $intro ) : ?>
			<p class="tpa-note" style="margin-top:14px;"><?php echo esc_html( $intro ); ?></p>
		<?php endif; ?>

		<?php $source_line = tpa_psychic_source_line( $pid ); ?>
		<?php if ( $source_line ) : ?>
			<p class="tpa-note" style="text-align:center;margin-top:12px;"><?php echo wp_kses_post( $source_line ); ?></p>
		<?php endif; ?>
	</article>
</section>

<section class="tpa-section">
	<div class="tpa-profile__layout">

		<div class="tpa-profile__main">

			<?php if ( get_the_content() ) : ?>
			<div class="tpa-profile__block">
				<h2 class="tpa-h3"><?php echo esc_html( sprintf( __( 'About %s', 'tpa' ), get_the_title() ) ); ?></h2>
				<div class="tpa-body tpa-prose"><?php the_content(); ?></div>
			</div>
			<?php endif; ?>

			<?php if ( $tools || $langs || $comm ) : ?>
			<div class="tpa-profile__block">
				<h2 class="tpa-h3"><?php esc_html_e( 'Details', 'tpa' ); ?></h2>
				<div class="tpa-grid2 tpa-profile__details">
					<?php if ( $tools ) : ?>
						<div><span class="tpa-label"><?php esc_html_e( 'Tools', 'tpa' ); ?></span><b><?php echo esc_html( implode( ', ', array_filter( $tools ) ) ); ?></b></div>
					<?php endif; ?>
					<?php if ( $langs ) : ?>
						<div><span class="tpa-label"><?php esc_html_e( 'Languages', 'tpa' ); ?></span><b><?php echo esc_html( implode( ', ', $langs ) ); ?></b></div>
					<?php endif; ?>
					<?php if ( $comm ) : ?>
						<div><span class="tpa-label"><?php esc_html_e( 'Communication style', 'tpa' ); ?></span><b><?php echo esc_html( $comm ); ?></b></div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $reviews ) : ?>
			<div class="tpa-profile__block">
				<h2 class="tpa-h3"><?php esc_html_e( 'Reviews', 'tpa' ); ?> <span class="review-count"><?php echo '(' . count( $reviews ) . ')'; ?></span></h2>
				<div class="review-list">
					<?php foreach ( array_slice( $reviews, 0, 50 ) as $rev ) :
						$r_name   = isset( $rev['reviewer_name'] ) ? $rev['reviewer_name'] : __( 'Anonymous', 'tpa' );
						$r_text   = isset( $rev['unique_text'] ) ? $rev['unique_text'] : ( isset( $rev['text'] ) ? $rev['text'] : '' );
						$r_rating = isset( $rev['rating'] ) ? (float) $rev['rating'] : 0;
						$r_date   = isset( $rev['review_date'] ) ? $rev['review_date'] : '';
						if ( ! $r_text ) { continue; }
						$r_parsed = tpad_parse_scraped_review( $r_text );
						if ( '' === $r_parsed['text'] ) { continue; }
					?>
					<div class="review-item">
						<div class="review-item__header">
							<span class="review-item__author"><?php echo esc_html( $r_name ); ?></span>
							<?php if ( $r_rating ) : ?>
								<span class="review-item__rating"><?php echo tpad_stars_precise( $r_rating ); ?></span>
							<?php endif; ?>
							<?php if ( $r_parsed['channel'] ) : ?>
								<span class="review-item__channel"><?php echo esc_html( $r_parsed['channel'] ); ?></span>
							<?php endif; ?>
							<?php if ( $r_date ) : ?>
								<span class="review-item__date"><?php echo esc_html( $r_date ); ?></span>
							<?php endif; ?>
						</div>
						<p class="review-item__text"><?php echo esc_html( $r_parsed['text'] ); ?></p>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php tpad_render_user_reviews( $pid ); ?>
		</div>

		<aside class="tpa-profile__sidebar">
			<?php if ( $sources ) : ?>
			<div class="tpa-card-ish">
				<h4 class="tpa-h3" style="font-size:16px;margin-bottom:14px;"><?php esc_html_e( 'Available On', 'tpa' ); ?></h4>
				<ul class="source-list">
					<?php foreach ( $sources as $i => $src ) :
						$url = isset( $src_urls[ $i ] ) ? $src_urls[ $i ] : '#';
					?>
					<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="nofollow noopener"><?php echo esc_html( $src ); ?> &rarr;</a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php if ( count( $photos ) > 1 ) : ?>
			<div class="tpa-card-ish">
				<h4 class="tpa-h3" style="font-size:16px;margin-bottom:14px;"><?php esc_html_e( 'Photos', 'tpa' ); ?></h4>
				<div class="tpa-photo-grid">
					<?php foreach ( array_slice( $photos, 0, 6 ) as $p ) : ?>
						<img src="<?php echo esc_url( $p ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" data-initial="<?php echo esc_attr( mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) ) ); ?>">
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</aside>

	</div>
</section>

<?php
if ( $specs_t && ! is_wp_error( $specs_t ) ) {
	$rq = new WP_Query( array(
		'post_type'      => 'psychic',
		'posts_per_page' => 4,
		'post__not_in'   => array( $pid ),
		'tax_query'      => array( array( 'taxonomy' => 'psychic_specialty', 'terms' => wp_list_pluck( $specs_t, 'term_id' ) ) ),
		'meta_key'       => '_rating',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
	) );
	if ( $rq->have_posts() ) :
	?>
<section class="tpa-section tpa-section--deep">
	<div class="tpa-section__head">
		<div class="tpa-section__title">
			<span class="tpa-eyebrow"><?php esc_html_e( 'Related profiles', 'tpa' ); ?></span>
			<h2 class="tpa-h2" style="font-size:32px;"><?php esc_html_e( 'Similar psychics', 'tpa' ); ?></h2>
		</div>
	</div>
	<div class="psychic-grid psychic-grid--sm">
		<?php while ( $rq->have_posts() ) : $rq->the_post();
			$r_rating = (float) get_post_meta( get_the_ID(), '_rating', true );
			$r_price  = (float) get_post_meta( get_the_ID(), '_price_per_minute', true );
			$r_photos = json_decode( get_post_meta( get_the_ID(), '_photo_urls', true ) ?: '[]', true );
			$r_img    = ! empty( $r_photos ) ? $r_photos[0] : '';
		?>
		<article class="psychic-card">
			<a href="<?php the_permalink(); ?>" class="psychic-card__link">
				<div class="psychic-card__photo">
					<?php if ( $r_img ) : ?>
						<img src="<?php echo esc_url( $r_img ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" data-initial="<?php echo esc_attr( mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) ) ); ?>">
					<?php else : ?>
						<div class="psychic-card__avatar"><?php echo mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) ); ?></div>
					<?php endif; ?>
				</div>
				<div class="psychic-card__body">
					<h3 class="psychic-card__name"><?php the_title(); ?></h3>
					<div class="psychic-card__rating"><?php echo tpad_stars_precise( $r_rating ); ?> <span class="psychic-card__score"><?php echo $r_rating ? number_format( $r_rating, 1 ) : esc_html__( 'N/A', 'tpa' ); ?></span></div>
					<div class="psychic-card__footer"><?php echo tpad_price_tag( $r_price ); ?></div>
				</div>
			</a>
		</article>
		<?php endwhile; ?>
	</div>
</section>
	<?php
	endif;
	wp_reset_postdata();
}
?>

<?php get_footer(); ?>
