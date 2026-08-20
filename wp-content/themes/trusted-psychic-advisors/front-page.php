<?php
/**
 * Homepage for Trusted Psychic Advisors.
 *
 * The information path is deliberately question-first: visitors can name the
 * area they want to explore, understand the kinds of readings available, and
 * only then compare public advisor profiles. Ratings and prices remain tied
 * to their published source data; this page makes no outcome or testing claim.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$stats         = tpa_homepage_stats();
$top_ids       = tpa_top_psychics( 6 );
$reading_types = tpa_top_specialty_terms( 8 );
$archive_url   = get_post_type_archive_link( 'psychic' ) ?: home_url( '/psychics/' );

$question_paths = array(
	array( 'Love & relationships', 'Bring more clarity to a connection, a change, or the pattern you keep returning to.', 'What would help me understand this relationship with more honesty?' ),
	array( 'Career & direction', 'Explore the choices, strengths, and next steps that deserve your attention.', 'Where could I focus my energy to move forward with purpose?' ),
	array( 'Life transitions', 'Make room to reflect when a chapter is ending, beginning, or asking to be reimagined.', 'What am I being invited to notice in this transition?' ),
	array( 'Spiritual growth', 'Use symbols, intuition, and reflection to consider the questions beneath the surface.', 'What practice or perspective might support my growth right now?' ),
);
?>

<main id="main-content">
	<section class="tpa-guide-hero">
		<div class="tpa-guide-hero__copy">
			<p class="tpa-guide-kicker"><span aria-hidden="true">✦</span> A directory for thoughtful seekers</p>
			<h1>Start with the question that matters to you.</h1>
			<p class="tpa-guide-lede">A reading can be a space to reflect, not a promise of certainty. Find a reading style, compare public advisor profiles, and choose with more context.</p>
			<div class="tpa-guide-hero__actions">
				<a class="tpa-guide-button tpa-guide-button--gold" href="#your-question">Explore your question <span aria-hidden="true">↓</span></a>
				<a class="tpa-guide-text-link" href="<?php echo esc_url( $archive_url ); ?>">Browse all advisor profiles <span aria-hidden="true">→</span></a>
			</div>
			<p class="tpa-guide-disclosure">Profile details come from public source pages. Confirm current rates and availability before booking.</p>
		</div>

		<aside class="tpa-question-path" aria-label="Your path through the directory">
			<div class="tpa-question-path__top"><span>YOUR PATH</span><span>01—03</span></div>
			<ol>
				<li><span class="tpa-question-path__number">01</span><div><strong>Name what you want to explore</strong><p>Begin with an open question, not a prediction to chase.</p></div></li>
				<li><span class="tpa-question-path__number">02</span><div><strong>Find a reading style</strong><p>Tarot, astrology, mediumship, and other approaches offer different lenses.</p></div></li>
				<li><span class="tpa-question-path__number">03</span><div><strong>Compare the public details</strong><p>Review specialties, published ratings, prices, and the original source profile.</p></div></li>
			</ol>
			<a href="#comparison">See how profiles are compared <span aria-hidden="true">→</span></a>
		</aside>
	</section>

	<section class="tpa-guide-proof" aria-label="Directory coverage">
		<div><strong><?php echo esc_html( number_format( $stats['advisors'] ) ); ?></strong><span>public advisor profiles</span></div>
		<div><strong><?php echo esc_html( number_format( $stats['specialties'] ) ); ?></strong><span>reading specialties indexed</span></div>
		<div><strong><?php echo esc_html( number_format( $stats['sources'] ) ); ?></strong><span>public sources represented</span></div>
		<p>These counts describe the directory, not an endorsement or a measure of reading quality.</p>
	</section>

	<section class="tpa-guide-section tpa-guide-section--questions" id="your-question">
		<div class="tpa-guide-section__intro">
			<p class="tpa-guide-kicker">Choose a place to begin</p>
			<h2>A useful question makes room for a useful conversation.</h2>
			<p>Specific, open-ended questions usually create more room for reflection than a yes-or-no prediction. Use these prompts as a starting point, then choose the kind of reading that feels relevant.</p>
		</div>
		<div class="tpa-question-list">
			<?php foreach ( $question_paths as $index => $path ) : ?>
				<a class="tpa-question" href="#reading-types">
					<span class="tpa-question__index">0<?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
					<div><h3><?php echo esc_html( $path[0] ); ?></h3><p><?php echo esc_html( $path[1] ); ?></p></div>
					<blockquote>“<?php echo esc_html( $path[2] ); ?>”</blockquote>
					<span class="tpa-question__arrow" aria-hidden="true">↘</span>
				</a>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="tpa-guide-section tpa-guide-section--reading-types" id="reading-types">
		<div class="tpa-guide-section__intro tpa-guide-section__intro--split">
			<div><p class="tpa-guide-kicker">Reading styles</p><h2>Match the question to a lens that helps you explore it.</h2></div>
			<p>A reading style is not a guarantee of an outcome. It is a language for approaching a situation from a different angle. Explore the specialties represented in our public profile directory.</p>
		</div>
		<div class="tpa-reading-types">
			<?php if ( $reading_types ) : foreach ( $reading_types as $term ) : ?>
				<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"><span class="tpa-reading-types__mark" aria-hidden="true">✦</span><strong><?php echo esc_html( tpa_clean_term_name( $term->name ) ); ?></strong><span><?php echo esc_html( sprintf( _n( '%s profile', '%s profiles', $term->count, 'tpa' ), number_format( $term->count ) ) ); ?></span><i aria-hidden="true">→</i></a>
			<?php endforeach; else : ?>
				<p class="tpa-note">Reading types will appear here as advisor profiles are added.</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="tpa-guide-section tpa-guide-section--comparison" id="comparison">
		<div class="tpa-guide-section__intro tpa-guide-section__intro--split">
			<div><p class="tpa-guide-kicker">Compare with context</p><h2>Once you know what you are looking for, look beyond a single score.</h2></div>
			<p>We show the published rating, review volume, stated price, specialty, and original source profile side by side. Rates and availability can change, so use the table to narrow your choices, then verify before booking.</p>
		</div>
		<div class="tpa-table tpa-table--guide">
			<div class="tpa-row tpa-row--head"><span>NO.</span><span>ADVISOR</span><span>PUBLISHED RATING</span><span>PRICE / MIN</span><span>READINGS</span><span>SOURCE</span><span style="text-align:right;">PROFILE</span></div>
			<?php if ( $top_ids ) : $no = 0; foreach ( $top_ids as $psychic_id ) : $no++; get_template_part( 'template-parts/comparison-row', null, array( 'no' => $no, 'post_id' => $psychic_id ) ); endforeach; else : ?>
				<p class="tpa-note" style="padding:26px;">No advisor profiles are available yet.</p>
			<?php endif; ?>
		</div>
		<div class="tpa-guide-section__footer"><a class="tpa-guide-text-link" href="<?php echo esc_url( $archive_url ); ?>">Open the full advisor directory <span aria-hidden="true">→</span></a></div>
	</section>

	<section class="tpa-guide-method">
		<div class="tpa-guide-method__heading"><p class="tpa-guide-kicker">What the directory does, and does not, say</p><h2>Useful context is more honest than a promise.</h2></div>
		<div class="tpa-guide-method__items">
			<div><span>PUBLIC PROFILE DATA</span><h3>We identify what a profile publishes.</h3><p>Listings may include the profile's stated rating, review count, price, availability, specialties, and source links.</p></div>
			<div><span>CLEAR LIMITS</span><h3>We do not claim to test psychic ability.</h3><p>A directory cannot establish the accuracy of a reading. Treat any reading as personal guidance and keep your own judgment at the center.</p></div>
			<div><span>VERIFY BEFORE BOOKING</span><h3>We point you back to the source.</h3><p>Published terms can change. Open the original profile to confirm current pricing, availability, and policies before making a decision.</p></div>
		</div>
	</section>

	<section class="tpa-guide-section tpa-guide-section--faq">
		<div class="tpa-guide-section__intro tpa-guide-section__intro--split"><div><p class="tpa-guide-kicker">A few practical questions</p><h2>Before you choose an advisor.</h2></div><p>We encourage curiosity, care, and realistic expectations. Psychic readings are for entertainment and reflection, not professional medical, legal, financial, or mental-health advice.</p></div>
		<div class="tpa-guide-faq">
			<details><summary>What makes a question useful for a reading?</summary><p>Questions that include your context and invite reflection are often more productive than requests for a fixed prediction. Ask what you can notice, consider, or do next.</p></details>
			<details><summary>Where do the profile details come from?</summary><p>Each listing is built from publicly available advisor-profile information. Where a source is available, the profile links back so you can check the original details.</p></details>
			<details><summary>Are the ratings on this site a measure of psychic ability?</summary><p>No. Ratings and review counts shown on profiles are published figures from their source pages. They are not our own assessment of an advisor or the outcome of a reading.</p></details>
			<details><summary>What should I check before booking?</summary><p>Review the original source profile for the current rate, availability, service terms, and any refund policy. Choose a provider whose boundaries and process feel clear to you.</p></details>
		</div>
	</section>

	<section class="tpa-guide-closing">
		<div><p class="tpa-guide-kicker">When you are ready</p><h2>Let your question lead the way.</h2><p>Explore reading types, compare public profile details, and take only the next step that feels right for you.</p></div>
		<a class="tpa-guide-button tpa-guide-button--gold" href="<?php echo esc_url( $archive_url ); ?>">Browse advisor profiles <span aria-hidden="true">→</span></a>
	</section>
</main>

<?php get_footer();


