<?php

require_once ( __DIR__.'/WPrenter.class.php' );
class WPrenterTheme extends WPrenter
{
	public function __construct()
	{
		add_action('login_head', array(&$this, 'action_login_head'));
		add_action('wp_head', array($this, 'action_wp_head') );
		add_action('wp_footer', array($this, 'action_wp_footer') );
		add_filter('widget_text', 'do_shortcode'); // add shortcode support for widgets
		// shortcode
		add_shortcode( 'wprform', array( &$this, 'wprformfrontend') );
		parent::__construct();
	}

	public function action_wp_footer()
	{
	}

	public function action_login_head()
	{
		// custom login
		?>
		<style>
			body.login { background-image:url("<?php echo WPMU_PLUGIN_URL; ?>/WPrenter/app/core/img/bg<?php echo date('m'); ?>.jpg"); -webkit-background-size: cover; background-size: cover; }
			body.login h1 { display:none; }
			body.login div#login form#loginform { border-radius:5px; }
			body.login p#nav a, body.login p#backtoblog a { background-color:white; padding:5px; border-radius:5px; }
		</style>
	<?php }

	public function wprformfrontend( $attributes )
	{
		$json = json_decode( get_post($attributes['block'])->post_content, true );
		$content = '';
		$content .= "<form onsubmit='return formapisubmit(\"form{$attributes['block']}\");' id='form{$attributes['block']}'><table>";
		foreach ( $json['inputs'] as $input )
		{
			$sanitize_title = str_replace( '-', '_', sanitize_title( $input['name'] ) );
			if( $input['type']!=='select' )
			{
				$content .= "<tr><td>{$input['name']}:</td><td><input id='{$sanitize_title}' name='{$sanitize_title}' type='{$input['type']}'></td></tr>";
			}
			else
			{
				$content .= "<tr><td>{$input['name']}:</td><td><select id='{$sanitize_title}' name='{$sanitize_title}'><option>-</option>";
				// if there is optgroups
				if ( is_array($input['options']) )
				{
					foreach( $input['options'] as $optgroup=>$options )
					{
						$content .= "<optgroup label='{$optgroup}'>";

						$options = explode( ';', $options );
						foreach( $options as $option )
						{
							$content .= "<option value='{$option}'>{$option}</option>";
						}

						$content .= "</optgroup>";
					}
				}
				else
				{
					$options = explode( ';', $input['options'] );
					foreach( $options as $option )
					{
						$content .= "<option value='{$option}'>{$option}</option>";
					}
				}
				$content .= "</select></td></tr>";
			}
		}
		$content .= '</table><button type="submit">Küldés</button></form><p id="result" style="display: none;">**</p>';
		ob_start();
		?>
		<script>
			// wprformfrontend script
			function formapisubmit( formID )
			{
				jQuery('#'+formID ).find('button').text('⌛');
				jQuery.ajax({
					url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
					type: "POST",
					dataType: 'type',
					data: {
						action: "wprform",
						postID: <?php echo $attributes['block']; ?>,
						data: jQuery('#'+formID ).serializeArray()
					},
					success: function(response){
						document.getElementById("result").innerHTML = response;
						document.getElementById("result").style.display = "block";
						//jQuery("#result").val(response).css("display", "block");
						setTimeout(function (){ jQuery('#'+formID ).find('button').text('Küldés'); }, 2000);
						return false;
					}, error: function(response){
						console.warn(response);
						document.getElementById("result").innerHTML = response.responseText;
						document.getElementById("result").style.display = "block";
						//jQuery("#result").val(data).css("display", "block");
						return false;
					}
				});
				//jQuery('.ajax')[0].reset();
				return false;
			}
		</script>
		<?php
		$content .= ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function action_wp_head()
	{
		$wprenter_settings = json_decode( get_option('wprenter_settings'), true);
		global $post, $wp;
		/* ?>
		<!--meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
		<meta name="description" content="<?php if(is_home()) { bloginfo('description'); } else { echo getPostExcerptOutsideLoop($post->ID, 160, FALSE); } ?>">
		<meta name="keywords" content="<?php $posttags=get_the_tags(); if($posttags) { foreach($posttags as $tag) { echo $tag->name.', '; } } ?>">
		<meta name="author" content="<?php the_author_meta('display_name', $post->post_author); ?>">
		<meta name="robots" content="all">
		<meta name="revisit-after" content="30 days"-->

		<!-- Google Search Console -->
		<!--meta name="google-site-verification" content="<?php echo get_theme_mod('GoogleSiteVerification'); ?>">
		<?php */ ?>
		<?php echo isset($wprenter_settings['head_html']) ? $wprenter_settings['head_html'] : '';
		$description =
			is_home() ? get_bloginfo('description') :
				( !is_null($post) ? $this->getPostExcerptOutsideLoop($post->ID, 600) : '' );
		?>
		<meta name="description" content="<?php echo $description; ?>">

		<!-- Facebook OpenGraph | https://developers.facebook.com/docs/sharing/best-practices -->
		<?php if(!is_null($wprenter_settings) && array_key_exists('facebookappid', $wprenter_settings)) { ?>
			<meta property="fb:app_id" content="<?php echo $wprenter_settings['facebookappid']; ?>">
		<?php } ?>
		<meta property="og:url" content="<?php echo home_url(add_query_arg(array(), $wp->request)); ?>">
		<meta property="og:title" content="<?php if(is_front_page()) { bloginfo('name'); echo ' | '; bloginfo('description'); } else { wp_title('|', true, 'right'); bloginfo('name'); } ?>">
		<meta property="og:site_name" content="<?php bloginfo('name'); ?>">
		<meta property="og:description" content="<?php echo $description; ?>">
		<meta property="og:locale" content="<?php echo get_locale(); ?>">
		<?php
		if(is_single() || is_page())
		{ ?>
			<meta property="og:type" content="article">
			<meta property="og:image" content="<?php
			if(is_front_page())
			{
				$wp_get_attachment_image_src = wp_get_attachment_image_src( get_post_thumbnail_id( get_option('page_on_front') ), 'full' );
				if(is_array($wp_get_attachment_image_src))
				{
					echo $wp_get_attachment_image_src['0'];
				}
			}
			else
			{
				if(has_post_thumbnail($post->ID))
				{
					echo wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full')['0'];
				}
			}
			?>">
			<meta property="article:author" content="<?php //echo get_theme_mod('facebookpageURL'); ?>">
			<meta property="article:publisher" content="<?php //echo get_theme_mod('facebookpageURL');?>">
			<meta property="article:published_time" content="<?php echo get_the_date('Y-m-d H:i:s'); the_date('Y-m-d H:i:s'); ?>">
			<meta property="article:modified_time" content="<?php the_modified_date('Y-m-d H:i:s'); ?>">
			<!--<meta property="article:expiration_time" content="">-->
			<meta property="article:section" content="">
			<meta property="article:tag" content="<?php $posttags=get_the_tags(); if($posttags) { foreach($posttags as $tag) { echo $tag->name.', '; } } ?>">
			<?php
		}
		else
		{ ?>
			<meta property="og:type" content="website">
		<?php }
	}
	// get post's excerpt outside the loop
	public function getPostExcerptOutsideLoop($postID, $ExcerptLength=500 )
	{
		$post = get_post($postID);
		if($post->post_excerpt=='')
		{
			// Get content parts
			$content_parts = get_extended( $post->post_content );
			$excerpt = strip_tags($content_parts['main']);

			// drop contents after first line break
			$excerpt_explode = explode("\n", $excerpt);
			$excerpt = ( $excerpt_explode['0']=='  ' ) ? $excerpt_explode['1'] : $excerpt_explode['0'];
			if ( strlen( $excerpt ) < $ExcerptLength )
			{
				$excerpt = strip_tags( $excerpt );
			}
			else
			{
				$excerpt = substr( strip_tags( $excerpt ), 0, $ExcerptLength );
			}
		}
		else
		{
			$excerpt = strip_tags($post->post_excerpt);
		}
		$excerpt = preg_replace( "/\r|\n/", " ", $excerpt );
		// escape HTML characters
		//return htmlspecialchars($excerpt);
		return $excerpt;
	}



}
