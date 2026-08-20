<?php get_header(); ?>

<section class="catalog-hero">
<div class="wrap">
<?php echo tpad_breadcrumbs(); ?>
<h1 class="catalog-hero__title">Find Your Psychic Advisor</h1>
<p class="catalog-hero__sub">Browse <?php
    $count = wp_count_posts('psychic');
    echo number_format( $count->publish );
?> advisor profiles aggregated from public source pages. Ratings and prices repeat what each advisor's own profile publishes.</p>
</div>
</section>

<section class="catalog-body">
<div class="wrap">
<div class="catalog-layout">

<!-- Sidebar Filters -->
<aside class="catalog-sidebar">
<div class="filter-group">
<h3 class="filter-group__title">Specialties</h3>
<ul class="filter-list">
<?php
$specs = get_terms([ 'taxonomy' => 'psychic_specialty', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 20 ]);
if ( $specs && ! is_wp_error($specs) ) :
    foreach ( $specs as $t ) :
        $active = is_tax('psychic_specialty', $t->slug) ? ' class="active"' : '';
?>
<li<?php echo $active; ?>><a href="<?php echo esc_url(get_term_link($t)); ?>"><?php echo esc_html( tpa_clean_term_name( $t->name ) ); ?> <span class="filter-count"><?php echo $t->count; ?></span></a></li>
<?php endforeach; endif; ?>
</ul>
</div>

<div class="filter-group">
<h3 class="filter-group__title">Skills</h3>
<ul class="filter-list">
<?php
$skills = get_terms([ 'taxonomy' => 'psychic_skill', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 15 ]);
if ( $skills && ! is_wp_error($skills) ) :
    foreach ( $skills as $t ) :
        $active = is_tax('psychic_skill', $t->slug) ? ' class="active"' : '';
?>
<li<?php echo $active; ?>><a href="<?php echo esc_url(get_term_link($t)); ?>"><?php echo esc_html( tpa_clean_term_name( $t->name ) ); ?> <span class="filter-count"><?php echo $t->count; ?></span></a></li>
<?php endforeach; endif; ?>
</ul>
</div>
</aside>

<!-- Main Grid -->
<div class="catalog-main">
<div class="catalog-topbar">
<span class="catalog-topbar__count"><?php
    global $wp_query;
    echo number_format($wp_query->found_posts) . ' psychics';
    if ( is_tax() ) {
        echo ' in <strong>' . esc_html( tpa_clean_term_name( single_term_title( '', false ) ) ) . '</strong>';
    }
?></span>
</div>

<?php
// Client-side sort/filter toolbar (assets/js/catalog-filter.js). Replaces
// the old sidebar "Sort By" list of <a href="?sort=..."> links — those
// were crawlable query-string permutations that Simply Static would
// otherwise materialize as junk static files.
get_template_part( 'template-parts/catalog-toolbar', null, array(
    'tax'   => null,
    'term'  => null,
    'total' => $wp_query->found_posts,
) );
?>

<?php if ( have_posts() ) : ?>
<div class="psychic-grid">
<?php while ( have_posts() ) : the_post();
    $rating   = (float) get_post_meta( get_the_ID(), '_rating', true );
    $ratings  = (int) get_post_meta( get_the_ID(), '_total_ratings', true );
    $readings = (int) get_post_meta( get_the_ID(), '_total_readings', true );
    $price    = (float) get_post_meta( get_the_ID(), '_price_per_minute', true );
    $avail    = get_post_meta( get_the_ID(), '_availability', true );
    $photo    = get_post_meta( get_the_ID(), '_photo_urls', true );
    $photos   = $photo ? json_decode( $photo, true ) : [];
    $img_url  = ! empty($photos) ? $photos[0] : '';
    $specs_t  = get_the_terms( get_the_ID(), 'psychic_specialty' );
?>
<article class="psychic-card">
<a href="<?php the_permalink(); ?>" class="psychic-card__link">
<div class="psychic-card__photo">
<?php if ( $img_url ) : ?>
<img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" data-initial="<?php echo esc_attr( mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) ) ); ?>">
<?php else : ?>
<div class="psychic-card__avatar"><?php echo mb_strtoupper( mb_substr( get_the_title(), 0, 1 ) ); ?></div>
<?php endif; ?>
<?php if ( $avail === 'online' ) : ?>
<span class="psychic-card__status psychic-card__status--online"></span>
<?php endif; ?>
</div>
<div class="psychic-card__body">
<h3 class="psychic-card__name"><?php the_title(); ?></h3>
<div class="psychic-card__rating">
<?php echo tpad_stars_precise( $rating ); ?>
<span class="psychic-card__score"><?php echo $rating ? number_format($rating,1) : esc_html__('N/A','tpa'); ?></span>
<?php if ( $ratings ) : ?>
<span class="psychic-card__reviews">(<?php echo number_format($ratings); ?>)</span>
<?php endif; ?>
</div>
<?php if ( $specs_t && ! is_wp_error($specs_t) ) : ?>
<div class="psychic-card__tags">
<?php foreach ( array_slice($specs_t, 0, 3) as $st ) : ?>
<span class="tag"><?php echo esc_html( tpa_clean_term_name( $st->name ) ); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
<div class="psychic-card__footer">
<?php echo tpad_price_tag( $price ); ?>
<?php if ( $readings ) : ?>
<span class="psychic-card__readings"><?php echo number_format($readings); ?> readings</span>
<?php endif; ?>
</div>
</div>
</a>
</article>
<?php endwhile; ?>
</div>

<div class="catalog-pagination">
<?php echo paginate_links([
    'prev_text' => '&larr; Prev',
    'next_text' => 'Next &rarr;',
    'type'      => 'list',
]); ?>
</div>

<?php else : ?>
<div class="catalog-empty">
<p>No psychics found matching your criteria.</p>
<a href="<?php echo get_post_type_archive_link('psychic'); ?>" class="btn btn--ghost">View All Psychics</a>
</div>
<?php endif; ?>

</div><!-- .catalog-main -->
</div><!-- .catalog-layout -->
</div>
</section>

<?php get_footer(); ?>
