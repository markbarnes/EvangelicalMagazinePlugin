<?php
/**
Plugin Name: Evangelical Magazine
Description: Customisations for the Evangelical Magazine
Plugin URI: http://www.evangelicalmagazine.com/
Version: 0.2
Author: Mark Barnes
Author URI: http://www.markbarnes.net/
*/

/**
* This class contains all the main functions for the plugin, mostly as static methods.
* We're using a class mostly to avoid name clashes.
*/
class evangelical_magazine {

	/**
	* Main hooks and activation
	*
	* @return void
	*/
	public function __construct() {
		//Make sure classes autoload
		spl_autoload_register(array(__CLASS__, 'autoload_classes'));

		// Register activation/deactivation hooks
		register_activation_hook (__FILE__, array(__CLASS__, 'on_activation'));
		register_deactivation_hook ( __FILE__, array(__CLASS__, 'on_deactivation'));

		// Add main actions
		add_action ('evangelical_magazine_activate', array(__CLASS__, 'flush_rewrite_rules'));
		add_action ('init', array (__CLASS__, 'register_custom_post_types'));
		add_action ('admin_init', array (__CLASS__, 'setup_custom_post_type_columns'));
		add_action ('save_post', array(__CLASS__, 'save_cpt_data'));
		add_action ('admin_menu', array(__CLASS__, 'remove_admin_menus'));
		add_action ('rss2_ns', array(__CLASS__, 'add_mediarss_namespace'));
		add_action ('atom_ns', array(__CLASS__, 'add_mediarss_namespace'));
		add_action ('rss2_item', array(__CLASS__, 'add_featured_image_to_rss'));
		add_action ('atom_entry', array(__CLASS__, 'add_featured_image_to_rss'));

		//Configure WP Cron
		add_action ('evangelical_magazine_cron', array (__CLASS__, 'update_all_stats_for_articles_static'));
		if (!wp_next_scheduled('evangelical_magazine_cron')) {
			wp_schedule_event (time(), 'twicedaily', 'evangelical_magazine_cron');
		}

		//Add filters
		add_filter ('sanitize_title', array(__CLASS__, 'pre_sanitize_title'), 9, 3);
		add_filter ('the_author', array (__CLASS__, 'filter_author_name'));
		add_filter ('the_content_feed', array (__CLASS__, 'filter_feed_for_mailchimp'));

		//Image sizes
		add_image_size ('article_rss', 560, 373, true);

		//Instant articles
		$ia = new evangelical_magazine_facebook_instant_articles();

		//Google Analytics
		$this->use_google_analytics = file_exists($this->plugin_dir_path('google-api-credentials.json')) && file_exists($this->plugin_dir_path('libraries/google-api-php-client/vendor/autoload.php'));
		if ($this->use_google_analytics) {
			require_once $this->plugin_dir_path('libraries/google-api-php-client/vendor/autoload.php');
			$this->analytics = new evangelical_magazine_google_analytics();
		}
	}

	/**
	* Runs when plugin is activated. Can be extended through actions.
	*
	* @return void
	*/
	public static function on_activation() {
		do_action ('evangelical_magazine_activate');
		add_role ('subs-admin', 'Subscriptions administrator', array ('gravityforms_view_entries' => true, 'gravityforms_edit_entries' => true, 'gravityforms_delete_entries' => true, 'gravityforms_export_entries' => true, 'gravityforms_view_entry_notes' => true, 'gravityforms_edit_entry_notes' => true, 'gravityforms_preview_forms' => true));
	}

	/**
	* Runs when plugin is deactivated. Can be extended through actions.
	*
	* @return void
	*/
	public static function on_deactivation() {
		do_action ('evangelical_magazine_deactivate');
		remove_role ('subs-admin');
		if ($next_cron_time = wp_next_scheduled ('evangelical_magazine_cron')) {
			wp_unschedule_event($next_cron_time, 'evangelical_magazine_cron');
		};
	}

