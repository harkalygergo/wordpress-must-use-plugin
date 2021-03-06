<?php

require_once ( __DIR__.'/WPrenter.class.php' );
class WPrenterDashboard extends WPrenter
{
	public function __construct()
	{
		// actions
		add_action('admin_bar_menu', array(&$this, 'action_admin_bar_menu'), 999);
		add_action('admin_init', array(&$this, 'action_admin_init'));
		add_action('admin_menu', array(&$this, 'action_admin_menu' ) );
		add_action('manage_posts_custom_column', array(&$this, 'action_manage_posts_custom_column'), 10, 2);
		add_action('wp_ajax_save_wprenter_settings', array(&$this, 'action_wp_ajax_save_wprenter_settings' ) );
		// filters
		add_filter('admin_footer_text', array(&$this, 'filter_admin_footer_text') );
		add_filter('manage_posts_columns', array(&$this, 'filter_manage_posts_columns'), 1);
		add_filter('user_can_richedit', array( &$this, 'filter_user_can_richedit') );
		add_filter('upload_mimes', array( &$this, 'filter_upload_mimes'));


		add_filter( 'manage_users_columns', array(&$this, 'filter_manage_users_columns' ) );
		add_filter( 'manage_users_custom_column', array(&$this, 'filter_manage_users_custom_column' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array(&$this, 'filter_manage_users_sortable_columns' ) );


		parent::__construct();
	}

	public function filter_manage_users_columns( $column )
	{
		$column['registration_date'] = 'Registered';
		return $column;
	}
	public function filter_manage_users_custom_column( $val, $column_name, $user_id )
	{
		switch ($column_name) {
			case 'registration_date' :
				return get_the_author_meta( 'registered', $user_id );
			default:
		}
		return $val;
	}
	public function filter_manage_users_sortable_columns( $columns )
	{
		return wp_parse_args( array( 'registration_date' => 'registered' ), $columns );
	}


	public function filter_upload_mimes($mimes = array())
	{
		// allow .csv uploads
		if( !isset($mimes['csv'] ) ) $mimes['csv'] = "text/csv";
		if( !isset($mimes['ico'] ) ) $mimes['ico'] = "image/x-icon";
		return $mimes;
	}

	public function action_admin_init()
	{
		// remove admin welcome
		remove_action('welcome_panel', 'wp_welcome_panel');
		//remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
		remove_meta_box('dashboard_primary', 'dashboard', 'normal');
		//remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
		//remove_meta_box('dashboard_quick_press', 'dashboard', 'core');
		//remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
	}
	public function action_admin_bar_menu($wp_admin_bar)
	{
		// remove dashboard wordpress logo
		$wp_admin_bar->remove_node('wp-logo');
	}


	public function action_admin_menu()
	{
		add_menu_page( 'WPrenter', 'WPRenter.com', 'manage_options', 'wprenter', array(&$this, 'menu_page_wprenter'), 'dashicons-universal-access-alt', 1 );
		add_submenu_page('wprenter', 'WPRenter', 'WPRenter', 'manage_options', 'wpcoder', array(&$this, 'menu_page_wpcoder'));
		// register 'WPrenter' menu to dashboard
		//add_menu_page('WPrenter menu', 'WPrenter', 'edit_pages', 'wprenter', array(&$this, 'add_menu_page_wpcoder'), 'dashicons-admin-site', '0');
		// register 'Settings' submenu to 'WPrenter'
		add_submenu_page('wprenter', 'Settings', 'Settings', 'manage_options', 'wprenter_settings', array(&$this, 'add_submenu_page_wpcoder_settings'));
		// register 'Blocks' submenu to 'WPrenter'
		//add_submenu_page('wprenter', 'Blocks', 'Blocks', 'edit_pages', 'wprenter_blocks', array(&$this, 'add_submenu_page_wprenter_blocks'));
		// register 'Marketing' submenu to 'WPrenter'
		add_submenu_page('wprenter', 'Marketing', 'Marketing', 'edit_pages', 'wprenter_marketing', array(&$this, 'add_submenu_page_wpcoder_marketing'));
		// register 'Documentation' submenu to 'WPrenter'
		add_submenu_page('wprenter', 'Documentation', 'Documentation', 'edit_pages', 'wprenter_documentation', array(&$this, 'add_submenu_page_wpcoder_documentation'));

		// remove tools.php
		//remove_menu_page('tools.php', 'tools.php');

		// add WordPress 5 versions block editors' blocks
		add_submenu_page('wprenter', 'WP blocks', 'WP ' . __('Blocks'), 'manage_options', 'edit.php?post_type=wp_block');
		// add WordPress 5 versions block editors' blocks
		add_submenu_page('wprenter', 'WC product import', 'WC import', 'manage_options', 'edit.php?post_type=product&page=product_importer');
		// add WordPress 5 versions block editors' blocks
		add_submenu_page('wprenter', 'WC product export', 'WC export', 'manage_options', 'edit.php?post_type=product&page=product_exporter');
	}
	public function action_manage_posts_custom_column($column_name, $id)
	{

		// display thumbnail on dashboard edit.php screen
		if($column_name === 'post_thumbnail')
		{
			the_post_thumbnail( array(160, 40) );
		}
	}
	public function action_wp_ajax_save_wprenter_settings()
	{
		$wprenter_settings = isset( $_POST['wprenter_settings_import'] ) ? $_POST['wprenter_settings_import'] : json_encode(str_replace("\\'", "'", str_replace("\\\"", "'", $_POST)), JSON_UNESCAPED_UNICODE);
		update_option( 'wprenter_settings', $wprenter_settings, 'yes' );
		echo "<script>window.location.replace('{$_SERVER['HTTP_REFERER']}');</script>";
		//header( "Location:{$_SERVER['HTTP_REFERER']}" );
		/*
		if( update_option( 'wprenter_settings', json_encode($_POST['json'], JSON_UNESCAPED_UNICODE), 'yes' ) )
		{
			echo 'true';
		}
		*/
		//wp_die();
	}

	public function filter_admin_footer_text ()
	{
		// modify dashborad footer thank-you-wordpress-text
		?>
		<script>
			/*
			// hide WordPress logo from dashboard dropdown
			for(var i=0; i<document.getElementsByClassName('blavatar').length; i++)
			{
				document.getElementsByClassName('blavatar')[i].style.display = "none";
			}
x			// hide WordPress version and theme name from dashboard
			if(document.getElementById("wp-version-message")!==null)
			{
				document.getElementById("wp-version-message").style.display = "none";
			}
			*/
		</script>
		<script src="/contents/mu-plugins/WPrenter/app/core/js/footer_dashboard.js"></script>
		&copy; WPRenter.com | <a target="_blank" href="https://www.wprenter.com/">WPRenter</a> by [<a target="_blank" href="https://www.brandcomstudio.com/">BrandCom Studio</a>]
	<?php }
	public function filter_manage_posts_columns($colums)
	{
		// display thumbnail on dashboard edit.php screen
		if( $_GET['post_type'] !== 'product' )
		{
			$colums['post_thumbnail'] = 'Image';
		}
		return $colums;
	}
	public function filter_user_can_richedit($default)
	{
		// disable WYSIWYG editor - disable_polygons_static_block_post_type_wysiwyg
		global $post;
		if('static_block' == get_post_type($post))
			return false;
		return $default;
	}

	public function add_submenu_page_wpcoder_settings()
	{
		?>
		<div class="wrap">
			<h1>WPRenter settings</h1>
			<form method="post" action="admin-ajax.php" id="wprenter_settings_form">
				<input type="hidden" name="action" id="action" value="save_wprenter_settings" />
				<h2 class="title">DESIGN</h2>
				<h3 class="title">??rtes??t?? s??v</h3>
				<table class="form-table">
					<tbody>
					<tr>
						<th><label for="notification_active">??rtes??t?? s??v akt??v</label></th>
						<td>
							<select class="regular-text ltr" name="notification_active" id="notification_active">
								<option value="yes">igen</option>
								<option value="no">nem</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="notification_text">??rtes??t?? s??v sz??vege</label></th>
						<td><input class="regular-text ltr" type="text" name="notification_text" id="notification_text"><p class="description">Tetsz??leges HTML karakter is megadhat??.</p></td>
					</tr>
					<tr>
						<th><label for="notification_background_color">??rtes??t?? s??v sz??ne</label></th>
						<td>
							<select class="regular-text ltr" name="notification_background_color" id="notification_background_color">
								<option value="danger">piros (danger)</option>
								<option value="primary">k??k (primary)</option>
								<option value="secondary">sz??rke (secondary)</option>
								<option value="success">z??ld (success)</option>
								<option value="warning">s??rga (warning)</option>
								<option value="info">t??rkiz (info)</option>
								<option value="light">feh??r (light)</option>
								<option value="dark">s??t??tsz??rke (dark)</option>
							</select>
						</td>
					</tr>
					</tbody>
				</table>
				<h3 class="title">El??rhet??s??gek</h3>
				<table class="form-table">
					<tbody>
					<tr>
						<th><label for="phone">Telefonsz??m</label></th>
						<td><input class="regular-text ltr" type="tel" name="phone" id="phone" placeholder="+36..."></td>
					</tr>
					<tr>
						<th><label for="mobile">Mobil</label></th>
						<td><input class="regular-text ltr" type="tel" name="mobile" id="mobile" placeholder="+36..."></td>
					</tr>
					<tr>
						<th><label for="fax">Fax</label></th>
						<td><input class="regular-text ltr" type="tel" name="fax" id="fax" placeholder="+36..."></td>
					</tr>
					<tr>
						<th><label for="email">E-mail c??m</label></th>
						<td><input class="regular-text ltr" type="email" name="email" id="email" placeholder="...@..."></td>
					</tr>
					<tr>
						<th><label for="address">C??m</label></th>
						<td><input class="regular-text ltr" type="text" name="address" id="address" placeholder="1234 Budapest, F?? utca 1."></td>
					</tr>
					<tr>
						<th><label for="openhours">Nyitva tart??s</label></th>
						<td><input class="regular-text ltr" type="text" name="openhours" id="openhours" placeholder="h??tf??-vas??rnap 07:00-19:00"></td>
					</tr>
					<tr>
						<th><label for="facebook_url">Facebook URL</label></th>
						<td><input class="regular-text ltr" type="url" name="facebook_url" id="facebook_url" placeholder="https://www.facebook.com/...."></td>
					</tr>
					<tr>
						<th><label for="instagram_url">Instagram URL</label></th>
						<td><input class="regular-text ltr" type="url" name="instagram_url" id="instagram_url" placeholder="https://instagram.com/..."></td>
					</tr>
					<tr>
						<th><label for="twitter_url">Twitter URL</label></th>
						<td><input class="regular-text ltr" type="url" name="twitter_url" id="twitter_url" placeholder="https://twitter.com/..."></td>
					</tr>
					<tr>
						<th><label for="youtube_url">YouTube URL</label></th>
						<td><input class="regular-text ltr" type="url" name="youtube_url" id="youtube_url" placeholder="https://www.youtube.com/..."></td>
					</tr>
					<tr>
						<th><label for="linkedin_url">LinkedIn URL</label></th>
						<td><input class="regular-text ltr" type="url" name="linkedin_url" id="linkedin_url" placeholder="https://www.linkedin.com/..."></td>
					</tr>
					<tr>
						<th><label for="pinterest_url">Pinterest URL</label></th>
						<td><input class="regular-text ltr" type="url" name="pinterest_url" id="pinterest_url" placeholder="https://www.pinterest.com/..."></td>
					</tr>
					<tr>
						<th><label for="flickr_url">Flickr URL</label></th>
						<td><input class="regular-text ltr" type="url" name="flickr_url" id="flickr_url" placeholder="https://flickr.com/..."></td>
					</tr>
					</tbody>
				</table>
				<h3 class="title">Speci??lis</h3>
				<table class="form-table">
					<tbody>
					<tr>
						<th><label for="slider_images_url">F??oldali slider</label></th>
						<td><textarea class="large-text code" rows="10" cols="50" name="slider_images_url" id="slider_images_url">
https://via.placeholder.com/800x400/000000
https://via.placeholder.com/800x400/ff0000
https://via.placeholder.com/800x400/00ff00
https://via.placeholder.com/800x400/0000ff
								</textarea></td>
					</tr>
					<tr>
						<th><label for="custom_css">Egyedi CSS</label></th>
						<td><textarea class="large-text code" rows="10" cols="50" name="custom_css" id="custom_css">
body { font-family:"Times New Roman", Times, serif; }
header div.alert { margin:0; }
header a { color:black; }
header div.bg-dark.text-light a { color:green; }
@media( min-device-width: 767px )
{
	header div.bg-dark.text-light div.container { height:16px; }
}
form#search { width:100%; padding: 25% 0; }
img.custom-logo { width:100%; height:auto; }
								</textarea></td>
					</tr>
					</tbody>
				</table>
				<h2 class="title">MARKETING</h2>
				<h3 class="title">Marketing, keres??optimaliz??l??si ??s egy??b speci??lis be??ll??t??sok</h3>
				<!--
				$wp_customize->add_setting('openinghours', array('default'=>'Monday - Friday 8-18'));
				$wp_customize->add_control('openinghours', array('section'=>'mangowptheme-contact', 'label'=>__('Opening hours'), 'type'=>'text'));
				$wp_customize->add_setting('feedURL');
				$wp_customize->add_control('feedURL', array('section'=>'mangowptheme-contact', 'label'=>'Feed', 'type'=>'url'));
				$wp_customize->add_setting('whatsappnumber');
				$wp_customize->add_control('whatsappnumber', array('section'=>'mangowptheme-contact', 'label'=>'WhatsApp phone number', 'type'=>'tel'));
				$wp_customize->add_setting('facebookpageURL');
				$wp_customize->add_control('facebookpageURL', array('section'=>'mangowptheme-contact', 'label'=>'Facebook Page URL', 'type'=>'url'));
				$wp_customize->add_setting('instagrampageURL');
				$wp_customize->add_control('instagrampageURL', array('section'=>'mangowptheme-contact', 'label'=>'Instagram Page URL', 'type'=>'url'));
				$wp_customize->add_setting('googlepluspageURL');
				$wp_customize->add_control('googlepluspageURL', array('section'=>'mangowptheme-contact', 'label'=>'Google+ Page URL', 'type'=>'url'));
				$wp_customize->add_setting('youtubeURL');
				$wp_customize->add_control('youtubeURL', array('section'=>'mangowptheme-contact', 'label'=>'YouTube URL', 'type'=>'url'));
				$wp_customize->add_setting('twitterpageURL');
				$wp_customize->add_control('twitterpageURL', array('section'=>'mangowptheme-contact', 'label'=>'Twitter Page URL', 'type'=>'url'));
				$wp_customize->add_setting('pinterestpageURL');
				$wp_customize->add_control('pinterestpageURL', array('section'=>'mangowptheme-contact', 'label'=>'Pinterest Page URL', 'type'=>'url'));
				$wp_customize->add_setting('linkedinURL');
				$wp_customize->add_control('linkedinURL', array('section'=>'mangowptheme-contact', 'label'=>'LinkedIn URL', 'type'=>'url'));
				-->
				<table class="form-table">
					<tbody>
					<tr>
						<th><label for="facebookappid">Facebook App ID</label></th>
						<td><input class="regular-text ltr" type="text" name="facebookappid" id="facebookappid" placeholder="123456789..."></td>
					</tr>
					<tr>
						<th><label for="google_analytics">Google Analytics</label></th>
						<td><input class="regular-text ltr" type="text" name="google_analytics" id="google_analytics" placeholder="UA-..."></td>
					</tr>
					<tr>
						<th><label for="head_html">HEAD HTML tartalom</label></th>
						<td><textarea class="large-text code" rows="10" cols="50" name="head_html" id="head_html"></textarea></td>
					</tr>
					<tr>
						<th><label for="body_html">BODY HTML tartalom</label></th>
						<td><textarea class="large-text code" rows="10" cols="50" name="body_html" id="body_html"></textarea></td>
					</tr>
					<tr>
						<th><label for="footer_html">FOOTER HTML tartalom</label></th>
						<td><textarea class="large-text code" rows="10" cols="50" name="footer_html" id="footer_html"></textarea></td>
					</tr>
					</tbody>
				</table>


				<p>You can add custom content (<code>meta, link, script, style, HTML, etc...</code>) many part of website via these options:</p>
				<nav class="nav-tab-wrapper">
					<a href="#" class="nav-tab nav-tab-active">add to <code>wp_head();</code></a>
					<a href="#" class="nav-tab">add to <code>wp_footer();</code></a>
					<a href="#" class="nav-tab ">add content after posts</a>
					<a href="#" class="nav-tab ">add content after pages</a>
				</nav>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="M??dos??t??sok ment??se">
					<input type="reset" name="reset" id="reset" class="button button-secondary" value="alap??rt??kek vissza??ll??t??sa">
				</p>
			</form>
			<script>
				jQuery.fn.serializeObject = function()
				{
					var o = {};
					var a = this.serializeArray();
					jQuery.each(a, function() {
						if (o[this.name]) {
							if (!o[this.name].push) {
								o[this.name] = [o[this.name]];
							}
							o[this.name].push(this.value || '');
						} else {
							o[this.name] = this.value || '';
						}
					});
					return o;
				};
				function save_theme_settings(form)
				{
					jQuery.post(
						ajaxurl,
						{
							'action': 'save_wprenter_settings',
							'json': (form.id==="wprenter_settings_import_form") ? jQuery("#wprenter_settings_import").val() : jQuery('#wprenter_settings_form').serializeObject()
						},
						function(response)
						{
							console.log('SERVER response: ', response);
							if( response === "true" )
							{
								alert("Sikeres ment??s");
							}
							else
							{
								alert("Nem t??rt??nt v??ltoz??s, vagy hiba keletkezett.");
							}
						}
					);
					return false;
				}
			</script>
			<hr>
			<h2>Be??ll??t??sok export??l??s / import??l??sa</h2>
			<p>??j be??ll??t??sok ment??se ut??n az oldal ??jrat??lt??se sz??ks??ges!</p>
			<h4><label for="wprenter_settings_export">Utolj??ra mentett be??ll??t??sok export??l??sa</label></h4>
			<textarea class="large-text code" id="wprenter_settings_export" readonly><?php echo get_option( 'wprenter_settings' ); ?></textarea>
			<script>
				var saved_wprenter_settings_to_load_to_form = <?php echo get_option( "wprenter_settings" ); ?>;
				const saved_wprenter_settings_array_to_load_to_form =  Object.keys(saved_wprenter_settings_to_load_to_form).map((key) => [key, saved_wprenter_settings_to_load_to_form[key]]);
				for( var i=0; i<saved_wprenter_settings_array_to_load_to_form.length; i++ )
				{
					document.getElementById( saved_wprenter_settings_array_to_load_to_form[i]['0'] ).value = saved_wprenter_settings_array_to_load_to_form[i]['1'];
				}
			</script>
			<h4><label for="wprenter_settings_import">Sablonbe??ll??t??sok import??l??sa</label></h4>
			<form method="post" action="admin-ajax.php" id="wprenter_settings_import">
				<input type="hidden" name="action" id="action" value="save_wprenter_settings" />
				<textarea class="large-text code" id="wprenter_settings_import"></textarea>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Import??l??s">
					<input type="reset" name="reset" id="reset" class="button button-secondary" value="importtartalom elvet??se">
				</p>
			</form>
		</div>
		<?php
	}
	public function add_submenu_page_wpcoder_documentation()
	{ ?>
		<div class="wrap">
			<h1>WPrenter Documentation</h1>

			<h2>Theme documentation</h2>

			<hr>
			<h2>Function documentation</h2>

			<hr>
			<h2>Future plan</h2>

		</div>
	<?php }
	public function add_submenu_page_wpcoder_marketing()
	{ ?>
		<div class="wrap">
			<h1>WPrenter Marketing</h1>
			<ul>
				<li>Short links</li>
				<li>Newsletter</li>
				<li>Popup</li>
			</ul>

			<style>
				.tabs { position:relative; height:600px; clear:both; margin:25px 0; }
				.tab { float:left; }
				.tab label.maintabs { background:#eee; padding:10px; border:1px solid #ccc; margin-left:-1px; position:relative; left:1px; }
				.tab [type=radio] { display:none; }
				.content { position:absolute; top:28px; left:0; background:white; right:0; bottom:0; padding:20px; border:1px solid #ccc; overflow:hidden; }
				.content > * { opacity:0; -webkit-transform:translate3d(0, 0, 0); -webkit-transform:translateX(-100%); -moz-transform:translateX(-100%); -ms-transform:translateX(-100%); -o-transform:	translateX(-100%); -webkit-transition:all 0.6s ease; -moz-transition:all 0.6s ease; -ms-transition:all 0.6s ease; -o-transition:	all 0.6s ease; }
				[type=radio]:checked ~ label { background:white; border-bottom:1px solid white; z-index:2; }
				[type=radio]:checked ~ label ~ .content { z-index:1; }
				[type=radio]:checked ~ label ~ .content > * { opacity:1; -webkit-transform:translateX(0); -moz-transform:translateX(0); -ms-transform:translateX(0); -o-transform:	translateX(0); }
			</style>
			<?php
			global $wpdb;
			$url = 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$user = wp_get_current_user()->user_login;
			//$wpdb->query("INSERT INTO log(date, user, url) VALUES(CURRENT_TIMESTAMP, '$user', '$url')");
			$url = ABSPATH.'wp-content/plugins/vegsoingatlanplugin/crm/';
			$nevnapcontent = '<p>Kedves ??gyfel??nk!</p><p>Ez??ton k??sz??ntj??k n??vnapj??n!</p><p>??dv??zlettel:<br />V??gs?? Ingatlan??gyn??ks??g</p>';
			$szuletesnapcontent = '<p>Kedves ??gyfel??nk!</p><p>Ez??ton sok szeretettel k??sz??ntj??k sz??let??snapj??n!</p><p>??dv??zlettel:<br />V??gs?? Ingatlan??gyn??ks??g</p>';
			$karacsonycontent = '<p>Kedves ??gyfel??nk!</p><p>Kellemes kar??csonyi ??nnepeket k??v??nunk!</p><p>??dv??zlettel:<br />V??gs?? Ingatlan??gyn??ks??g</p>';
			$ujevcontent = '<p>Kedves ??gyfel??nk!</p><p>Ez??ton k??sz??ntj??k ??j??v alkalm??b??l!</p><p>??dv??zlettel:<br>V??gs?? Ingatlan??gyn??ks??g</p>';

			$ingatlanadatlappublikalascontent = '<p>Kedves ??gyfel??nk!</p><p>Az ??n ??ltal hirdetett ingatlan megjelent a honlapunkon.</p><p>??dv??zlettel:<br />V??gs?? Ingatlan??gyn??ks??g</p>';
			$havistatcontent = '<p>Kedves ??gyfel??nk!</p><p>Ez??ton k??ldj??k havi statisztik??nkat a hirdetett ingatlanr??l!</p><p>??dv??zlettel:<br />V??gs?? Ingatlan??gyn??ks??g</p>';

			if(isset($_GET['action']) && $_GET['action'] == 'success')
			{ ?>
				<div id="message" class="updated">
					<p>??zenetsablonok friss??tve!</p>
				</div>
			<?php }
			?>

			<form method="post" action="/wp-content/plugins/vegsoingatlanplugin/action.php?action=crm">
				<h2>??gyf??lk??sz??nt??k ??s -t??j??koztat??k <button type="submit" class="button button-primary button-large">??sszes lev??lsablon friss??t??se</button></h2>
				<div class="tabs">
					<div class="tab">
						<input type="radio" id="nevnap" name="tab-group-1" checked />
						<label for="nevnap" class="maintabs">N??vnap</label>
						<div class="content">
							<?php wp_editor($nevnapcontent, 'nevnapbox', array('textarea_name'=>'nevnap', 'wpautop'=>false)); ?>
						</div>
					</div>
					<div class="tab">
						<input type="radio" id="szuletesnap" name="tab-group-1">
						<label for="szuletesnap" class="maintabs">Sz??let??snap</label>
						<div class="content">
							<?php wp_editor($szuletesnapcontent, 'szuletesnapbox', array('textarea_name'=>'szuletesnap', 'wpautop'=>false)); ?>
						</div>
					</div>
					<div class="tab">
						<input type="radio" id="karacsony" name="tab-group-1">
						<label for="karacsony" class="maintabs">Kar??csony</label>
						<div class="content">
							<?php wp_editor($karacsonycontent, 'karacsonybox', array('textarea_name'=>'karacsony', 'wpautop'=>false)); ?>
						</div>
					</div>
					<div class="tab">
						<input type="radio" id="ujev" name="tab-group-1">
						<label for="ujev" class="maintabs">??j??v</label>
						<div class="content">
							<?php wp_editor($ujevcontent, 'ujevbox', array('textarea_name'=>'ujev')); ?>
						</div>
					</div>
					<div class="tab">
						<input type="radio" id="ingatlanadatlappublikalas" name="tab-group-1">
						<label for="ingatlanadatlappublikalas" class="maintabs">??rtes??t?? ingatlanadatlap publik??l??s??r??l</label>
						<div class="content">
							<?php wp_editor($ingatlanadatlappublikalascontent, 'ingatlanadatlappublikalasbox', array('textarea_name'=>'ingatlanadatlappublikalas', 'wpautop'=>false)); ?>
						</div>
					</div>
					<div class="tab">
						<input type="radio" id="havistat" name="tab-group-1">
						<label for="havistat" class="maintabs">Havi statisztika</label>
						<div class="content">
							<?php wp_editor($havistatcontent, 'havistatbox', array('textarea_name'=>'havistat', 'wpautop'=>false)); ?>
						</div>
					</div>
				</div>
			</form>
		</div>

		<?php
	}
	public function menu_page_wprenter()
	{ ?>
		<div class="wrap">
			<h1><span class="dashicons dashicons-universal-access-alt"></span> WPRenter.com Admin</h1>
			<h3>Available modules</h3>
			<?php
			foreach( glob(__DIR__ .'/../modules/*', GLOB_ONLYDIR) as $available_module_directory )
			{
				$available_module = basename($available_module_directory);
				echo "<input type='checkbox' value='{$available_module}'> {$available_module}";
			}

			?>



			<p><b>Referral program</b></p>
			<p>Minden, az ??n egyedi aj??nl??i linkje ??ltal csatlakozott felhaszn??l?? ut??n az els?? 6 h??nap d??j??t j??v????rjuk ??nnek a bels?? egyenleg??n, mely ??sszeggel az esed??kes d??jak cs??kkenthet??ek, a k??vetkez?? kiegyenl??t??sekor besz??m??tunk. Aj??nl??i linkj??t b??rhol szabadon megoszthatja, ehhez csak jel??lje ki azt, m??solja, majd illessze be kedve szerint, p??ld??ul Facebook, Google+ oldalra, vagy ak??r e-mailbe.</p>
			<p>Your custom referral link: <input type="text" value="https://www.wpcoder.net/?ref=<?php global $current_user; echo $current_user->user_login; ?>" size="42" onclick="select()" /></p>
			<p>Registerd users you invited: -</p>
			<hr />
			<p><b>E-mail fi??k</b></p>
			<p>
				Webmail: <a href="https://www.wpcoder.net/webmail/" target="_blank">www.wpcoder.net/webmail</a>, vagy <a href="<?php echo site_url(); ?>/webmail" target="_blank"><?php echo site_url(); ?>/webmail</a>.
				<br />Felhaszn??l??n??v ide a teljes e-mail c??m, jelsz?? a kor??bban megadott karaktersorozat. A jelsz?? bel??p??s ut??n ak??r meg is v??ltoztathat??. Webmail mellett term??szetesene van POP3 ??s IMAP is, ??gy le lehet k??rni a leveleket telefonra, tabletre, vagy b??rmilyen m??s eszk??zre.
			</p>
			<h3>E-mail fi??kok be??ll??t??sa (POP3/IMAP/SMTP)</h3>
			<p>E-mail c??mmel rendelkez?? ??gyfeleink saj??t levelez??kliens??ket az al??bbiak szerint tudj??k be??ll??tani:</p>
			<ul class="list" style="list-style-type:circle; list-style-position:inside;">
				<li>felhaszn??l??n??v: [adott e-mail c??m, p??ld??ul info@domain.hu]</li>
				<li>jelsz??: [??rtelemszer??en mindig az aktu??lis jelsz??]</li>
				<li>Titkos??t??s n??lk??li kapcsolat eset??n
					<ul>
						<li>szerver: mail.wpcoder.net vagy mail.[sajatdomain.hu]</li>
						<li>port
							<ul>
								<li>IMAP: 143</li>
								<li>POP3: 110</li>
								<li>SMTP: 26</li>
							</ul>
						</li>
					</ul>
				</li>
				<li>SSL/TLS titkos??tott kapcsolat eset??n
					<ul>
						<li>szerver: server4.websitehostserver.net</li>
						<li>port
							<ul>
								<li>IMAP: 993</li>
								<li>POP3: 995</li>
								<li>SMTP: 465</li>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
			<hr />
			<p><b>Hibabejelent??s</b></p>
			<p>Amennyiben b??rmilyen ??szrev??tele, javaslata van, k??rj??k, nyisson ??j hibajegyet (tickettet) <a href="admin.php?page=wprenter" target="_blank">ide kattintva</a>, vagy ??rjon az <a href="mailto:info@wpcoder.net" target="_blank">info@wpcoder.net</a> e-mail c??mre!</p>
			<hr />
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) return;
					js = d.createElement(s); js.id = id;
					js.src = "//connect.facebook.net/hu_HU/sdk.js#xfbml=1&appId=143279985827985&version=v2.0";
					fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));</script>
			<div class="fb-like-box" data-href="https://www.facebook.com/wprentercom" data-width="500" data-colorscheme="light" data-show-faces="true" data-header="true" data-stream="true" data-show-border="true"></div>



			<h2>About the system</h2>
			<ul>
				<li><a target="_blank" href="https://www.wpcoder.net/about/">About WPrenter</a></li>
				<li><a target="_blank" href="https://www.wpcoder.net/documentation/">Documentation</a></li>
			</ul>
			<h2>Pages for renters</h2>
			<ul>
				<li><a href="admin.php?page=marketing_page">Marketing settings</a></li>
				<li><a href="admin.php?page=settings_page">Settings settings</a></li>
			</ul>


			<h2>WPrenter dashboard subpages list</h2>
			<ul>
				<li>Blocks: <a href="<?php echo $this->domain_name; ?>/wp-admin/admin.php?page=wprenter_blocks"><?php echo $this->domain_name; ?>/wp-admin/admin.php?page=wprenter_blocks</a></li>
				<li>E-mail, newsletter: <a href="<?php echo $this->domain_name; ?>/wp-admin/admin.php?page=wprenter_mail"><?php echo $this->domain_name; ?>/wp-admin/admin.php?page=wprenter_mail</a></li>
				<li>WPrenter Settings: <a href="<?php echo $this->domain_name; ?>/wp-admin/admin.php?page=wprenter_settings"><?php echo $this->domain_name; ?>/wp-admin/admin.php?page=wprenter_settings</a></li>
			</ul>
			<h2>WPrenter useful links</h2>
			<ul>
				<li>WPrenter documentation page: <a target="_blank" href="https://www.wpcoder.net/documentation/">https://www.wpcoder.net/documentation/</a></li>
			</ul>
			<hr>
			<h2>Tickets</h2>
			<form>
				<textarea></textarea><br>
				<button type="submit" class="button button-primary">Submit ticket</button>
			</form>
		</div>
	<?php }
}
