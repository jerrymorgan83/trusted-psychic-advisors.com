<?php
/**
 * Template: Single Review Page
 * Template Name: Service Review
 * Unique design: archetype=Magazine hero=E ratings=4 tables=V faq=F1 order=S5
 */
get_header();
while ( have_posts() ) : the_post();
    $pid        = get_the_ID();
    $score      = (float) get_post_meta( $pid, '_review_score', true ) ?: 4.5;
    $website    = get_post_meta( $pid, '_review_website', true );
    $tagline    = get_post_meta( $pid, '_review_tagline', true );
    $pricing    = get_post_meta( $pid, '_review_pricing', true );
    $best       = get_post_meta( $pid, '_review_best_for', true );
    $pros       = json_decode( get_post_meta( $pid, '_review_pros', true ) ?: '[]', true );
    $cons       = json_decode( get_post_meta( $pid, '_review_cons', true ) ?: '[]', true );
    $feats      = json_decode( get_post_meta( $pid, '_review_features', true ) ?: '[]', true );
    $ratings    = json_decode( get_post_meta( $pid, '_review_ratings', true ) ?: '[]', true );
    $pricetable = json_decode( get_post_meta( $pid, '_review_pricing_table', true ) ?: '[]', true );
    $comptable  = json_decode( get_post_meta( $pid, '_review_comparison', true ) ?: '[]', true );
    $sources    = json_decode( get_post_meta( $pid, '_review_sources', true ) ?: '[]', true );
    $ataglance  = json_decode( get_post_meta( $pid, '_review_at_glance', true ) ?: '[]', true );
    $faq        = json_decode( get_post_meta( $pid, '_review_faq', true ) ?: '[]', true );
    $reviewer   = get_post_meta( $pid, '_review_reviewer', true ) ?: 'Editorial Team';
    $testedhrs  = get_post_meta( $pid, '_review_tested_hours', true );
    $testeddate = get_post_meta( $pid, '_review_tested_date', true );
    $updated    = get_the_modified_date( 'F j, Y', $pid );
    $full       = floor($score);
    $hero_img   = get_the_post_thumbnail_url( $pid, 'full' );
    $chips      = ['2-yr history required', 'Entity verified', 'Clean dispute record'];
?>

