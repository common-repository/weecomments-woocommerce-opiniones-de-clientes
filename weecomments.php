<?php 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: weeComments
Plugin URI: weecomments.com
Description: Plugin de weeComments para WooCommerce v2.2 o posterior
Version: 3.0.0
Author: <a href="https://weecomments.com">weeComments</a>
*/


require_once('classes/wee_weemailClass.php');

add_action('wp_print_styles', 'weecomments_styles'); 
add_action('wp_footer', 'wee_javascripts');
add_action('admin_enqueue_scripts', 'weecomments_admin_styles');

register_activation_hook(__FILE__, 'weecomments_install');
register_deactivation_hook(__FILE__,'weecomments_uninstall');

function weecomments_install()
{
	wee_createWeecommentsDatabase();
}

function weecomments_uninstall()
{
	delete_option( 'weecomments_options'); 	
}

function weecomments_admin_styles()
{
    wp_enqueue_style( 'back_css', plugins_url( '/css/back.css', __FILE__ ));
}

//Añade el css
function weecomments_styles()
{
    wp_enqueue_style( 'weecomments_external_css', "https://weecomments.com/css/style_webservice.css");
    wp_enqueue_style( 'inner_css', plugins_url('/css/style.css', __FILE__) );
}

//Añade el javascript
function wee_javascripts()
{
	$weecomments_options = get_option('weecomments_options');
	echo '
	<script type="text/javascript">
		var $ = jQuery.noConflict();
		$("document").ready(function(){
			$.ajax({type:"GET",url:"https://weecomments.com/es/webservice/show_small?callback=lol&id_shop='.$weecomments_options['WEE_ID_SHOP'].'", dataType: "jsonp",success: function(resp){$(".weecomments").html(resp["widget"]);},error: function(e){}});
			
			$.ajax({type:"GET",url:"https://weecomments.com/es/webservice/show_product?callback=lol&id_shop='.$weecomments_options['WEE_ID_SHOP'].'&id_product='.get_the_ID().'", dataType: "jsonp",success: function(resp){$("#wee_prod").html(resp["widget"]);},error: function(e){}});
		});
	</script>
	';
}


//SI ES PREMIUM MUESTRA LOS TABS DE OPINIONES EN LA PÁGINA DE PRODUCTO
$weecomments_options = get_option('weecomments_options');

//Rewrite Reviews Tab
add_filter( 'woocommerce_product_tabs', 'woo_custom_reviews_tab', 120 );
function woo_custom_reviews_tab( $tabs ) {
	$tabs['reviews']['callback'] = 'wee_custom_reviews_tab_content';	// Custom description callback
	$tabs['reviews']['title'] = __( 'Opiniones' );				// Rename the reviews tab
	return $tabs;
}
 
function wee_custom_reviews_tab_content()
{
	echo '<div id="wee_prod"></div>	';
}


if ($weecomments_options['WEE_SUBSCRIPTION'] > 0)
{
	add_filter( 'woocommerce_single_product_summary', 'wee_product_rich_snippets', 36 );
	function wee_product_rich_snippets()
	{
		global $post;
		$id_product = $post->ID;
		$product_info = wee_getProduct($id_product);
        $WEE_RATING_TYPE = $weecomments_options['WEE_RATING_TYPE'];
		include 'views/extra_right.php';
	}
    
    add_filter( 'woocommerce_after_shop_loop_item_title', 'wee_product_list', 5 );
    function wee_product_list()
    {
        global $post;
		$id_product = $post->ID;
		$product_info = wee_getProduct($id_product);
		include 'views/product_list.php';
    }
     
}


	
	
add_action( 'widgets_init', create_function('', 'return wee_register_widgets();') );
function wee_register_widgets()
{
	include_once('widgets/class-wee-widget.php');
	register_widget( 'wee_Widget' );
}

add_action('admin_menu', 'wee_plugin_admin_add_page');
function wee_plugin_admin_add_page()
{
	add_options_page('Custom Plugin Page', 'weeComments', 'manage_options', 'plugin', 'wee_plugin_configuration_page');
}

//PÁGINA DE CONFIGURACIÓN DEL PLUGIN
function wee_plugin_configuration_page()
{
	global $wpdb;
	$message = '';
    	
	if (isset($_POST['WEE_API_KEY']))
		$message = wee_updateShopInfo();
	$weecomments_options = get_option('weecomments_options');

	if ($message)
		echo '<h2 class="green">'.$message.'</h2>';
	
	if ($weecomments_options['WEE_API_KEY']) {
		switch ($weecomments_options['WEE_SUBSCRIPTION']) {
			case 2:
				$subscription = 'weeComments Pro';
				break;
			case 3:
				$subscription = 'weeComments Premium';
				break;
			default:
				$subscription = 'weeComments Free';
				break;
		}
		include 'views/settings.php';
        
	} else
		include 'views/login.php';

}


