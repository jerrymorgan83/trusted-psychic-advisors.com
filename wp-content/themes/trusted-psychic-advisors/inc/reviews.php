<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Sub-criteria definitions ─────────────────────────────────── */
define( 'TPAD_REVIEW_CRITERIA', [
    'accuracy'      => 'Accuracy',
    'helpfulness'   => 'Helpfulness',
    'connection'    => 'Connection',
    'value'         => 'Value for Money',
    'communication' => 'Communication',
]);

/* ── Enable comments on psychic CPT for user reviews ─────────── */
function tpad_enable_psychic_comments() {
    add_post_type_support( 'psychic', 'comments' );
}
add_action( 'init', 'tpad_enable_psychic_comments' );

/* ── Force comments open on all psychic posts ─────────────────── */
function tpad_force_psychic_comments_open( $open, $post_id ) {
    if ( get_post_type( $post_id ) === 'psychic' ) return true;
    return $open;
}
add_filter( 'comments_open', 'tpad_force_psychic_comments_open', 10, 2 );

/* ── Fix cookie consent text + remove URL field ───────────────── */
function tpad_fix_comment_fields( $fields ) {
    if ( get_post_type() === 'psychic' ) {
        unset( $fields['url'] );
        $fields['cookies'] = '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" checked="checked"> <label for="wp-comment-cookies-consent">Save my name and email in this browser for the next time I comment.</label></p>';
    }
    return $fields;
}
add_filter( 'comment_form_default_fields', 'tpad_fix_comment_fields', 20 );

/* ── Build star selector HTML ─────────────────────────────────── */
function tpad_star_selector( $name, $label, $id_prefix ) {
    $h = '<div class="review-form-rating">';
    $h .= '<label>' . esc_html( $label ) . '</label>';
    $h .= '<div class="star-select" data-target="' . esc_attr( $id_prefix ) . '">';
    for ( $i = 1; $i <= 5; $i++ ) {
        $h .= '<button type="button" class="star-btn" data-rating="' . $i . '" aria-label="' . $i . ' star' . ($i>1?'s':'') . '">';
        $h .= '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
        $h .= '</button>';
    }
    $h .= '</div>';
    $h .= '<input type="hidden" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id_prefix ) . '" value="5">';
    $h .= '</div>';
    return $h;
}

/* ── Add rating fields to comment form ────────────────────────── */
function tpad_comment_rating_field( $fields ) {
    if ( get_post_type() !== 'psychic' ) return $fields;

    // Overall rating
    $ratings_html = tpad_star_selector( 'rpr_rating', 'Overall Rating', 'rpr-rating-overall' );

    // Sub-criteria in a grid
    $ratings_html .= '<div class="review-criteria-grid">';
    foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) {
        $ratings_html .= tpad_star_selector( 'rpr_' . $key, $label, 'rpr-rating-' . $key );
    }
    $ratings_html .= '</div>';

    $new_fields = [];
    $new_fields['rpr_ratings'] = $ratings_html;
    foreach ( $fields as $key => $field ) {
        $new_fields[$key] = $field;
    }
    return $new_fields;
}
add_filter( 'comment_form_fields', 'tpad_comment_rating_field' );

/* ── Save ratings as comment meta ─────────────────────────────── */
function tpad_save_comment_rating( $comment_id ) {
    // Overall
    if ( isset( $_POST['rpr_rating'] ) ) {
        $rating = intval( $_POST['rpr_rating'] );
        if ( $rating >= 1 && $rating <= 5 ) {
            add_comment_meta( $comment_id, '_rpr_rating', $rating, true );
        }
    }
    // Sub-criteria
    foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) {
        $field = 'rpr_' . $key;
        if ( isset( $_POST[$field] ) ) {
            $val = intval( $_POST[$field] );
            if ( $val >= 1 && $val <= 5 ) {
                add_comment_meta( $comment_id, '_rpr_' . $key, $val, true );
            }
        }
    }
}
add_action( 'comment_post', 'tpad_save_comment_rating' );

