<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (isset($product_info->avg_rating) && $product_info->avg_rating) {

$avg_rating = $product_info->avg_rating;

    for ($i=1; $i<=5; $i++) {
        switch ($avg_rating) {
            case ($avg_rating >= $i):
                $stars .= '<span class="ministar ministar_active5"></span>';
                break;
            case ($avg_rating >= (($i-1)+0.75)):
                $stars .= '<span class="ministar ministar_active4"></span>';
                break;
            case ($avg_rating >= (($i-1)+0.5)):
                $stars .= '<span class="ministar ministar_active3"></span>';
                break;
            case ($avg_rating >= (($i-1)+0.25)):
                $stars .= '<span class="ministar ministar_active2"></span>';
                break;
            case ($avg_rating < $i):
                $stars .= '<span class="ministar ministar_active1"></span>';
                break;
            default:
                $stars .= '<span class="ministar ministar_active1"></span>';
                break;
        }
    }

?>

<div class="wee_stars_container"><?=$stars?></div>

<?php } ?>