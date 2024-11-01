<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class wee_mailerClass
{
	function __construct()
	{
		require_once('wee_weemailClass.php');
		$this->weecomments_options = get_option('weecomments_options');
	}
	
	function wee_executeWeecomments()
	{	
		//Actualizamos los valores de la tienda
		if (isset($this->weecomments_options['WEE_API_KEY'])) {
			$this->wee_updateShopInfo();
			//$this->wee_updateLangsConfiguration();
			$mailer_result 	= $this->wee_sendScheduledMails();
			$sincro_result 	= $this->wee_sincronizeProducts();
			$sincro_comment_result 	= $this->wee_sincronizeComments();
			$sincro_reply_result 	= $this->wee_sincronizeCommentsReplies();
			//$sincro_result .= $this->sincronizeCategories();
			
			echo $mailer_result.'<br><br>';
			echo $sincro_result.'<br><br>';
			echo $sincro_comment_result.'<br><br>';
			echo $sincro_reply_result.'<br><br>';
            
		} else {
			echo "not logged<br><br>\n";
		}
	}
	
	/* SE ENVIAN LOS MAILS PREPARADOS */
	function wee_sendScheduledMails()
	{
		global $wpdb;
		$orders = $this->wee_getOrders($this->weecomments_options['WEE_MAIL_FROM'], $this->weecomments_options['WEE_MAIL_TO'], $wpdb->prefix);

		if ($orders) {
			$text_results = '';
			$num_mails_sent = 0;
			foreach ($orders as $order) {

				//Evitar Spam //Máximo enviar X mails cada vez
				if ($num_mails_sent >= $this->weecomments_options['WEE_MAIL_LIMIT']) break;

				$send_mail = file_get_contents(
				'http://weecomments.com/wsrest/check_send_email2?api='.$this->weecomments_options['WEE_API_KEY'].'&id_order='.$order->id_order.'&email='.$order->email);
				$send_mail = new SimpleXMLElement($send_mail);

                
				//Si validamos con weeComments que podemos enviar el mail, o ya se ha enviado antes.
				if ((int)$send_mail->send_mail) {
					$products = $this->wee_getOrderProducts($order->id_order, $wpdb->prefix);
					$id_products_string = $product_names_string = $product_urls_string = $product_photos_string = '';
					//De momento el iso_code es el language default
					$order->iso_code = $this->weecomments_options['WEE_LANG'];
					
					if (count($products)) {
						$i = 0;
						foreach ($products as $product) {	
							if ($i > 0) {
								$id_products_string .= '---';
								$product_names_string .= '---';
                                $product_urls_string .= '---';
                                $product_photos_string .= '---';
							}
							$id_products_string .= $product->id_product;
							$product_names_string .= $product->product_name;
                            
                            $product_urls_string .= get_permalink($product->id_product);
                            $image_array = wp_get_attachment_image_src(get_post_thumbnail_id($product->id_product), 'full');
                            $product_photos_string .= $image_array[0];
							$i++;
						}

					}
                    //$order->email = "weecomments@gmail.com";
					$text_results .= $this->wee_sendMail($order, $id_products_string, $product_names_string, $product_urls_string, $product_photos_string);
					$num_mails_sent++;
					sleep(rand(3, 10));
				}
			}
			$result = '<h2>'.__('TOTAL:', 'weecomments').' '.$num_mails_sent.' '.__('correos', 'weecomments').'</h2><br><br>'.$text_results;
		} else {
			$result = '<h2>'.__('No hay correos para enviar', 'weecomments').'<h2>';
        }

		return $result;
	}

	
	function wee_sendMail($order, $id_products_string, $product_names_string, $product_urls_string, $product_photos_string)
	{
		$mail_html = $this->wee_prepareMailTemplate($order, $id_products_string, $product_names_string, $product_urls_string, $product_photos_string);
		
		$from	  = $this->weecomments_options['WEE_EMAIL'];
		$headers  = "From: ".substr(get_option('blogname'), 0,15)." <$from>\r\n";
		$headers .= "Reply-To: $from\r\n";
		$headers .= "Return-Path: $from\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=UTF-8\r\n";
		$headers .= "Organization: ".get_option('blogname')."\r\n"; 
		$headers .= "X-Priority: 3\r\n";
		$headers .= "X-Mailer: PHP". phpversion();


		if (mail($order->email, $this->weecomments_options['WEE_SUBJECT'], $mail_html, $headers)) {
			$text_result = "<p style='color: green;'>(ID: ".$order->id_order.') '.__('Se ha enviado correctamente el email a:', 'weecomments').' '.$order->email.' ('.date('Y-m-d H:i:s').')</p>';	
				
			$response = file_get_contents(
		'http://weecomments.com/track/send2?api='.$this->weecomments_options['WEE_API_KEY'].'&id_order='.$order->id_order);			
		} else {
			$text_result = "<p style='color: red;'>".__('No se ha enviado el email a:', 'weecomments').' '.$order->email.'('.$order->id_order.') </p>';
        }
		
		return $text_result;
	}

	
	function wee_prepareMailTemplate($order, $id_products_string, $product_names_string, $product_urls_string, $product_photos_string)
	{
		$link = 'http://weecomments.com/'.$order->iso_code.'/new_review_form?id_shop='
		.$this->weecomments_options['WEE_ID_SHOP']
		.'&id_order='.$order->id_order
		.'&customer_name='.urlencode($order->firstname)
		.'&customer_lastname='.urlencode($order->lastname)
		.'&email='.urlencode($order->email)
		.'&lang='.$order->iso_code
		.'&id_products='.$id_products_string
		.'&product_names='.$product_names_string
        .'&product_urls='.$product_urls_string
        .'&product_photos='.$product_photos_string
		.'&security_key='.$this->weecomments_options['WEE_SECURITY_KEY']
		.'&mail_version=3'
		.'&utm_source=map&utm_medium=email&utm_campaign=map_web_view';
		
		$track = 'http://weecomments.com/track/open2?id_shop='
		.$this->weecomments_options['WEE_ID_SHOP']
        ."&id_order=".$order->id_order
		.'&security_key='.$this->weecomments_options['WEE_SECURITY_KEY'];
		
		$weemailClass = new wee_weemailClass();
		$weemailClass->wee_cargarPlantilla(plugin_dir_path(__FILE__).'../mails/review_general.html');
		$weemailClass->wee_registrarCampo("ID_ORDER", $order->id_order);
		$weemailClass->wee_registrarCampo("LANG_CODE", $order->iso_code);
		$weemailClass->wee_registrarCampo("SHOP_NAME", get_option('blogname') );
		$weemailClass->wee_registrarCampo("CUSTOMER_NAME", $order->firstname);
		$weemailClass->wee_registrarCampo("CUSTOMER_LASTNAME", $order->lastname);
		$weemailClass->wee_registrarCampo("CUSTOMER_EMAIL", $order->email);
		$weemailClass->wee_registrarCampo("WEE_ID_SHOP", $this->weecomments_options['WEE_ID_SHOP']);
		$weemailClass->wee_registrarCampo("WEE_SECURITY_KEY", $this->weecomments_options['WEE_SECURITY_KEY']);
		$weemailClass->wee_registrarCampo("WEE_TEXT", $this->weecomments_options['WEE_TEXT']);
		$weemailClass->wee_registrarCampo("ID_PRODUCTS_STRING", $id_products_string);
		$weemailClass->wee_registrarCampo("PRODUCT_NAMES_STRING", $product_names_string);
        $weemailClass->wee_registrarCampo("PRODUCT_URLS_STRING", $product_urls_string);
        $weemailClass->wee_registrarCampo("PRODUCT_PHOTOS_STRING", $product_photos_string);
		$weemailClass->wee_registrarCampo("LINK", $link);
		$weemailClass->wee_registrarCampo("TRACK", $track);
        $weemailClass->wee_registrarCampo("WEE_LOGO_URL", $this->weecomments_options['WEE_LOGO_URL']);
        
        $weemailClass->wee_registrarCampo("URL_IMG_LOGO_WEECOMMENTS", plugins_url( '/img/logo-weecomments-new-v4.png', __FILE__ ) );
        $weemailClass->wee_registrarCampo("URL_IMG_NOTEBOOK", plugins_url( '/img/notebook-white-23.png', __FILE__ ) );
		$weemailClass->wee_parsearCodigoHtml();
		
		return $weemailClass->wee_devolverCodigoHtml();
		//return utf8_decode($mail_html);
	}
  
	function wee_getOrders($mail_from, $mail_to, $prefix)
	{
		$sql = "SELECT O.ID as id_order, O.post_date_gmt as date, M.meta_value as email, M2.meta_value as firstname, M3.meta_value as lastname
		FROM ".$prefix."posts O 
		LEFT JOIN ".$prefix."postmeta M ON M.post_id = O.ID AND M.meta_key = '_billing_email'
		LEFT JOIN ".$prefix."postmeta M2 ON M2.post_id = O.ID AND M2.meta_key = '_billing_first_name'
		LEFT JOIN ".$prefix."postmeta M3 ON M3.post_id = O.ID AND M3.meta_key = '_billing_last_name'
		WHERE O.post_type = 'shop_order' AND O.post_status = 'wc-completed'
		AND O.post_date_gmt < NOW() - INTERVAL $mail_from DAY 
		AND O.post_date_gmt > NOW() - INTERVAL $mail_to DAY";

		global $wpdb;
		$result = $wpdb->get_results($sql);
		if ($result)
			return $result;
		else
			return NULL;
	}
	
	function wee_getNumMonthlyOrders($prefix)
	{
		$month = date('m');
		$year = date('Y');
		$sql = "SELECT COUNT(*) as num_monthly_orders 
		FROM ".$prefix."posts 
		WHERE post_type = 'shop_order' AND post_status = 'wc-completed' AND month(post_date_gmt) = '".$month."' AND year(post_date_gmt) = '".$year."'";
		global $wpdb;
		return $wpdb->get_var($sql);
	}
	
	function wee_getOrderProducts($id_order, $prefix)
	{
		$sql = "SELECT DISTINCT(meta_value) as id_product, order_item_name as product_name
			FROM ".$prefix."woocommerce_order_items OI 
			LEFT JOIN ".$prefix."woocommerce_order_itemmeta M ON M.order_item_id = OI.order_item_id AND M.meta_key = '_product_id'
			WHERE OI.order_id = '".$id_order."'
			AND OI.order_item_type = 'line_item'
			";
		global $wpdb;
		$result = $wpdb->get_results($sql);
		if ($result) 
			return $result;
		else 
			return NULL;
	}
	
	
	
	function wee_updateProduct($id_product, $num_ratings, $avg_rating)
	{
		global $wpdb;
		$sql = wpdb::prepare("UPDATE wee_products SET num_ratings = %u, avg_rating = %f WHERE id_product = %u", $num_ratings, $avg_rating, $id_product);        
		$wpdb->query($sql);
	}
	
	function wee_insertProduct($id_product, $num_ratings, $avg_rating)
	{
		global $wpdb;
		$sql = wpdb::prepare("INSERT INTO `wee_products`(id_product, num_ratings, avg_rating) VALUES (%u, %u, %f)", $id_product, $num_ratings, $avg_rating);
		$wpdb->query($sql);
	}
	
	function wee_checkProductExist($id_product)
	{
		$sql = "SELECT * FROM wee_products WHERE id_product = '$id_product'";
		global $wpdb;
		return $wpdb->get_results($sql);
	}



	//Actualiza la información de la tienda con el webservice de weecomments
	function wee_updateShopInfo()
	{
        
		$shop_info = file_get_contents('http://weecomments.com/wsrest/module_shop_info?api='.$this->weecomments_options['WEE_API_KEY']);
		$shop_info = new SimpleXMLElement($shop_info);
        
        
        file_get_contents('http://weecomments.com/wsrest/update_module_version?api='.$this->weecomments_options['WEE_API_KEY'].'&version=3.0.0');

		//Si el usuario y pass son correctos, actualiza los datos de la tienda
		if((int)$shop_info->id_shop) {
			global $wpdb;
			$monthly_orders = $this->wee_getNumMonthlyOrders($wpdb->prefix);
			file_get_contents(
			'http://weecomments.com/track/update_monthly_orders?api='.$this->weecomments_options['WEE_API_KEY'].'&num_monthly_orders='.$monthly_orders);
		
			$weecomments_options['WEE_API_KEY'] 		= $this->weecomments_options['WEE_API_KEY'];
			$weecomments_options['WEE_ID_SHOP'] 		= trim($shop_info->id_shop);
			$weecomments_options['WEE_URL'] 			= trim($shop_info->friendly_url);
			$weecomments_options['WEE_SECURITY_KEY'] 	= trim($shop_info->security_key);
			$weecomments_options['WEE_SUBSCRIPTION'] 	= trim($shop_info->subscription);
			$weecomments_options['WEE_SUBJECT'] 		= trim($shop_info->mail_subject);
			$weecomments_options['WEE_TITLE'] 			= trim($shop_info->mail_title);
			$weecomments_options['WEE_TEXT'] 			= trim($shop_info->mail_text);
			$weecomments_options['WEE_BUTTON'] 			= trim($shop_info->mail_button);
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
            
		} else {
			$weecomments_options['WEE_API_KEY'] = '';
			$weecomments_options['WEE_ID_SHOP'] = '';
			$weecomments_options['WEE_URL'] = '';
			$weecomments_options['WEE_SECURITY_KEY'] = '';
			$weecomments_options['WEE_SUBSCRIPTION'] = '';
			$weecomments_options['WEE_SUBJECT'] = '';
			$weecomments_options['WEE_TITLE'] = '';
			$weecomments_options['WEE_TEXT'] = '';
			$weecomments_options['WEE_BUTTON'] = '';
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
		}
				
	}
	
	function wee_updateLangsConfiguration()
	{
		$langs_configuration = file_get_contents(
		'http://weecomments.com/wsrest/module_langs_configuration?api='.$this->weecomments_options['WEE_API_KEY']);
		$langs_configuration = new SimpleXMLElement($langs_configuration);

		//Si el usuario y pass son correctos, actualiza los datos de la tienda
		foreach($langs_configuration as $lang_conf) {
			$weecomments_langconf['WEE'.$lang_conf->lang.'_SUBJECT'] = $lang_conf->mail_subject;
			$weecomments_langconf['WEE'.$lang_conf->lang.'_TEXT'] = $lang_conf->mail_text;
			$weecomments_langconf['WEE'.$lang_conf->lang.'_EMAIL'] = $lang_conf->email_reply;
			$weecomments_langconf['WEE'.$lang_conf->lang] = 'active';
			update_option( 'weecomments_langconf', $weecomments_langconf);
		}
	}

	/* SE ACTUALIZAN LAS VALORACIONES DE LOS PRODUCTOS PARA EL RICH SNIPPET */
	function wee_sincronizeProducts()
	{
		$response = file_get_contents('http://weecomments.com/wsrest/shop_product_reviews2?api='.$this->weecomments_options['WEE_API_KEY']);
		$products = new SimpleXMLElement($response);

		$result = '<br><br><h2>Updating '.count($products).' products</h2>';
		foreach ($products as $product) {
			if ($this->wee_checkProductExist($product->id_product)) {
				$result .= 'Updated: '.$product->id_product.'<br>';
				$this->wee_updateProduct($product->id_product, $product->num_ratings, $product->avg_rating);
			} else {
				$result .= 'Inserted: '.$product->id_product.'<br>';
				$this->wee_insertProduct($product->id_product, $product->num_ratings, $product->avg_rating);
			}
		}
		return $result;
	}
    
    function wee_sincronizeComments(){
        
        $return = "<b>Sincronizando comentarios</b> <br>";
        
		$last_comment = $this->wee_getLastIDComment();
        
        if($last_comment == "" || !isset($last_comment)){
            $last_comment = 0;
        }
        
		$response = file_get_contents('http://weecomments.com/wsrest/get_all_shop_comments?api='.$this->weecomments_options['WEE_API_KEY'].'&last_comment='.$last_comment);
                
		$comments = new SimpleXMLElement($response);
        
		foreach ($comments as $comment)
		{
            $return .= "<br>";
            $return .= $comment->id_comment;
			$this->wee_insertComment($comment);
		}
        
        return $return;
        
	}
    
    function wee_insertComment($comment){        
        global $wpdb;
        $sql = wpdb::prepare("INSERT INTO wee_comments (id_product, id_comment, id_order, id_shop, customer_name, customer_lastname, email, IP, date, comment, rating, rating1, rating2, rating3, status, lang, external) VALUES (%u, %u, %s, %u, %s, %s, %s, %s, %s, %s, %u, %u, %u, %u, %s, %s, %s)", $comment->id_product, $comment->id_comment, $comment->id_order, $comment->id_shop, $comment->customer_name, $comment->customer_lastname, $comment->email, $comment->IP, $comment->date, $comment->comment, $comment->rating, $comment->rating1, $comment->rating2, $comment->rating3, $comment->status, $comment->lang, $comment->external);
		$wpdb->query($sql);
	}

    function wee_getLastIDComment(){
        
        $sql = "SELECT id_comment FROM wee_comments where 1 ORDER BY id_comment DESC LIMIT 1";
        
        global $wpdb;
		return $wpdb->get_var($sql);
    }
    
    function wee_sincronizeCommentsReplies(){
        
        $result = "<b>Sincronizando respuestas</b> <br>";
        
		$last_comment = $this->wee_getLastIDCommentReply();
        
        if($last_comment == "" || !isset($last_comment)){
            $last_comment = 0;
        }
        
		$response = file_get_contents('http://weecomments.com/wsrest/get_all_shop_comments_replies2?api='.$this->weecomments_options['WEE_API_KEY'].'&last_id_comment_reply='.$last_comment);
        
		$replies = new SimpleXMLElement($response);
		
		foreach ($replies as $reply)
		{
			$this->wee_insertCommentReply($reply);
		}

	}
    
    function wee_insertCommentReply($comment){
        
        global $wpdb;
        $sql = wpdb::prepare("INSERT INTO wee_comments_replies (id_reply, id_comment, reply, date) VALUES (%u, %s, %s, %s)", $comment->id_reply, $comment->id_comment, $comment->reply, $comment->date);
		$wpdb->query($sql);
	}
    
    function wee_getLastIDCommentReply(){
        
        $sql = "SELECT id_reply FROM wee_comments_replies where 1 ORDER BY id_reply DESC LIMIT 1";
        
        global $wpdb;
		return $wpdb->get_var($sql);
	}
    
    public function wee_getProductComments($id_product, $lang)
	{
		$sql = "SELECT * FROM wee_comments WHERE id_product = '$id_product' AND lang = '$lang' AND status = '1' ORDER BY date DESC LIMIT 5";
        
        global $wpdb;
		return $wpdb->get_results($sql);
	}
    
    public function wee_getCommentReply($id_comment)
	{
		$sql = "SELECT * FROM wee_comments_replies WHERE id_comment = ".(int)$id_comment;
        
		global $wpdb;
		return $wpdb->get_results($sql);
	}
    
}

?>