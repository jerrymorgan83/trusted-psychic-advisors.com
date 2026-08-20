<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

<section class="tpa-section">
	<div class="tpa-section__head">
		<div class="tpa-section__title">
			<span class="tpa-eyebrow"><?php esc_html_e( 'Guides', 'tpa' ); ?></span>
			<h1 class="tpa-h2"><?php echo esc_html( wp_get_document_title() ); ?></h1>
		</div>
	</div>

	<div class="tpa-types">
		<?php while ( have_posts() ) : the_post(); ?>
			<a class="tpa-type" href="<?php the_permalink(); ?>">
				<strong><?php the_title(); ?></strong>
				<span><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></span>
			</a>
		<?php endwhile; ?>
	</div>
	<?php the_posts_pagination(); ?>
</section>

<?php get_footer(); ?>
