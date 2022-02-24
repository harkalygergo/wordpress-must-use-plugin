<?php

class WPcoderSEO
{
	public function __construct()
	{
		add_action( 'wp_loaded', array( &$this, 'action_wp_loaded' ) );
	}

	public function action_wp_loaded()
	{
		// generate sitemap XML
		if( in_array( $_SERVER['REQUEST_URI'], ['/sitemap', '/sitemap.xml'] ) )
		{
			header("Content-type: text/xml");
			$post_to_sitemap = new WP_Query(array(
					'post_type' => ['post', 'page', 'product'],
					'post_status' => 'publish',
					'posts_per_page' => 10000,
					'orderby' => 'title',
					'order' => 'ASC',
				)
			);
			$main_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/";
			echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			echo "\n\t<url><loc>{$main_url}</loc><priority>1.00</priority></url>";
			foreach ($post_to_sitemap->posts as $post) {
				echo sprintf("\n\t<url><loc>%s</loc><lastmod>%s</lastmod><priority>%s</priority></url>",
					get_permalink($post->ID),
					str_replace(' ', 'T', $post->post_modified_gmt) . '+00:00',
					0.8
				);
			}
			echo "\n</urlset>";
			wp_reset_query();
			wp_reset_postdata();
			exit;
		}
	}
}