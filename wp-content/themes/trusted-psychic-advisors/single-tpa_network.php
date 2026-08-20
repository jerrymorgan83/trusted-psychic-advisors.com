<?php
/**
 * Single network entry: published terms, then editorial notes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>

<section class="tpa-hero">
	<div class="tpa-hero__col">
		<span class="tpa-eyebrow"><?php esc_html_e( 'Directory entry', 'tpa' ); ?></span>
		<h1 class="tpa-h1"><?php the_title(); ?></h1>
		<?php if ( has_excerpt() ) : ?>
			<p class="tpa-lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<?php if ( tpa_source_line( get_the_ID() ) ) : ?>
			<p class="tpa-note"><?php echo wp_kses_post( tpa_source_line( get_the_ID() ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php get_template_part( 'template-parts/entry-card', null, array( 'post_id' => get_the_ID() ) ); ?>
</section>

<section class="tpa-section">
	<div class="tpa-prose"><?php the_content(); ?></div>
</section>

<?php endwhile; get_footer(); ?>