/* ── Display user reviews section ─────────────────────────────── */
function tpad_render_user_reviews( $post_id ) {
    $comments = get_comments([
        'post_id' => $post_id,
        'status'  => 'approve',
        'orderby' => 'comment_date',
        'order'   => 'DESC',
        'number'  => 50,
    ]);

    // Calculate average sub-criteria
    $criteria_totals = [];
    $criteria_counts = [];
    foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) {
        $criteria_totals[$key] = 0;
        $criteria_counts[$key] = 0;
    }
    foreach ( $comments as $c ) {
        foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) {
            $val = (int) get_comment_meta( $c->comment_ID, '_rpr_' . $key, true );
            if ( $val ) {
                $criteria_totals[$key] += $val;
                $criteria_counts[$key]++;
            }
        }
    }
    ?>
    <div class="profile-section" id="user-reviews">
        <h2>User Reviews <span class="review-count">(<?php echo count($comments); ?>)</span></h2>

        <?php if ( $comments && array_sum($criteria_counts) > 0 ) : ?>
        <div class="criteria-summary">
            <?php foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) :
                if ( $criteria_counts[$key] === 0 ) continue;
                $avg = round( $criteria_totals[$key] / $criteria_counts[$key], 1 );
            ?>
            <div class="criteria-summary__item">
                <span class="criteria-summary__label"><?php echo esc_html($label); ?></span>
                <span class="criteria-summary__stars"><?php echo tpad_stars_precise($avg); ?></span>
                <span class="criteria-summary__score"><?php echo number_format($avg, 1); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $comments ) : ?>
        <div class="review-list">
            <?php foreach ( $comments as $c ) :
                $c_rating = (int) get_comment_meta( $c->comment_ID, '_rpr_rating', true );
            ?>
            <div class="review-item">
                <div class="review-item__header">
                    <span class="review-item__author"><?php echo esc_html( $c->comment_author ); ?></span>
                    <?php if ( $c_rating ) : ?>
                    <span class="review-item__rating"><?php echo tpad_stars_precise( $c_rating ); ?></span>
                    <?php endif; ?>
                    <span class="review-item__date"><?php echo date( 'M j, Y', strtotime( $c->comment_date ) ); ?></span>
                </div>
                <?php
                // Show sub-criteria inline if present
                $has_sub = false;
                foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) {
                    $val = (int) get_comment_meta( $c->comment_ID, '_rpr_' . $key, true );
                    if ( $val ) { $has_sub = true; break; }
                }
                if ( $has_sub ) : ?>
                <div class="review-item__criteria">
                    <?php foreach ( TPAD_REVIEW_CRITERIA as $key => $label ) :
                        $val = (int) get_comment_meta( $c->comment_ID, '_rpr_' . $key, true );
                        if ( ! $val ) continue;
                    ?>
                    <span class="review-item__criterion"><?php echo esc_html($label); ?>: <?php echo tpad_stars_precise($val); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <p class="review-item__text"><?php echo esc_html( $c->comment_content ); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="review-empty">No user reviews yet. Be the first to share your experience!</p>
        <?php endif; ?>

        <!-- Review Form -->
        <div class="review-form-wrap" id="leave-review">
            <h3>Leave Your Review</h3>
            <?php
            comment_form([
                'title_reply'          => '',
                'comment_notes_before' => '',
                'comment_notes_after'  => '',
                'label_submit'         => 'Submit Review',
                'class_submit'         => 'btn btn--gold',
                'comment_field'        => '<div class="review-form-field"><label for="comment">Your Experience</label><textarea id="comment" name="comment" rows="4" required placeholder="Share your experience with this psychic..."></textarea></div>',
                'fields' => [
                    'author'  => '<div class="review-form-row"><div class="review-form-field"><label for="author">Name</label><input id="author" name="author" type="text" required placeholder="Your name"></div>',
                    'email'   => '<div class="review-form-field"><label for="email">Email</label><input id="email" name="email" type="email" required placeholder="your@email.com (not published)"></div></div>',
                    'url'     => '',
                    'cookies' => '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" checked="checked"> <label for="wp-comment-cookies-consent">Save my name and email in this browser for the next time I comment.</label></p>',
                ],
            ]);
            ?>
        </div>
    </div>
    <?php
}