	/**
	* Autoloads classes
	*
	* @param string $class_name
	* @return void
	*/
	public static function autoload_classes ($class_name) {
		$prefix  = 'evangelical_magazine_';
		if (strpos($class_name, $prefix) === 0) {
			require (plugin_dir_path(__FILE__).'classes/'.substr($class_name, strlen($prefix))).'.php';
		}
	}
	/**
	* Returns the URL of this plugin
	*
	* @param string $path
	* @return string
	*/
	function plugin_url($path) {
		return plugins_url($path, __FILE__);
	}

	/**
	* Returns the path to the plugin, or to a specified file or folder within it
	*
	* @param string $relative_path - a file or folder within the plugin
	* @return string
	*/
	public static function plugin_dir_path ($relative_path = '') {
		return plugin_dir_path(__FILE__).$relative_path;
	}

	/**
	* Flushes the rewrite rules
	*
	* @return void
	*/
	public static function flush_rewrite_rules () {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	/**
	* Generates an array of labels for custom post types
	*
	* @param string $plural - the plural form of the word used in the labels
	* @param string $singular - the singular form of the word used in the labels
	* @return array - the label array
	*/
	static function generate_post_label ($plural, $singular) {
		$singular_l = strtolower($singular);
		$plural_l = strtolower($plural);
		return array( 'name' => $plural,
		              'singular_name' => $singular,
		              'add_new' => 'Add New',
		              'add_new_item' => "Add New {$singular}",
		              'edit_item' => "Edit {$singular}",
		              'new_item' => "New {$singular}",
		              'view_item' => "View {$singular}",
		              'search_items' => "Search {$plural}",
		              'not_found' => "No {$plural} Found",
		              'not_found_in_trash' => "No {$plural} Found in Trash",
		              'parent_item_colon' => "Parent {$singular}:",
		              'menu_name' => $plural,
		              'featured_image' => "{$singular} image",
		              'set_featured_image' => "Set {$singular_l} image",
		              'remove_featured_image' => "Remove {$singular_l} image",
		              'use_featured_image' => "Use {$singular_l} image",
		              'archives' => "{$singular} Archive",
		              'insert_into_item' => "Insert into {$singular_l}",
		              'uploaded_to_this_item' => "Uploaded to this {$singular_l}",
		              'filter_items_list' => "Filter {$plural_l} list",
		              'items_list_navigation' => "{$plural} list navigation",
		              'items_list' => "{$plural} list");
	}

	/**
	* Registers the custom post types
	*
	* @return void
	*/
	static function register_custom_post_types() {
		//Sections
		$args = array ( 'label' => 'Sections',
						'labels' => self::generate_post_label ('Sections', 'Section'),
						'description' => 'Information about each section is stored here',
						'public' => true,
						'show_in_menu' => true,
						'exclude_from_search' => true,
						'menu_position' => 4,
						'menu_icon' => 'dashicons-portfolio',
						'supports' => array ('title', 'editor'),
						'has_archive' => true,
						'query_var' => 'sections',
						'rewrite' => array('slug' => 'sections', 'with_front' => false));
		register_post_type ('em_section', $args);
		//Issues
		$args = array ( 'label' => 'Issues',
						'labels' => self::generate_post_label ('Issues', 'Issue'),
						'description' => 'Information about each issue is stored here',
						'public' => true,
						'show_in_menu' => true,
						'menu_position' => 5,
						'menu_icon' => 'dashicons-id-alt',
						'supports' => array ('title', 'thumbnail', 'editor'),
						'has_archive' => true,
						'query_var' => 'issues',
						'register_meta_box_cb' => array ('evangelical_magazine_issue', 'issue_meta_boxes'),
						'rewrite' => array('slug' => 'issues', 'with_front' => false));
		register_post_type ('em_issue', $args);
		//Series
		$args = array ( 'label' => 'Series',
						'labels' => self::generate_post_label ('Series', 'Series'),
						'description' => 'Information about each series is stored here',
						'public' => false,
						'show_ui' => true,
						'show_in_menu' => true,
						'menu_position' => 6,
						'menu_icon' => 'dashicons-index-card',
						'supports' => array ('title', 'editor'),
						'has_archive' => false);
		register_post_type ('em_series', $args);
		//Authors
		$args = array ( 'label' => 'Authors',
						'labels' => self::generate_post_label ('Authors', 'Author'),
						'description' => 'Information about each author is stored here',
						'public' => true,
						'show_in_menu' => true,
						'menu_position' => 7,
						'menu_icon' => 'dashicons-admin-users',
						'supports' => array ('title', 'thumbnail', 'editor'),
						'has_archive' => true,
						'query_var' => 'authors',
						'rewrite' => array('slug' => 'authors', 'with_front' => false));
		register_post_type ('em_author', $args);
		//Articles
		$args = array ( 'label' => 'Articles',
						'labels' => self::generate_post_label ('Articles', 'Article'),
						'description' => 'Information about each article is stored here',
						'public' => true,
						'show_in_menu' => true,
						'menu_position' => 8,
						'menu_icon' => 'dashicons-media-text',
						'supports' => array ('title', 'thumbnail', 'editor'),
						'has_archive' => true,
						'query_var' => 'articles',
						'register_meta_box_cb' => array ('evangelical_magazine_article', 'article_meta_boxes'),
						'rewrite' => array('slug' => 'article', 'with_front' => false));
		register_post_type ('em_article', $args);
	}

	/**
	* Sets up the actions and filters required to add custom columns to the Edit Articles page
	*
	* @return void
	*/
	public static function setup_custom_post_type_columns() {
		global $evangelical_magazine;
		add_filter ('manage_edit-em_article_columns', array ('evangelical_magazine_article', 'filter_columns'));
		add_action ('manage_em_article_posts_custom_column', array ('evangelical_magazine_article', 'output_columns'), 10, 2);
		add_filter ('manage_edit-em_article_sortable_columns', array ('evangelical_magazine_article', 'make_columns_sortable'));
		add_action ('pre_get_posts', array ('evangelical_magazine_article', 'sort_by_columns'));
		add_action ('admin_head', array (__CLASS__, 'add_styles_to_admin_head'));
		add_filter ('post_row_actions', array (__CLASS__, 'adds_recalc_stats_to_article_actions'), 10, 2);
		if (isset($_GET['recalc_stats']) && is_admin()) {
			$article = new evangelical_magazine_article((int)$_GET['recalc_stats']);
			if ($article) {
				delete_transient($article->get_facebook_transient_name());
				if ($evangelical_magazine->use_google_analytics) {
					delete_transient($article->get_google_analytics_transient_name());
				}
				$evangelical_magazine->update_all_stats_if_required ($article->get_id());
			}
		}
	}

	/**
	* Makes sure all the additional metabox data is saved.
	*
	* @param integer $post_id - the current post_id
	* @return void
	*/
	public static function save_cpt_data ($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
		if (isset($_POST['post_type']) && $_POST['post_type'] == 'em_article') {
			$article = new evangelical_magazine_article ($post_id);
			$article->save_meta_data();
		} elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'em_issue') {
			$issue = new evangelical_magazine_issue ($post_id);
			$issue->save_meta_data();
		}
	}

