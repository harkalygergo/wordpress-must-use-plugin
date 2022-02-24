<?php

if ( is_admin() )
{
	include_once( __DIR__.'/app/core/classes/WPrenterDashboard.class.php' );
	new WPrenterDashboard();
}
else
{
	include_once( __DIR__.'/app/core/classes/WPrenterTheme.class.php' );
	new WPrenterTheme();
}


add_action('plugins_loaded', 'action_plugins_loaded');
function action_plugins_loaded()
{
	if (defined('WC_VERSION') || is_admin() ) {
		include_once( __DIR__.'/app/core/classes/WPrenterWooCommerce.class.php' );
		new WPrenterWooCommerce();
		// no woocommerce :(
	}
}



// 
// return with static block post content
function polygons_static_block_function($atts)
{
	global $post;
	if( isset($atts['id']) || isset($atts['slug']) )
	{
		$post = null;
		if(isset($atts['id']))
		{
			$post = get_post($atts['id']);
		}
		else
		{
			if(isset($atts['slug']))
			{
				$post = get_posts(array('name'=>$atts['slug'], 'posts_per_page' => 1, 'post_type' =>'static_block', 'post_status' => 'publish'));
				$post = $post['0'];
			}
		}
		if(is_object($post))
		{
			return $post->post_content;
		}
	}
}
// add an usage text on edit static form admin page
function polygons_static_block_edit_form_help()
{
	global $post;
	if('static_block' == get_post_type($post))
	{
		echo '<p>Shortcode example: <code>[block id="123"]</code> OR <code>[block slug="test_demo"]</code></p>';
	}
}