<style>
/* shared */
.tpad .sec-head{font-size:var(--fs-2xl,1.5rem);margin:var(--sp-8,32px) 0 var(--sp-4,16px);}
.tpad .hero-score-row{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
.tpad .hero-score{font-size:1.5rem;font-weight:800;color:var(--clr-gold,#d4a843);}
.tpad .hero-tag{color:var(--clr-muted);font-size:1.1rem;margin:8px 0 18px;}
.tpad .trust-chips{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0;}
.tpad .trust-chip{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;background:var(--clr-primary-tint,rgba(0,0,0,.06));color:var(--clr-primary-ink,var(--clr-primary,#333));font-size:.76rem;font-weight:700;border:1px solid var(--clr-border);}
.tpad .hero-meta{font-size:.88rem;color:var(--clr-muted);margin:14px 0;}
.tpad .hero-meta strong{color:var(--clr-text);font-weight:600;}
.tpad .hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px;}
.tpad .method-box{display:flex;gap:18px;align-items:flex-start;background:var(--clr-surface-hover,rgba(0,0,0,.03));border:1px solid var(--clr-border);border-left:4px solid var(--clr-gold,#d4a843);border-radius:var(--r-md,10px);padding:18px 22px;margin:var(--sp-8,32px) 0;font-size:.9rem;color:var(--clr-muted);line-height:1.6;}
.tpad .method-box strong{color:var(--clr-text);}
.tpad .method-icon{flex-shrink:0;width:32px;height:32px;border-radius:50%;background:var(--clr-primary,#333);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;}
.tpad .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:var(--sp-6,24px) 0;}
.tpad .stat{background:var(--clr-surface,#fff);border:1px solid var(--clr-border);border-radius:var(--r-md,10px);padding:18px;text-align:center;}
.tpad .stat-k{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--clr-muted);font-weight:700;margin-bottom:6px;}
.tpad .stat-v{font-size:1.3rem;font-weight:800;color:var(--clr-gold,#d4a843);}
.tpad .at-glance{display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--clr-border);border-radius:var(--r-lg,14px);overflow:hidden;background:var(--clr-surface,#fff);margin:var(--sp-4,16px) 0 var(--sp-8,32px);}
.tpad .at-item{padding:16px 20px;border-bottom:1px solid var(--clr-border);border-right:1px solid var(--clr-border);}
.tpad .at-item:nth-child(2n){border-right:0;}
.tpad .at-item:nth-last-child(-n+2){border-bottom:0;}
.tpad .at-k{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--clr-muted);font-weight:700;margin-bottom:4px;}
.tpad .at-v{font-size:1rem;color:var(--clr-text);font-weight:500;}
.tpad .pc-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:var(--sp-6,24px) 0;}
.tpad .pc-col{background:var(--clr-surface,#fff);border:1px solid var(--clr-border);border-radius:var(--r-lg,14px);padding:22px;border-top:4px solid var(--clr-green,#6B9D87);}
.tpad .pc-col--neg{border-top-color:var(--clr-red,#C57A78);}
.tpad .pc-col ul{list-style:none;padding:0;}
.tpad .pc-li{display:flex;align-items:flex-start;gap:8px;padding:6px 0;font-size:.9rem;}
.tpad .pc-check{color:var(--clr-green,#6B9D87);font-weight:bold;}
.tpad .pc-cross{color:var(--clr-red,#C57A78);font-weight:bold;}
.tpad .feat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;margin:var(--sp-4,16px) 0 var(--sp-8,32px);}
.tpad .feat{background:var(--clr-surface,#fff);border:1px solid var(--clr-border);border-radius:var(--r-md,10px);padding:18px;}
.tpad .feat h4{margin-bottom:6px;color:var(--clr-primary-ink,var(--clr-primary,#333));}
.tpad .feat p{color:var(--clr-muted);font-size:.88rem;margin:0;}
.tpad .body-content{max-width:820px;}
.tpad .body-content h2{margin-top:var(--sp-8,32px);}
.tpad .body-content p,.tpad .body-content li{color:var(--clr-text);line-height:1.75;}
.tpad .byline{display:flex;gap:14px;align-items:center;padding:16px;border:1px solid var(--clr-border);border-radius:var(--r-md,10px);background:var(--clr-surface,#fff);margin:var(--sp-5,20px) 0;}
.tpad .byline-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--clr-primary,#333),var(--clr-gold,#d4a843));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0;}
.tpad .byline strong{display:block;}
.tpad .byline-sub{font-size:.84rem;color:var(--clr-muted);}
.tpad .src-box{background:var(--clr-surface-hover,rgba(0,0,0,.03));border:1px solid var(--clr-border);border-radius:var(--r-lg,14px);padding:24px;margin:var(--sp-8,32px) 0;}
.tpad .src-box ol{padding-left:20px;margin:0;}
.tpad .src-box li{padding:8px 0;font-size:.88rem;color:var(--clr-muted);line-height:1.6;}
.tpad .src-box li strong{color:var(--clr-text);}
.tpad .src-box a{color:var(--clr-primary-ink,var(--clr-primary,#333));text-decoration:underline;}
.tpad .cta-bottom{text-align:center;margin:var(--sp-12,48px) 0;padding:var(--sp-8,32px);background:linear-gradient(135deg,var(--clr-primary-tint,rgba(0,0,0,.04)),transparent);border-radius:var(--r-xl,18px);}
@media(max-width:720px){.tpad .at-glance,.tpad .pc-grid{grid-template-columns:1fr;}.tpad .at-item{border-right:0!important;}.tpad .rat-row{grid-template-columns:130px 1fr 50px!important;gap:10px;}}
/* Hero E: centered with small thumbnail */
.tpad .hero-wrap{padding:var(--sp-12,48px) 0;text-align:center;}
.tpad .hero-media{width:140px;height:140px;margin:0 auto var(--sp-5,20px);border-radius:50%;overflow:hidden;border:4px solid var(--clr-primary,currentColor);box-shadow:0 8px 24px rgba(0,0,0,.12);}
.tpad .hero-media img{width:100%;height:100%;object-fit:cover;}
.tpad .hero-body{max-width:680px;margin:0 auto;}
/* Ratings 4: filled stars per row */
.tpad .rat-box{background:var(--clr-surface-hover,var(--clr-surface,#fff));border-radius:var(--r-lg,14px);padding:var(--sp-6,24px);margin:var(--sp-8,32px) 0;}
.tpad .rat-row{display:grid;grid-template-columns:1fr auto;gap:var(--sp-4,16px);align-items:center;padding:var(--sp-3,12px) 0;border-bottom:1px dashed var(--clr-border);}
.tpad .rat-row:last-child{border-bottom:0;}
.tpad .rat-stars{letter-spacing:2px;color:var(--clr-gold,#d4a843);font-size:1.2rem;}
.tpad .rat-stars .off{opacity:.25;}
/* Tables V: minimalist (dividers only) */
.tpad .tbl-wrap{margin:var(--sp-6,24px) 0;}
.tpad .tbl{width:100%;border-collapse:collapse;font-size:.92rem;}
.tpad .tbl caption{text-align:left;padding-bottom:var(--sp-4,16px);font-weight:700;font-size:1.15rem;border-bottom:2px solid var(--clr-text,#333);margin-bottom:var(--sp-3,12px);}
.tpad .tbl th{padding:10px 4px;text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--clr-muted);border-bottom:1px solid var(--clr-text,#333);}
.tpad .tbl td{padding:14px 4px;border-bottom:1px solid var(--clr-border);}
.tpad .tbl tr.hl td{border-bottom-color:var(--clr-gold,#d4a843);font-weight:600;color:var(--clr-primary-ink,var(--clr-primary,#333));}
/* FAQ F1: accordion */
.tpad .faq-wrap details{background:var(--clr-surface,#fff);border:1px solid var(--clr-border);border-radius:var(--r-md,10px);padding:14px 18px;margin-bottom:10px;}
.tpad .faq-wrap summary{cursor:pointer;font-weight:700;color:var(--clr-text);list-style:none;display:flex;justify-content:space-between;align-items:center;}
.tpad .faq-wrap summary::-webkit-details-marker{display:none;}
.tpad .faq-wrap summary::after{content:'+';font-size:1.4em;color:var(--clr-primary,#333);}
.tpad .faq-wrap details[open] summary::after{content:'\2212';}
.tpad .faq-wrap details[open]{box-shadow:0 4px 16px rgba(0,0,0,.06);}
.tpad .faq-wrap p{margin-top:10px;color:var(--clr-muted);font-size:.9rem;line-height:1.7;}
</style>

<div class="tpad">

<section class="hero-wrap"><div class="wrap"><?php if($hero_img): ?><div class="hero-media"><img src="<?php echo esc_url($hero_img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="eager"/></div><?php endif; ?><div class="hero-body"><div class="hero-score-row">
<?php if(function_exists("tpad_stars")) echo tpad_stars($full); ?>
<span class="hero-score"><?php echo number_format($score,1); ?>/5</span>
</div>
<h1><?php the_title(); ?></h1>
<?php if($tagline): ?><p class="hero-tag"><?php echo esc_html($tagline); ?></p><?php endif; ?>
<div class="trust-chips"><?php foreach($chips as $ch): ?><span class="trust-chip">&#10003; <?php echo esc_html($ch); ?></span><?php endforeach; ?></div>
<div class="hero-meta">By <strong><?php echo esc_html($reviewer); ?></strong> &middot; Updated <strong><?php echo esc_html($updated); ?></strong><?php if($testedhrs): ?> &middot; <strong><?php echo esc_html($testedhrs); ?></strong> tested<?php endif; ?></div>
<div class="hero-actions">
<?php if($website): ?><a href="<?php echo esc_url($website); ?>" class="btn btn--gold btn--lg" target="_blank" rel="nofollow noopener sponsored">Visit <?php the_title(); ?> &rarr;</a><?php endif; ?>
<a href="#verdict" class="btn btn--ghost">Read verdict</a>
</div></div></div></section>

<section class="sec">
<div class="wrap">

<div class="method-box"><div class="method-icon">i</div><div><strong>Methodology.</strong> &ldquo;Trusted&rdquo; status requires a two-year continuous trading history, verified corporate entity, and a clean record with card-network dispute systems.</div></div>

<div class="stat-grid">
<div class="stat"><span class="stat-k">Editor Score</span><span class="stat-v"><?php echo number_format($score,1); ?>/5</span></div>
<?php if($pricing): ?><div class="stat"><span class="stat-k">Pricing</span><span class="stat-v"><?php echo esc_html($pricing); ?></span></div><?php endif; ?>
<?php if($best): ?><div class="stat"><span class="stat-k">Best For</span><span class="stat-v"><?php echo esc_html($best); ?></span></div><?php endif; ?>
</div>

<?php if($pros || $cons): ?>
<div class="pc-grid">
<?php if($pros): ?><div class="pc-col"><h3>What We Like</h3><ul><?php foreach($pros as $p): ?><li class="pc-li"><span class="pc-check">&check;</span><?php echo esc_html($p); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if($cons): ?><div class="pc-col pc-col--neg"><h3>Could Improve</h3><ul><?php foreach($cons as $c): ?><li class="pc-li"><span class="pc-cross">&times;</span><?php echo esc_html($c); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
</div>
<?php endif; ?>

<?php if($ataglance): ?>
<h2 class="sec-head">At a Glance</h2>
<div class="at-glance"><?php foreach($ataglance as $ag): ?>
<div class="at-item"><span class="at-k"><?php echo esc_html($ag['k']??''); ?></span><span class="at-v"><?php echo wp_kses_post($ag['v']??''); ?></span></div>
<?php endforeach; ?></div>
<?php endif; ?>

<div class="rat-box"><h3 style="margin-bottom:var(--sp-5,20px)">Category Stars</h3>
<?php foreach($ratings as $r): $v=(float)($r['score']??0); $full=(int)floor($v); ?>
<div class="rat-row"><div class="rat-k"><?php echo esc_html($r['label']??''); ?><small style="display:block;font-size:.75rem;color:var(--clr-muted);margin-top:2px"><?php echo number_format($v,1); ?> / 5</small></div>
<div class="rat-stars"><?php for($i=1;$i<=5;$i++){echo '<span'.($i>$full?' class="off"':'').'>&#9733;</span>';} ?></div></div>
<?php endforeach; ?></div>

<?php if($feats): ?>
<h2 class="sec-head">Standout Features</h2>
<div class="feat-grid"><?php foreach($feats as $ft): ?>
<div class="feat"><h4><?php echo esc_html($ft['title']??''); ?></h4><p><?php echo esc_html($ft['desc']??''); ?></p></div>
<?php endforeach; ?></div>
<?php endif; ?>

<?php if($pricetable): ?>
<h2 class="sec-head" id="pricing">Pricing Breakdown</h2>
<div class="tbl-wrap"><table class="tbl">
<caption>Verified packages &mdash; retested <?php echo esc_html($testeddate ?: $updated); ?></caption>
<thead><tr><?php foreach(($pricetable['headers']??[]) as $h): ?><th><?php echo esc_html($h); ?></th><?php endforeach; ?></tr></thead>
<tbody><?php foreach(($pricetable['rows']??[]) as $row): ?>
<tr<?php echo !empty($row['highlight'])?' class="hl"':''; ?>><?php foreach(($row['cells']??$row) as $c): ?><td><?php echo wp_kses_post($c); ?></td><?php endforeach; ?></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<?php if($comptable): ?>
<h2 class="sec-head" id="comparison">How It Compares</h2>
<div class="tbl-wrap"><table class="tbl">
<caption>Side-by-side with platforms we track this quarter</caption>
<thead><tr><?php foreach(($comptable['headers']??[]) as $h): ?><th><?php echo esc_html($h); ?></th><?php endforeach; ?></tr></thead>
<tbody><?php foreach(($comptable['rows']??[]) as $row): ?>
<tr<?php echo !empty($row['highlight'])?' class="hl"':''; ?>><?php foreach(($row['cells']??$row) as $c): ?><td><?php echo wp_kses_post($c); ?></td><?php endforeach; ?></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>

<div class="body-content" id="verdict">
<div class="byline"><div class="byline-avatar"><?php echo esc_html(strtoupper(substr($reviewer,0,1))); ?></div>
<div><strong><?php echo esc_html($reviewer); ?></strong><span class="byline-sub"><?php the_title(); ?> review &middot; <?php echo esc_html($updated); ?><?php if($testedhrs): ?> &middot; <?php echo esc_html($testedhrs); ?> tested<?php endif; ?></span></div></div>
<h2 class="sec-head">Our Full Review</h2>
<?php the_content(); ?>
</div>

<?php if($faq): ?>
<h2 class="sec-head" id="faq">Frequently Asked Questions</h2>
<div class="faq-wrap"><?php foreach($faq as $qa): ?><details><summary><?php echo esc_html($qa['q']??''); ?></summary><p><?php echo wp_kses_post($qa['a']??''); ?></p></details><?php endforeach; ?></div>
<?php endif; ?>

<?php if($sources): ?>
<div class="src-box"><h3>Sources &amp; References</h3>
<ol><?php foreach($sources as $s): ?><li>
<?php if(!empty($s['url'])): ?><a href="<?php echo esc_url($s['url']); ?>" target="_blank" rel="nofollow noopener"><strong><?php echo esc_html($s['title']??''); ?></strong></a><?php else: ?><strong><?php echo esc_html($s['title']??''); ?></strong><?php endif; ?>
<?php if(!empty($s['note'])): ?> &mdash; <?php echo esc_html($s['note']); ?><?php endif; ?>
<?php if(!empty($s['accessed'])): ?> <em>Accessed <?php echo esc_html($s['accessed']); ?>.</em><?php endif; ?>
</li><?php endforeach; ?></ol></div>
<?php endif; ?>

<div class="cta-bottom">
<h2 style="margin-top:0">Ready to test <?php the_title(); ?>?</h2>
<p style="color:var(--clr-muted);margin-bottom:18px">Open our reviewer-verified link in a new tab to see the current welcome offer.</p>
<?php if($website): ?><a href="<?php echo esc_url($website); ?>" class="btn btn--gold btn--lg" target="_blank" rel="nofollow noopener sponsored">Visit <?php the_title(); ?> &rarr;</a><?php endif; ?>
</div>

<?php
$schema = ['@context'=>'https://schema.org','@graph'=>[[
  '@type'=>'Review','itemReviewed'=>['@type'=>'Service','name'=>get_the_title(),'url'=>$website ?: ''],
  'reviewRating'=>['@type'=>'Rating','ratingValue'=>number_format($score,1),'bestRating'=>'5'],
  'author'=>['@type'=>'Organization','name'=>$reviewer],
  'datePublished'=>get_the_date('c',$pid),'dateModified'=>get_the_modified_date('c',$pid),
]]];
if($faq) $schema['@graph'][]=['@type'=>'FAQPage','mainEntity'=>array_map(function($qa){return ['@type'=>'Question','name'=>$qa['q']??'','acceptedAnswer'=>['@type'=>'Answer','text'=>wp_strip_all_tags($qa['a']??'')]];},$faq)];
echo "\n".'<script type="application/ld+json">'.wp_json_encode($schema,JSON_UNESCAPED_SLASHES).'</script>';
?>

</div>
</section>

</div>

<?php endwhile; get_footer(); ?>