/* ── Inline styles & JS for review form ───────────────────────── */
function tpad_review_assets() {
    if ( ! is_singular('psychic') ) return;
    ?>
    <style>
    .review-form-wrap{margin-top:var(--sp-8);padding:var(--sp-6);background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--r-lg)}
    .review-form-wrap h3{font-size:var(--fs-xl);margin-bottom:var(--sp-4)}
    .review-form-rating{margin-bottom:var(--sp-3)}
    .review-form-rating label{display:block;font-size:var(--fs-sm);font-weight:var(--fw-semibold);color:var(--clr-text);margin-bottom:var(--sp-1)}
    .review-criteria-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:0 var(--sp-6);margin-bottom:var(--sp-2);padding:var(--sp-3) var(--sp-4);background:rgba(255,255,255,0.02);border:1px solid var(--clr-border);border-radius:var(--r-md)}
    .review-criteria-grid .review-form-rating{margin-bottom:var(--sp-2)}
    .review-criteria-grid .review-form-rating label{font-size:var(--fs-xs);color:var(--clr-muted)}
    .review-criteria-grid .star-btn svg{width:18px;height:18px}
    .star-select{display:inline-flex;gap:2px}
    .star-btn{background:none;border:none;cursor:pointer;padding:2px;color:var(--clr-border);transition:color .15s}
    .star-btn svg{width:24px;height:24px}
    .star-btn.active{color:var(--clr-accent)}
    .star-btn:hover{color:var(--clr-accent)}
    .review-form-row{display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4)}
    .review-form-field{margin-bottom:var(--sp-4)}
    .review-form-field label{display:block;font-size:var(--fs-sm);font-weight:var(--fw-semibold);color:var(--clr-text);margin-bottom:var(--sp-1)}
    .review-form-field input,.review-form-field textarea{width:100%;padding:10px 14px;background:var(--clr-bg);border:1px solid var(--clr-border);border-radius:var(--r-md);color:var(--clr-text);font-family:var(--ff-body);font-size:var(--fs-sm);transition:border-color .2s}
    .review-form-field input:focus,.review-form-field textarea:focus{outline:none;border-color:var(--clr-accent)}
    .review-form-field textarea{resize:vertical;min-height:100px}
    .review-empty{color:var(--clr-dim);font-size:var(--fs-sm);font-style:italic}
    #respond .form-submit{margin-top:var(--sp-2)}
    /* Criteria summary bar */
    .criteria-summary{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:var(--sp-3);padding:var(--sp-4);background:var(--clr-surface);border:1px solid var(--clr-border);border-radius:var(--r-lg);margin-bottom:var(--sp-5)}
    .criteria-summary__item{display:flex;align-items:center;gap:var(--sp-2)}
    .criteria-summary__label{font-size:var(--fs-xs);color:var(--clr-muted);min-width:90px}
    .criteria-summary__score{font-size:var(--fs-xs);font-weight:var(--fw-bold);color:var(--clr-accent)}
    /* Review sub-criteria inline */
    .review-item__criteria{display:flex;flex-wrap:wrap;gap:var(--sp-3);margin-bottom:var(--sp-2)}
    .review-item__criterion{display:inline-flex;align-items:center;gap:var(--sp-1);font-size:var(--fs-xs);color:var(--clr-dim)}
    .review-item__criterion .stars-precise svg{width:12px;height:12px}
    @media(max-width:768px){
        .review-form-row{grid-template-columns:1fr}
        .review-criteria-grid{grid-template-columns:1fr 1fr}
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.star-select').forEach(function(wrap){
            var target=wrap.dataset.target;
            var btns=wrap.querySelectorAll('.star-btn');
            var input=document.getElementById(target);
            if(!input)return;
            function setRating(n){
                input.value=n;
                btns.forEach(function(b,i){b.classList.toggle('active',i<n)});
            }
            setRating(5);
            btns.forEach(function(b){
                b.addEventListener('click',function(){setRating(parseInt(this.dataset.rating))});
            });
        });
    });
    </script>
    <?php
}
add_action( 'wp_head', 'tpad_review_assets' );
