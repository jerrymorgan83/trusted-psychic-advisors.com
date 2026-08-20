<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( get_theme_mod( 'tpa_show_banner', true ) ) : ?>
<div class="tpa-banner">
	<span><?php echo esc_html( tpa_copy( 'tpa_banner_text', 'Comparison built from published prices, published intro offers and published refund terms.' ) ); ?></span>
	<a href="<?php echo esc_url( tpa_copy( 'tpa_banner_url', '#comparison' ) ); ?>"><?php echo esc_html( tpa_copy( 'tpa_banner_link', 'See the table' ) ); ?></a>
</div>
<?php endif; ?>

<header class="tpa-header">
	<a class="tpa-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<span class="tpa-brand__name"><?php bloginfo( 'name' ); ?></span>
		<span class="tpa-brand__tag"><?php echo esc_html( tpa_copy( 'tpa_tagline', 'A directory of published terms' ) ); ?></span>
	</a>

	<nav class="tpa-nav" id="tpa-nav">
		<?php
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'fallback_cb'    => false,
			'depth'          => 1,
		) );
		?>
	</nav>

	<button class="tpa-burger" type="button" aria-controls="tpa-nav" aria-expanded="false">
		<span></span><span></span>
		<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'tpa' ); ?></span>
	</button>

	<a class="tpa-btn tpa-btn--ink" href="<?php echo esc_url( get_post_type_archive_link( 'psychic' ) ?: home_url( '/psychics/' ) ); ?>"><?php esc_html_e( 'Compare advisors', 'tpa' ); ?></a>
</header>
