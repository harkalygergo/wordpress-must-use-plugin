<?php

// EXTRA SETTINGS
/*
* ADD THESE LINES TO wp-config.php FILE
define('WP_POST_REVISIONS', 1); // set max post revision to 1
// SECURITY SETTINGS
define('DISALLOW_FILE_EDIT', true); // disable themes' and plugins' file editor
define('DISABLE_WP_CRON', true); // disable WP cron, use wp-cron-multisite.php instead
define('WP_AUTO_UPDATE_CORE', true); // enable all core updates, including minor and major
define('WP_CONTENT_DIRECTORY', 'content');
define('WP_CONTENT_DIR', ABSPATH . WP_CONTENT_DIRECTORY); // rename wp-content folder and redefine wp-content path
define('WP_CONTENT_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's://' : '://') . $_SERVER['HTTP_HOST'] .'/' . WP_CONTENT_DIRECTORY);

ADD TO .htaccess
<Files admin-ajax.php>
	Order allow,deny
	Allow from all
	Satisfy any
</Files>
order deny,allow
deny from all
<files ~ ".(xml|css|jpe?g|png|gif|js)$">
allow from all
</files>
AuthName "admin + 1234"
AuthType Basic
AuthUserFile .htpasswd
Require valid-user

ADD TO .htpasswd
#admin + 1234
admin:$apr1$XdwSCQFU$NJclZS7Og0VzuDOF8nPla0';
*/

class WPrenterSecurity
{
	private $captcha_key1, $captcha_key2;

	public function __construct()
	{

		//Remove All Meta Generators
		ini_set('output_buffering', 'on'); // turns on output_buffering

		add_action('get_header', array(&$this, 'clean_meta_generators'), 100);
		add_action('wp_footer', function(){ ob_end_flush(); }, 100);
		add_filter( 'registration_errors', array(&$this, 'filter_registration_errors'), 1, 3 );


		remove_action('wp_head', 'wp_generator'); // remove generator version from header
		add_filter('the_generator', '__return_empty_string'); // remove version from rss
		add_action('template_redirect', array(&$this, 'action_template_redirect')); // redirects ?author= URLs to homepage to avoid getting author names

		add_filter('auto_update_plugin', '__return_true'); // enable automatic updates for plugins
		add_filter('auto_update_theme', '__return_true'); // enable automatic updates for themes

		add_filter('pre_comment_content', array(&$this, 'filter_pre_comment_content'), 9999); // a security precaution to stop comments that are too long

		add_action('admin_footer-post-new.php', array($this, 'action_admin_footer_post_new_php')); // add functions to Dashboard's footer on post pages

		// captcha on login form
		add_action('login_form', array($this, 'action_login_form'));
		add_action('woocommerce_login_form', array($this, 'action_login_form'));
		add_action('wp_authenticate_user', array($this, 'action_wp_authenticate_user'), 10, 2);

		// remove query strings from URLs || https://kinsta.com/knowledgebase/remove-query-string-from-url/
		add_filter('script_loader_src', array(&$this, 'filter_script_loader_src_style_loader_src'), 15, 1);
		add_filter('style_loader_src', array(&$this, 'filter_script_loader_src_style_loader_src'), 15, 1);

	}

	public function filter_registration_errors( $errors, $sanitized_user_login, $user_email )
	{
		$false_login_cooke_value = 0;
		if( isset( $_COOKIE['false_login'] ) )
		{
			$false_login_cooke_value = (int)$_COOKIE['false_login']+1;
			unset( $_COOKIE['false_login'] );
			if( $false_login_cooke_value>5 )
			{
				header('Location:http://zoldmertekszolnok.hu/');
				exit;
			}
		}
		setcookie("false_login", $false_login_cooke_value);
		$bad_domains = ['.ch', '.ru', 'yahoo.com', 'hotmail.com', 'spirits.com', 'msn.com', 'maryannk4802'];
		foreach( $bad_domains as $bad_domain )
		{
			if ( strpos( $user_email, $bad_domain ) !== false )
			{
				$errors->add( 'bad_email_domain', '<strong>ERROR</strong>: This email domain is not allowed.' );
			}
		}
		return $errors;
	}

	public function remove_meta_generators($html) {
		$pattern = '/<meta name(.*)=(.*)"generator"(.*)>/i';
		$html = preg_replace($pattern, '', $html);
		return $html;
	}
	public function clean_meta_generators($html) {
		ob_start(array(&$this,'remove_meta_generators'));
	}

	// redirects ?author= URLs to homepage to avoid getting author names
	public function action_template_redirect()
	{
		if (is_author())
		{
			wp_redirect(home_url());
			exit();
		}
	}

	public function action_admin_footer_post_new_php()
	{ ?>
		<!-- set new post's author the first user, which is not admin, just for security options -->
		<script>
			document.getElementById('post_author_override').value = '1';
		</script>
	<?php }

	public function filter_pre_comment_content( $text )
	{
		if(strlen($text) > 13000 )
		{
			wp_die(
			/*message*/ 'This comment is longer than the maximum allowed size and has been dropped.',
				/*title*/ 'Comment Declined',
				/*args*/ array( 'response' => 413 )
			);
		}
		return $text;
	}

	public function action_login_form()
	{
		$this->captcha_key1 = rand(1, 10);
		$this->captcha_key2 = rand(1, 10);
		?>
		<p>
			<label for="user_captcha">Captcha</label>
			<input type="text" name="user_captcha" id="user_captcha" class="input" placeholder="<?php echo $this->captcha_key1.'+'.$this->captcha_key2.'=?';?>" required>
			<input type="hidden" name="captcha_result" value="<?php echo $this->captcha_key1+$this->captcha_key2; ?>" required>
		</p>
	<?php }
	public function action_wp_authenticate_user($user, $password)
	{
		if(!isset($_POST['user_captcha']) || empty($_POST['user_captcha']) || !isset($_POST['captcha_result']) || empty($_POST['captcha_result']))
		{
			return new \WP_Error('empty_captcha', 'CAPTCHA should not be empty');
		}
		if(isset($_POST['user_captcha']) && isset($_POST['captcha_result']) && $_POST['user_captcha'] != $_POST['captcha_result'])
		{
			return new \WP_Error('invalid_captcha', 'CAPTCHA response was incorrect');
		}
		return $user;
	}

	// remove query strings from URLs || https://kinsta.com/knowledgebase/remove-query-string-from-url/
	public function filter_script_loader_src_style_loader_src($src)
	{
		if(!is_admin())
		{
			$src_explode = explode('?ver=', $src);
			$parts_explode = explode('.', $src_explode ['0']);
			if(end($parts_explode)==='css' || end($parts_explode)==='js')
			{
				$src = $src_explode['0'];
			}
		}
		return $src;
	}
}