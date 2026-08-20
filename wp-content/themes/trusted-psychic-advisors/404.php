<?php
/**
 * 404: matches the new tpa-* design (paper background, grotesk headline,
 * accent-green button). No red anywhere -- the old theme's dark-red
 * numeral/button belonged to the retired burgundy palette.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>

<section class="tpa-section tpa-error-404">
	<div class="tpa-error-404__inner">
		<span class="tpa-eyebrow tpa-eyebrow--accent">404</span>
		<h1 class="tpa-h1 tpa-error-404__title">Page not found</h1>
		<p class="tpa-lede tpa-error-404__lede">The page you are looking for does not exist or has been moved.</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="tpa-btn tpa-btn--solid">Back to Home</a>
	</div>
</section>

<?php get_footer(); ?>