/* Query Vars */
add_filter( 'query_vars', 'weecomments_register_query_var' );
function weecomments_register_query_var( $vars )
{
    $vars[] = 'weecomments_page';
    return $vars;
}

/* Template Include MAILER */
add_filter('template_include', 'weecomments_template_include', 1, 1);
function weecomments_template_include($template)
{
    global $wp_query;
    //$weecomments_page_value = $wp_query->query_vars['weecomments_page'];
    if (isset($_GET['weecomments_page']) && isset($_GET['weecomments_page']) == "mailer") {
		require_once(plugin_dir_path(__FILE__).'classes/wee_mailerClass.php');
		$mailerClass = new wee_mailerClass();
		$result_send = $mailerClass->wee_executeWeecomments();
		echo $result_send;
        return plugin_dir_path(__FILE__).'views/test.php';
    }

    return $template;
}


	function wee_updateShopInfo()
	{
        if (isset($_POST['WEE_API_KEY']) && (strlen($_POST['WEE_API_KEY']) == 32 || strlen($_POST['WEE_API_KEY']) == 64)) {
            $shop_info = file_get_contents('http://weecomments.com/wsrest/module_shop_info?api='.$_POST['WEE_API_KEY']);
            $shop_info = new SimpleXMLElement($shop_info);
            $api_key = $_POST['WEE_API_KEY'];
        }
        
		//Si el usuario y pass son correctos, actualiza los datos de la tienda
		if($shop_info->id_shop > 0) {
			$weecomments_options['WEE_API_KEY'] 		= $api_key;
			$weecomments_options['WEE_ID_SHOP'] 		= trim($shop_info->id_shop);
			$weecomments_options['WEE_URL'] 			= trim($shop_info->friendly_url);
			$weecomments_options['WEE_SECURITY_KEY'] 	= trim($shop_info->security_key);
			$weecomments_options['WEE_SUBSCRIPTION'] 	= trim($shop_info->subscription);
			$weecomments_options['WEE_SUBJECT'] 		= trim($shop_info->mail_subject);
			$weecomments_options['WEE_TEXT'] 			= trim($shop_info->mail_text);
			$weecomments_options['WEE_EMAIL'] 			= trim($shop_info->email_reply);
			$weecomments_options['WEE_MAIL_FROM'] 		= trim($shop_info->mail_from);
			$weecomments_options['WEE_MAIL_TO'] 		= trim($shop_info->mail_to);
			$weecomments_options['WEE_MAIL_LIMIT'] 		= trim($shop_info->mail_limit);
			$weecomments_options['WEE_SHOP_AVG_RATING'] = round((float)$shop_info->avg_rating * 2, 2);
			$weecomments_options['WEE_SHOP_NUM_RATINGS'] = trim($shop_info->num_ratings);
			$weecomments_options['WEE_LANG'] 			= trim($shop_info->default_language);
            $weecomments_options['WEE_LOGO_URL'] 		= trim($shop_info->logo_url);
            $weecomments_options['WEE_RATING_TYPE'] 	= trim($shop_info->rating_type);
			update_option( 'weecomments_options', $weecomments_options); 
			$message = 'Successful';
            
		} else {
            
			$weecomments_options['WEE_API_KEY'] = '';
			$weecomments_options['WEE_ID_SHOP'] = '';
			$weecomments_options['WEE_URL'] = '';
			$weecomments_options['WEE_SECURITY_KEY'] = '';
			$weecomments_options['WEE_SUBSCRIPTION'] = '';
			$weecomments_options['WEE_SUBJECT'] = '';
			$weecomments_options['WEE_TEXT'] = '';
			$weecomments_options['WEE_EMAIL'] = '';
			$weecomments_options['WEE_MAIL_FROM'] = '';
			$weecomments_options['WEE_MAIL_TO'] = '';
			$weecomments_options['WEE_MAIL_LIMIT'] = '';
			$weecomments_options['WEE_SHOP_AVG_RATING'] = '';
			$weecomments_options['WEE_SHOP_NUM_RATINGS'] = '';
			$weecomments_options['WEE_LANG'] = '';
            $weecomments_options['WEE_LOGO_URL'] = '';
            $weecomments_options['WEE_RATING_TYPE'] = '';
			update_option( 'weecomments_options', $weecomments_options); 
			$message = 'email or password incorrect';
		}
		return $message;
	}

	function wee_createWeecommentsDatabase()
	{
		global $wpdb;
		$structure2 = "CREATE TABLE IF NOT EXISTS `wee_products` (
		  `id_product` BIGINT(20) NOT NULL,
		  `num_ratings` INT(10) NOT NULL,
		  `avg_rating` FLOAT(5) NOT NULL,
		  PRIMARY KEY (`id_product`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		$wpdb->query($structure2);
		
		$structure3 = 'CREATE TABLE IF NOT EXISTS `wee_categories` (
		  `id_category` BIGINT(20) NOT NULL,
		  `num_ratings` INT(10) NOT NULL,
		  `avg_rating` FLOAT(5) NOT NULL,
		  PRIMARY KEY (`id_category`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		$wpdb->query($structure3);
        
        $structure4 = "
        CREATE TABLE IF NOT EXISTS `wee_comments`(
              `id_product` BIGINT(20) NOT NULL,
              `id_comment` BIGINT(20) NOT NULL,
              `id_order` VARCHAR(11) NOT NULL,
              `id_shop` BIGINT(20) NOT NULL,
              `customer_name` VARCHAR(30) NOT NULL,
              `customer_lastname` VARCHAR(50) NOT NULL,
              `email` VARCHAR(100) NOT NULL,
              `IP` VARCHAR(45) NOT NULL,
              `date` DATETIME NOT NULL,
              `comment` VARCHAR(5000) NOT NULL,
              `rating` FLOAT(5) NOT NULL,
              `rating1` INT(1) NOT NULL,
              `rating2` INT(1) NOT NULL,
              `rating3` INT(1) NOT NULL,
              `status` INT(1) NOT NULL,
              `lang` VARCHAR(3) NOT NULL,
              `external` VARCHAR(20) NOT NULL,
              PRIMARY KEY (`id_product`, `id_comment`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $wpdb->query($structure4);
        
        
        $structure5 = "
        CREATE TABLE IF NOT EXISTS `wee_comments_replies` (
        `id_reply` BIGINT(20) NOT null,
        `id_comment` BIGINT(20) NOT null,
        `reply` VARCHAR(5000) NOT null,
        `date` DATETIME NOT null,
        PRIMARY KEY (`id_reply`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $wpdb->query($structure5);
        
        
	}
	
	function wee_getProduct($id_product)
	{	
		global $wpdb;
		$sql = "SELECT * FROM wee_products WHERE id_product = '$id_product'";
		$result = $wpdb->get_row($sql);
		if ($result)
			return $result;
		else
			return NULL;
	}



add_shortcode("weecomments_wide","weecomments_function_wide");
function weecomments_function_wide() {
    $weecomments_options = get_option('weecomments_options');
    return '<div class="weecomments-widget-wide"></div>
    <script type="text/javascript">var $ = jQuery.noConflict();$("document").ready(function(){$.ajax({type:"GET",url:"https://weecomments.com/es/webservice/show_widget_wide?callback=lol&id_shop='.$weecomments_options['WEE_ID_SHOP'].'&widget=2", dataType: "jsonp",success: function(resp){$(".weecomments-widget-wide").html(resp["widget"]);},error: function(e){}});});</script>
    <p class="wee_align_center"><small><a target="_blank" href="https://weecomments.com/es">opiniones por <span class="wee_colour">weeComments</span></a></small></p>';
}

add_shortcode("weecomments_float","weecomments_function_float");
function weecomments_function_float() {
    $weecomments_options = get_option('weecomments_options');
    return '<div class="weecomments_float"></div><script type="text/javascript">var $ = jQuery.noConflict();$("document").ready(function(){$.ajax({type:"GET",url:"https://weecomments.com/es/webservice/show_widget_float?callback=lol&id_shop='.$weecomments_options['WEE_ID_SHOP'].'&widget=1&left=100",dataType:"jsonp",success: function(resp){$(".weecomments_float").html(resp["widget"]);},error: function(e){}});$(".weecomments_float").delegate("#wee-floating-1","hover",function(event){if(event.type=="mouseenter") {$("#wee-floating-1").filter(":not(:animated)").animate({bottom: "0px"}, 800, function(){});}else{$("#wee-floating-1").animate({bottom:"-202px"},400,function(){});}});});</script>';
}

add_shortcode("weecomments_general","weecomments_function_general");
function weecomments_function_general() {
    $weecomments_options = get_option('weecomments_options');
    return '<a target="_blank" href="https://weecomments.com/es/opiniones/'.$weecomments_options['WEE_URL'].'"><div class="weecomments"></div></a><script type="text/javascript">var $ = jQuery.noConflict();$("document").ready(function(){$.ajax({type:"GET",url:"https://weecomments.com/es/webservice/show_small?callback=lol&id_shop='.$weecomments_options['WEE_ID_SHOP'].'", dataType: "jsonp",success: function(resp){$(".weecomments").html(resp["widget"]);},error: function(e){}});});</script><p class="wee_align_center"><small><a target="_blank" href="https://weecomments.com/es">opiniones por <span class="wee_colour">weeComments</span></a></small></p>';
}
	

?>