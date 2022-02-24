<?php

defined( 'ABSPATH' ) || exit;

class WPrenter
{
	public function __construct()
	{
		// security
		require_once(__DIR__.'/WPrenterSecurity.class.php');
		new WPrenterSecurity();

		// THEME SUPPORTS
		if(!current_theme_supports('custom-header')) { add_theme_support('custom-header'); }
		if(!current_theme_supports('custom-logo')) { add_theme_support('custom-logo'); }
		if(!current_theme_supports('custom-background')) { add_theme_support('custom-background'); }
		if(!current_theme_supports('title-tag')) { add_theme_support('title-tag'); }
		if(!current_theme_supports('post-thumbnails')) { add_theme_support('post-thumbnails'); }
		if(!current_theme_supports('automatic-feed-links')) { add_theme_support('automatic-feed-links'); } // add default posts and comments RSS feed links to head
		if(!current_theme_supports('woocommerce')) { add_theme_support('woocommerce'); }

		// actions
		add_action('init', array( &$this, 'action_init') );
		add_action('map_meta_cap', array(&$this, 'action_map_meta_cap'), 1, 4);
		add_action( "wp_ajax_nopriv_wprform", array ( &$this, 'wprform' ) );
		add_action( "wp_ajax_wprform", array ( &$this, 'wprform' ) );
		// filters
		add_filter('user_contactmethods', array(&$this, 'filter_user_contactmethods'), 10, 1);
		add_filter('wp_mail_from', array(&$this, 'filter_wp_mail_from')); // change default e-mail sender name and address
		add_filter('wp_mail_from_name', array(&$this, 'filter_wp_mail_from_name')); // change default e-mail sender name and address
		add_filter('pre_option_link_manager_enabled', '__return_true'); // enable links admin menu

		// STATIC BLOCK
		// create and set "static block" post type
		// disable WYSIWYG editor
		// return with static block post content
		//add_shortcode('block', 'polygons_static_block_function');
		// add an usage text on edit static form admin page
		//add_action('edit_form_top', 'polygons_static_block_edit_form_help');
	}

	public function wprform()
	{
		$JSON = json_decode( get_post( $_POST['postID'] )->post_content, true );
		$emailto = $JSON['emailto'];
		$emailsubject = $JSON['subject'];
		$emailcontent = '';
		$i = 0;
		$clientEmail = null;
		foreach( $_POST['data'] as $data )
		{
			// if first element is empty, drop request
			if($i===0)
			{
				if($data['value']==='')
				{
					echo 'Első mező kitöltése kötelező!';
					exit;
				}
				else
				{
					if(filter_var($data['value'], FILTER_VALIDATE_EMAIL))
					{
						$clientEmail = $data['value'];
					}
				}
			}
			$emailcontent .= $data['name'].": ".$data['value']."\n";
			$i++;
		}
		if( wp_mail( $emailto, $emailsubject, $emailcontent ) )
		{
			echo 'Feldolgozás sikeres.';
			if(!is_null($clientEmail))
			{
				wp_mail( $clientEmail, $emailsubject, $emailcontent );
			}
		}
		else
		{
			echo 'HIBA történt';
		}
		exit;
	}


	public function action_map_meta_cap($caps, $cap, $user_id, $args)
	{
		if (!is_user_logged_in()) return $caps;

		$user_meta = get_userdata($user_id);
		if (array_intersect(['editor', 'administrator'], $user_meta->roles)) {
			if ('manage_privacy_options' === $cap) {
				$manage_name = is_multisite() ? 'manage_network' : 'manage_options';
				$caps = array_diff($caps, [ $manage_name ]);
			}
		}
		return $caps;
	}


	// add new contact options to profile
	public function filter_user_contactmethods($contact_methods)
	{
		$contact_methods['phone'] = 'Phone';
		$contact_methods['address'] = 'Address';
		$contact_methods['birthday'] = 'Birthdate (YYYY-MM-DD)';
		$contact_methods['nameday'] = 'Nameday (MM-DD)';
		$contact_methods['tax_number'] = 'Tax number';
		$contact_methods['loyalty_id_card_number'] = 'Loyalty ID / card number';
		return $contact_methods;
	}



	public function action_init()
	{
		// polygons_create_static_block_post_type - create and set "static block" post type
		$post_type_settings = array('labels' => array('name'=>__('Blocks'), 'singular_name'=>__('Static Blocks')), 'public'=>true, 'has_archive'=>true, 'menu_icon'=>'dashicons-schedule', 'show_in_menu' => 'wpcoder', 'menu_position'=>1000);
		register_post_type('static_block', $post_type_settings);
	}

	// FILTERS
	public function filter_wp_mail_from_name($original_email_from)
	{
		return get_bloginfo('name');
	}
	public function filter_wp_mail_from($original_email_address)
	{
		return get_bloginfo('admin_email');
	}

}
