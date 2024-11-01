<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class wee_Category extends WP_Widget {
	
	//Constructor
	function wee_Category() {
		
		$this->weecomments_options = get_option('weecomments_options');
		$this->weecomments_configuration = get_option('weecomments_configuration');
		
		$widget_ops = array('classname' => 'weecomments category', 'description' => __('Muestra rich snippets en las categorías de productos', 'weecomments') );
		parent::__construct('weecomments_category', 'weeComments category', $widget_ops);
	}

	function widget($args, $instance) {
		
		// prints the widget
		global $post;
	}

	function update($new_instance, $old_instance) {
		//save the widget
	}
	
	function form($instance) {
		//widgetform in backend
	}
}

?>