	/**
	* Returns true if we're creating a post in admin
	*
	* @return boolean
	*/
	public static function is_creating_post() {
		$screen = get_current_screen();
		if ($screen->action == 'add' && $screen->base == 'post') {
			return true;
		} else {
			return false;
		}
	}

	/**
	* Removes unwanted admin menus
	*
	* @return void
	*/
	public static function remove_admin_menus() {
		remove_menu_page ('edit.php');
		remove_menu_page ('edit-comments.php');
	}

	/**
	* Returns the appropriate object when all we know is the ID
	*
	* @param int $id - the post_id
	* @return mixed - the object with that id
	*/
	public static function get_object_from_id($id) {
		$post = get_post($id);
		return self::get_object_from_post($post);
	}

	/**
	* Converts a WP_Post object to the correct magazine object
	*
	* @param WP_Post $post - a post object
	* @return mixed - an evangelical_magazine_ object, or false if the post_id if not
	*/
	public static function get_object_from_post($post) {
		if (is_a($post, 'WP_Post')) {
			if (substr ($post->post_type, 0, 3) == 'em_') {
				$class_name = 'evangelical_magazine_'.substr($post->post_type, 3);
				return new $class_name ($post);
			}
		}
		return false;
	}

	/**
	* Makes sure '/' characters are replaced with '-' characters when sanitising.
	*
	* Useful for creating slugs for magazine issues (e.g. 'September/October')
	* Filters sanitize_title.
	*
	* @param string $title - the title of this post
	* @param string $raw_title
	* @param string $context
	* @return string
	*/
	public static function pre_sanitize_title ($title, $raw_title = '', $context = 'display') {
		return str_replace ('/', '-', $title);
	}

