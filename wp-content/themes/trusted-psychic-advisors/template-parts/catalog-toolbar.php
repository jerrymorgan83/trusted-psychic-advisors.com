<?php
/**
 * Catalog sort/filter toolbar.
 *
 * Renders above the card grid on archive-psychic.php,
 * taxonomy-psychic_specialty.php and taxonomy-psychic_skill.php.
 *
 * Every interactive control is a <button>, <select> or <input> — never an
 * <a href="?..."> — so Simply Static never crawls a permutation URL.
 * All actual sorting/filtering happens client-side in catalog-filter.js;
 * this template only emits markup + the config block it reads.
 *
 * Expected $args (via get_template_part's third parameter):
 *   'tax'   => null | 'psychic_specialty' | 'psychic_skill'
 *   'term'  => null | current term slug
 *   'total' => int  server-rendered found_posts for this view
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tpad_cf_tax   = isset( $args['tax'] ) ? $args['tax'] : null;
$tpad_cf_term  = isset( $args['term'] ) ? $args['term'] : null;
$tpad_cf_total = isset( $args['total'] ) ? (int) $args['total'] : 0;

$tpad_cf_config = array(
    'indexUrl' => tpad_catalog_index_url(),
    'tax'      => $tpad_cf_tax,
    'term'     => $tpad_cf_term,
    'perPage'  => 24,
    'total'    => $tpad_cf_total,
);
?>
<div class="catalog-toolbar" id="bpr-cf-toolbar" data-state="server">

    <div class="catalog-toolbar__bar">
        <input
            type="text"
            id="bpr-cf-search"
            class="catalog-toolbar__search"
            placeholder="Search by name&hellip;"
            aria-label="Search psychics by name"
            autocomplete="off"
        >

        <select id="bpr-cf-sort" class="catalog-toolbar__sort" aria-label="Sort psychics">
            <option value="rating">Top Rated</option>
            <option value="reviews">Most Reviews</option>
            <option value="readings">Most Readings</option>
            <option value="price_low">Price: Low to High</option>
            <option value="price_high">Price: High to Low</option>
            <option value="name">Name A-Z</option>
        </select>

        <button type="button" id="bpr-cf-toggle" class="catalog-toolbar__toggle" aria-expanded="false" aria-controls="bpr-cf-panel">
            <?php echo tpad_icon( 'compass' ); ?>
            <span>Filters</span>
        </button>
    </div>

    <div class="catalog-toolbar__panel" id="bpr-cf-panel">

        <div class="catalog-toolbar__group">
            <span class="catalog-toolbar__label">Minimum Rating</span>
            <div class="chip-row" id="bpr-cf-rating" role="group" aria-label="Minimum rating">
                <button type="button" class="filter-chip filter-chip--active" data-value="0">Any</button>
                <button type="button" class="filter-chip" data-value="4">4.0+</button>
                <button type="button" class="filter-chip" data-value="4.5">4.5+</button>
                <button type="button" class="filter-chip" data-value="5">5.0</button>
            </div>
        </div>

        <div class="catalog-toolbar__group">
            <span class="catalog-toolbar__label">Price per Minute</span>
            <div class="chip-row" id="bpr-cf-price-presets" role="group" aria-label="Price range presets">
                <button type="button" class="filter-chip filter-chip--active" data-min="" data-max="">Any</button>
                <button type="button" class="filter-chip" data-min="" data-max="3">Under $3</button>
                <button type="button" class="filter-chip" data-min="3" data-max="5">$3 &ndash; $5</button>
                <button type="button" class="filter-chip" data-min="5" data-max="10">$5 &ndash; $10</button>
                <button type="button" class="filter-chip" data-min="10" data-max="">$10+</button>
            </div>
            <div class="catalog-toolbar__price-inputs">
                <input type="number" id="bpr-cf-pmin" min="0" step="0.5" placeholder="Min $" aria-label="Minimum price per minute">
                <span class="catalog-toolbar__price-sep">&ndash;</span>
                <input type="number" id="bpr-cf-pmax" min="0" step="0.5" placeholder="Max $" aria-label="Maximum price per minute">
            </div>
        </div>

        <div class="catalog-toolbar__group">
            <span class="catalog-toolbar__label">Availability</span>
            <button type="button" id="bpr-cf-online" class="filter-toggle" aria-pressed="false">
                <span class="filter-toggle__dot"></span> Online Now
            </button>
        </div>

        <div class="catalog-toolbar__group">
            <label class="catalog-toolbar__label" for="bpr-cf-yrs">Min. Years Experience</label>
            <input type="number" id="bpr-cf-yrs" min="0" step="1" placeholder="Any" aria-label="Minimum years of experience">
        </div>

        <?php if ( ! $tpad_cf_tax || 'psychic_specialty' !== $tpad_cf_tax ) : ?>
        <div class="catalog-toolbar__group">
            <label class="catalog-toolbar__label" for="bpr-cf-specialty">Specialties</label>
            <input type="text" id="bpr-cf-specialty-filter" class="catalog-toolbar__type-filter" placeholder="Type to filter&hellip;" aria-label="Filter the specialty list">
            <select id="bpr-cf-specialty" class="catalog-toolbar__multiselect" multiple aria-label="Filter by specialty" size="5"></select>
        </div>
        <?php endif; ?>

        <?php if ( ! $tpad_cf_tax || 'psychic_skill' !== $tpad_cf_tax ) : ?>
        <div class="catalog-toolbar__group">
            <label class="catalog-toolbar__label" for="bpr-cf-skill">Skills</label>
            <select id="bpr-cf-skill" class="catalog-toolbar__multiselect" multiple aria-label="Filter by skill" size="5"></select>
        </div>
        <?php endif; ?>

        <div class="catalog-toolbar__group">
            <label class="catalog-toolbar__label" for="bpr-cf-lang">Language</label>
            <select id="bpr-cf-lang" aria-label="Filter by language">
                <option value="">Any language</option>
            </select>
        </div>

        <div class="catalog-toolbar__group">
            <label class="catalog-toolbar__label" for="bpr-cf-tool">Tool Used</label>
            <select id="bpr-cf-tool" aria-label="Filter by tool used">
                <option value="">Any tool</option>
            </select>
        </div>

        <button type="button" id="bpr-cf-reset" class="btn btn--ghost btn--sm catalog-toolbar__reset">Reset All</button>
    </div>

    <div class="catalog-toolbar__active" id="bpr-cf-active" aria-hidden="true"></div>

    <div class="catalog-toolbar__status">
        <span id="bpr-cf-count" aria-live="polite"><?php echo esc_html( number_format( $tpad_cf_total ) ); ?> advisors</span>
        <span id="bpr-cf-msg" class="catalog-toolbar__msg" role="status"></span>
    </div>

    <script type="application/json" id="bpr-catalog-config"><?php echo wp_json_encode( $tpad_cf_config ); ?></script>
</div>
