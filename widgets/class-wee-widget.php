<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class wee_Widget extends WP_Widget {
	
	//Constructor
	function wee_Widget() {
		
		$this->weecomments_options = get_option('weecomments_options');
		$this->weecomments_configuration = get_option('weecomments_configuration');
		
		$widget_ops = array('classname' => 'weecomments', 'description' => __('Muestra la caja de opiniones de weeComments', 'weecomments') );
		parent::__construct('weecomments_box', 'weeComments widget', $widget_ops);
	}

	function widget($args, $instance) {
		
		// prints the widget
		echo '
		<aside class="widget widget_weecomments">
			<a target="_blank" href="http://weecomments.com/es/opiniones/'.$this->weecomments_options['WEE_URL'].'" title="'.__('Opiniones de', 'weecomments').' '.get_option('blogname').'">
				<div class="weecomments"></div>
			</a>
		</aside>';
	}

	function update($new_instance, $old_instance) {
		//save the widget
	}
	
	function form($instance) {
		//widgetform in backend
	}
}

?>