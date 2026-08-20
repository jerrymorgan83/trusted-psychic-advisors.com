<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>

<section class="tpa-section">
	<div class="tpa-section__head">
		<div class="tpa-section__title">
			<span class="tpa-eyebrow"><?php esc_html_e( 'The directory', 'tpa' ); ?></span>
			<h1 class="tpa-h2"><?php esc_html_e( 'Published terms, side by side', 'tpa' ); ?></h1>
		</div>
		<p class="tpa-section__aside"><?php esc_html_e( 'Each row repeats what the network states on its own pages, with the date the source page was read.', 'tpa' ); ?></p>
	</div>

	<div class="tpa-table">
		<?php
		$no = 0;
		while ( have_posts() ) : the_post();
			$no++;
			get_template_part( 'template-parts/comparison-row', null, array( 'no' => $no ) );
		endwhile;
		?>
	</div>
	<p class="tpa-note" style="margin-top:16px;"><?php esc_html_e( "Prices and offers change without notice. Check the network's own page before you buy.", 'tpa' ); ?></p>
	<?php the_posts_pagination(); ?>
</section>

<?php get_footer(); ?>
