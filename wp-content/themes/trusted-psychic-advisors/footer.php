<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<?php
/**
 * Legal links column, ported from tpad-theme's footer. Guarded the same
 * way as the old theme: only a slug that resolves to a PUBLISHED page
 * prints a link, so this renders safely even before any of the four
 * pages exist. Computed up front so the column count (and therefore the
 * grid) is known before the columns wrapper opens below.
 */
$tpa_legal_links = array(
	'privacy-policy' => __( 'Privacy Policy', 'tpa' ),
	'cookie-policy'  => __( 'Cookie Policy', 'tpa' ),
	'terms'          => __( 'Terms of Service', 'tpa' ),
	'disclaimer'     => __( 'Disclaimer', 'tpa' ),
);
$tpa_legal_items = '';
foreach ( $tpa_legal_links as $tpa_legal_slug => $tpa_legal_label ) {
	$tpa_legal_page = get_page_by_path( $tpa_legal_slug );
	if ( $tpa_legal_page && 'publish' === $tpa_legal_page->post_status ) {
		$tpa_legal_items .= sprintf(
			'<li><a href="%s">%s</a></li>',
			esc_url( home_url( '/' . $tpa_legal_slug . '/' ) ),
			esc_html( $tpa_legal_label )
		);
	}
}
// Brand column + the 3 existing nav-menu columns, plus Legal if present.
$tpa_footer_col_count = 3 + ( $tpa_legal_items ? 1 : 0 );
?>
<footer class="tpa-footer">
	<div class="tpa-footer__cols" style="--tpa-footer-col-count:<?php echo (int) $tpa_footer_col_count; ?>;">
		<div class="tpa-footer__brand">
			<strong><?php bloginfo( 'name' ); ?></strong>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : 'A directory of psychic advisor profiles, aggregated from public source pages.' ); ?></p>
		</div>

		<?php
		$footer_cols = array(
			'footer_networks' => __( 'Directory', 'tpa' ),
			'footer_types'    => __( 'Reading types', 'tpa' ),
			'footer_about'    => __( 'About', 'tpa' ),
		);
		foreach ( $footer_cols as $location => $title ) :
			?>
			<div class="tpa-footer__col">
				<h4><?php echo esc_html( $title ); ?></h4>
				<?php
				if ( has_nav_menu( $location ) ) {
					wp_nav_menu( array(
						'theme_location' => $location,
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 1,
					) );
				} else {
					tpa_footer_fallback_links( $location );
				}
				?>
			</div>
		<?php endforeach; ?>

		<?php if ( $tpa_legal_items ) : ?>
			<div class="tpa-footer__col">
				<h4><?php esc_html_e( 'Legal', 'tpa' ); ?></h4>
				<ul><?php echo $tpa_legal_items; ?></ul>
			</div>
		<?php endif; ?>
	</div>

	<p class="tpa-footer__legal"><?php echo esc_html( tpa_copy( 'tpa_legal', 'For entertainment. Readings are not a substitute for medical, legal or financial advice. Users must be 18 or over. Figures on this site repeat what each network publishes and may be out of date. Some links earn us a commission at no cost to you.' ) ); ?></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
