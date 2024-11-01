<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div id="weecomments_back">

    <div id="wee_logo_container">
        <img src="<?php echo plugins_url( 'img/logo-weecomments-new-v4.png', dirname(__FILE__) )?>" /><br />
        
        <p>
            <form method="post">
            <!--
            <input type="text" size="30" name="WEE_USER" value="<?php if(isset($weecomments_options['WEE_USER']))echo $weecomments_options['WEE_USER']?>" placeholder="<?php _e('usuario', 'weecomments')?>" />
            <input type="password" size="30" name="WEE_PASS" value="<?php if(isset($weecomments_options['WEE_PASS']))echo $weecomments_options['WEE_PASS']?>" placeholder="<?php _e('contraseña', 'weecomments')?>" />
            -->
            <input type="text" size="30" name="WEE_API_KEY" value="<?php if(isset($weecomments_options['WEE_API_KEY'])) echo $weecomments_options["WEE_API_KEY"] ?>" placeholder="API KEY"/>
            
            <input type="submit" name="submitConfiguration" value="<?php _e('enviar', 'weecomments')?>" class="wee_button wee_button_small" />
            </form>
        </p>
        <h4 class="green"><?php _e('Congratulations! You are now logged in, This is your current subscription', 'weecomments')?> <?=$subscription?>.</h4>
                
        <h4><?php _e('Now you can configure your settings in', 'weecomments')?> <a class="green" target="_blank" href="http://weecomments.com">weecomments.com</a></h4>
    </div>
    


</div>


