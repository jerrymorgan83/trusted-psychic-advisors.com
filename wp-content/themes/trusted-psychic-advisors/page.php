<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>

<section class="tpa-section">
	<h1 class="tpa-h2" style="margin-bottom:28px;"><?php the_title(); ?></h1>
	<div class="tpa-prose"><?php the_content(); ?></div>
</section>

<?php endwhile; get_footer(); ?>