	/**
	* Adds styling to the admin head.
	*
	* @return void
	*/
	public static function add_styles_to_admin_head () {
		echo '<style type="text/css">.column-title {width: 30%}</style>';
	}

	/**
	* Outputs the mediaRSS namespace to the RSS feeds
	* Called on the rss2_ns and atom_ns actions
	*
	* @return void
	*/
	public static function add_mediarss_namespace() {
		echo "xmlns:media=\"http://search.yahoo.com/mrss/\"\r\n";
	}

	/**
	* Adds the featured image to the RSS feeds
	* Adds both a media:content and enclosure
	*
	* @return void
	*/
	public static function add_featured_image_to_rss () {
		global $post;
		if ($object = self::get_object_from_post($post)) {
			$image = $object->get_image_details ('article_rss');
			if ($image) {
				echo "<media:content url=\"{$image['url']}\" type=\"{$image['mimetype']}\" height=\"{$image['height']}\" width=\"{$image['width']}\" />\r\n";
				echo "\t\t<enclosure url=\"{$image['url']}\" type=\"{$image['mimetype']}\" length=\"{$image['filesize']}\" />\r\n";
			}
		}
	}

	/**
	* Filters the authors names
	*
	* Replaces the post author with the actual author(s) of the article.
	* Filters the_author
	*
	* @param string $display_name - the display name of the author
	* @return string - the new display name
	*/
	public static function filter_author_name ($display_name) {
		global $post;
		if ($object = self::get_object_from_post($post)) {
			if ($object->is_article()) {
				return $object->get_author_names();
			} else {
				return 'editor';
			}
		} else {
			return $display_name;
		}
	}

	/**
	* Adds the 'recalc_stats' row action to articles
	* Filters post_row_actions
	*
	* @param array $actions - the existing actions
	* @param WP_Post $post - the current post
	* @return array - the filtered actions
	*/
	public static function adds_recalc_stats_to_article_actions ($actions, $post) {
		global $current_screen;
		if ($post->post_type == 'em_article') {
			$possible_variables = array ('paged', 'orderby', 'order', 'author', 'all_posts', 'post_status');
			$arguments = array('recalc_stats' => $post->ID);
			foreach ($possible_variables as $p) {
				if (isset($_GET[$p])) {
					$arguments[$p] = $_GET[$p];
				}
			}
			$url = esc_url(add_query_arg ($arguments, admin_url ($current_screen->parent_file)));
			$actions ['recalc_stats'] = "<a href=\"{$url}\">Recalc stats</a>";
		}
		return $actions;
	}

	/**
	* Enables an "?output=excerpt" parameter on the main feed, so it's possible to have both a full RSS feed and a excerpted one.
	* The resulting feed is fed to MailChimp.
	*
	* @param string $content
	* @return string
	*/
	public static function filter_feed_for_mailchimp ($content) {
		global $post;
		if (isset($_GET['output']) && $_GET['output']=='excerpt') {
			$link = esc_url (get_permalink());
			$content = strip_shortcodes ($content);
			$content = apply_filters ('the_content', $content);
			$content = str_replace(']]>', ']]&gt;', $content);
			$content = strip_tags ($content, '<em>,<b>,<i>,<strong>,<br>,<p>,<ul>,<ol>,<li>,<h1>,<h2>,<h3>,<h4>,<h5>');
			$max_words = 175;
			$tokens = array();
			preg_match_all('/(<[^>]+>|[^<>\s]+)\s*/u', $content, $tokens);
			$output = '';
			$count = 0;
			foreach ($tokens[0] as $token) {
				if ($count >= $max_words && preg_match('/[\,\;\?\.\!]\s*$/uS', $token)) {
					$output .= trim($token);
					break;
				}
				$count++;
				$output .= $token;
			}
			$output .= "&hellip; <a href=\"{$link}\">(continue reading)</a>";
			$content = trim(force_balance_tags($output));
			return $content;
		} else {
			return $content;
		}
	}

