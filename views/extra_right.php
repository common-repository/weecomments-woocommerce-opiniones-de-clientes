<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( isset($product_info->avg_rating) ) {
	$avg_rating = round($product_info->avg_rating, 1);
	$num_ratings = $product_info->num_ratings;
} else {
	$avg_rating = null;
	$num_ratings = null;
}

$stars = '';

for ($i=1; $i<=5; $i++) {
    switch ($avg_rating) {
        case ($avg_rating >= $i):
            $stars .= '<span class="midstar midstar_active5"></span>';
            break;
        case ($avg_rating >= (($i-1)+0.75)):
            $stars .= '<span class="midstar midstar_active4"></span>';
            break;
        case ($avg_rating >= (($i-1)+0.5)):
            $stars .= '<span class="midstar midstar_active3"></span>';
            break;
        case ($avg_rating >= (($i-1)+0.25)):
            $stars .= '<span class="midstar midstar_active2"></span>';
            break;
        case ($avg_rating < $i):
            $stars .= '<span class="midstar midstar_active1"></span>';
            break;
        default:
            $stars .= '<span class="midstar midstar_active1"></span>';
            break;
    }
}

if ($avg_rating > 0): ?>
<div class="wee_rating_container">
	
    <a href="#tab-reviews" class="wee-reviews-trigger">
    <div class="wee_stars_container"><?=$stars?></div>
	</a>
    <br />
    <a href="#tab-reviews" class="wee-reviews-trigger weecomments-link"><small>ver las opiniones</small></a>
    
	<small>
    <div xmlns:v="http://rdf.data-vocabulary.org/#" typeof="v:Review-aggregate">
       <span property="v:itemreviewed"><strong><?=get_the_title()?></strong></span> tiene una valoración media de 
       <span rel="v:rating">
          <span typeof="v:Rating">
            <strong>
                
             <?php if($WEE_RATING_TYPE == 5):?>
             <span property="v:average"><?=$avg_rating?></span>
             sobre
             <span property="v:best">5</span>
             <?php else:?>
             <span property="v:average"><?=round($avg_rating * 2, 1)?></span>
             sobre
             <span property="v:best">10</span>
             <?php endif;?>
            </strong>
          </span>
       </span>
       basada en <strong><span property="v:votes"><?=$num_ratings?></span> opiniones de clientes.</strong>
    </div>
    </small>
</div>

<script type="text/javascript">
var $ = jQuery.noConflict();
$("document").ready(function(){
	$(".wee-reviews-trigger").click(function() {
		$(".reviews_tab a").click();
	});
});
</script>

<?php endif;?>