	/**
	* Updates the Facebook stats for multiple objects in one request
	*
	* @param array $ids - an array of post ids or valid evangelical_magazine_* objects
	* @return void
	*/
	public function update_facebook_stats_if_required ($ids) {
		$chunks = array_chunk ($ids, 50);
		foreach ($chunks as $chunk) {
			$requests = $objects = array();
			foreach ($chunk as $id) {
				if (gettype ($id) == 'object') {
					/** @var evangelical_magazine_article[] $objects */
					$objects[] = $id;
				} else {
					$objects[] = SELF::get_object_from_id($id);
				}
			}
			foreach ($objects as $key => $object) {
				if (!$object->has_valid_facebook_stats()) {
					$url = apply_filters ('evangelical_magazine_url_for_facebook', $object->get_link());
					$requests[] = array ('method' => 'GET', 'relative_url' => '?id='.urlencode($url).'&fields=share');
					$lookup [$url] = $key;
				}
			}
			if ($requests) {
				$args['access_token'] = evangelical_magazine_fb_access_tokens::get_app_id().'|'.evangelical_magazine_fb_access_tokens::get_app_secret();
				$args['batch'] = json_encode($requests);
				$stats = wp_remote_post('https://graph.facebook.com/v2.8/', array ('body' => $args));
				if (!is_a($stats, 'WP_Error')) {
					$response = json_decode($stats['body']);
					if (!isset($response->error)) {
						foreach ((array)$response as $r) {
							$stats = json_decode($r->body);
							if ($stats !== NULL && isset($stats->share)) {
								$objects[$lookup[$stats->id]]->update_facebook_stats ($stats->share->share_count);
							} else {
								$objects[$lookup[$stats->id]]->update_facebook_stats (0);
							}
						}
					}
				}
			}
		}
	}

	/**
	* Updates the Google Analytics stats for multiple objects in one request
	*
	* @param array $ids - an array of post ids or valid evangelical_magazine_* objects
	* @return void
	*/
	public function update_google_analytics_stats_if_required ($ids) {
		$urls = $objects = $index = array();
		foreach ($ids as $id) {
			if (gettype ($id) == 'object') {
				$objects[$id->get_id()] = $id;
			} else {
				/**	@var evangelical_magazine_article[]	*/
				$objects[$id] = SELF::get_object_from_id($id);
			}
		}
		foreach ($objects as $key => $object) {
			if (!$object->has_valid_google_analytics_stats()) {
				$url = apply_filters ('evangelical_magazine_url_for_google_analytics', $object->get_link());
				$all_urls[] = $url;
				$index[wp_parse_url($url, PHP_URL_PATH)] = $object->get_id();
			}
		}
		if (isset($all_urls) && $all_urls) {
			$chunks = array_chunk ($all_urls, 10);
			foreach ($chunks as $chunked_urls) {
				$stats = $this->analytics->get_page_views($chunked_urls);
				foreach ($stats as $path => $count) {
					$objects[$index[$path]]->update_google_analytics_stats ($count);
				}
			}
		}
	}

	/**
	* Updates all Facebook and Google Analytics stats that are out of date
	*
	* @param int[]|evangelical_magazine_article[] $ids - an array of post ids or valid evangelical_magazine_article objects
	* @return void
	*/
	public function update_all_stats_if_required ($ids) {
		if (!is_array($ids)) {
			$ids = (array)$ids;
		}
		$this->update_facebook_stats_if_required($ids);
		if ($this->use_google_analytics) {
			$this->update_google_analytics_stats_if_required($ids);
		}
	}

	/**
	* Updates all Facebook and Google Analytics stats that are out of date
	* Can be called statically.
	*
	* @param int[]|evangelical_magazine_article[] $ids - an array of post ids or valid evangelical_magazine_article objects
	* @return void
	*/
	public static function update_all_stats_for_articles_static() {
		global $evangelical_magazine;
		$all_articles = evangelical_magazine_article::get_all_articles(array('post_status' => 'publish'));
		$evangelical_magazine->update_all_stats_if_required($all_articles);
	}
}

// Initialise
$evangelical_magazine = new evangelical_magazine();