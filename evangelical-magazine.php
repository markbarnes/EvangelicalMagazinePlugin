<?php
/**
Plugin Name: Evangelical Magazine
Description: Customisations for the Evangelical Magazine
Plugin URI: http://www.evangelicalmagazine.com/
Version: 1.03
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
		add_action ('init', array (__CLASS__, 'add_mailchimp_rss_feed'));
		add_action ('admin_init', array (__CLASS__, 'setup_custom_post_type_columns'));
		add_action ('save_post', array(__CLASS__, 'save_cpt_data'));
		add_action ('admin_menu', array(__CLASS__, 'remove_admin_menus'));
		add_action ('admin_menu', array(__CLASS__, 'add_admin_menu_separator'));
		add_action ('rss2_ns', array(__CLASS__, 'add_mediarss_namespace'));
		add_action ('atom_ns', array(__CLASS__, 'add_mediarss_namespace'));
		add_action ('rss2_item', array(__CLASS__, 'add_featured_image_to_rss'));
		add_action ('atom_entry', array(__CLASS__, 'add_featured_image_to_rss'));
		add_action ('admin_bar_menu', array(__CLASS__, 'add_toolbar_items'), 40);

		//Configure WP Cron
		add_action ('evangelical_magazine_cron', array (__CLASS__, 'update_all_stats_for_articles_static'));
		if (!wp_next_scheduled('evangelical_magazine_cron')) {
			wp_schedule_event (time(), 'twicedaily', 'evangelical_magazine_cron');
		}

		//Add filters
		add_filter ('sanitize_title', array(__CLASS__, 'pre_sanitize_title'), 9, 3);
		add_filter ('the_author', array (__CLASS__, 'filter_author_name'));
		add_filter ('enter_title_here', array ('evangelical_magazine_review', 'filter_title_placeholder'));
		add_filter ('the_title', array ('evangelical_magazine_review', 'add_review_type_to_title'), 10, 2);

		//Revelanssi filters
		add_filter ('relevanssi_index_custom_fields', array (__CLASS__, 'add_custom_fields_to_relevanssi'));

		//Image sizes
		add_image_size ('article_rss', 560, 280, true);

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
	* Generates an array of labels for custom taxonomies
	*
	* @param string $plural - the plural form of the word used in the labels
	* @param string $singular - the singular form of the word used in the labels
	* @return array - the label array
	*/
	static function generate_taxonomy_label ($plural, $singular) {
		$plural_l = strtolower($plural);
		return array( 'name' => $plural,
		              'singular_name' => $singular,
		              'all_items' => "All {$plural}",
		              'edit_item' => "Edit {$singular}",
		              'view_item' => "View {$singular}",
		              'update_item' => "Update {$singular}",
		              'add_new_item' => "Add New {$singular}",
		              'new_item_name' => "New {$singular} Name",
		              'parent_item' => "Parent {$singular}",
		              'parent_item_colon' => "Parent {$singular}:",
		              'search_items' => "Search {$plural}",
		              'popular_items' => "Popular {$plural}",
		              'separate_items_with_commas' => "Separate {$plural_l} with commas",
		              'add_or_remove_items' => "Add or remove {$plural_l}",
		              'choose_from_most_used' => "Choose from the most used {$plural_l}",
		              'not_found' => "No {$plural_l} found."
					  );
	}

	/**
	* Registers the custom post types and taxonomies
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
						'menu_position' => 30,
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
						'menu_position' => 31,
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
						'public' => true,
						'show_ui' => true,
						'show_in_menu' => true,
						'menu_position' => 32,
						'menu_icon' => 'dashicons-index-card',
						'supports' => array ('title', 'thumbnail', 'editor'),
						'has_archive' => false,
						'rewrite' => array('slug' => 'series', 'with_front' => false));
		register_post_type ('em_series', $args);
		//Authors
		$args = array ( 'label' => 'Authors',
						'labels' => self::generate_post_label ('Authors', 'Author'),
						'description' => 'Information about each author is stored here',
						'public' => true,
						'show_in_menu' => true,
						'menu_position' => 33,
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
						'menu_position' => 34,
						'menu_icon' => 'dashicons-media-text',
						'supports' => array ('title', 'thumbnail', 'editor'),
						'has_archive' => true,
						'query_var' => 'articles',
						'register_meta_box_cb' => array ('evangelical_magazine_article', 'article_meta_boxes'),
						'rewrite' => array('slug' => 'article', 'with_front' => false));
		register_post_type ('em_article', $args);
		//Reviews
		$args = array ( 'label' => 'Reviews',
						'labels' => self::generate_post_label ('Reviews', 'Review'),
						'description' => 'Information about each review is stored here',
						'public' => true,
						'show_in_menu' => true,
						'menu_position' => 35,
						'menu_icon' => 'dashicons-awards',
						'supports' => array ('title', 'thumbnail', 'editor'),
						'has_archive' => true,
						'query_var' => 'reviews',
						'register_meta_box_cb' => array ('evangelical_magazine_review', 'review_meta_boxes'),
						'rewrite' => array('slug' => 'review', 'with_front' => false));
		register_post_type ('em_review', $args);
		//Taxonomies
		$args = array (	'label' => 'Media type',
						'labels' => self::generate_taxonomy_label('Media Types', 'Media Type'),
						'public' => true,
						'show_in_menu' => true,
						'show_tagcloud' => false,
						'show_admin_column' => true,
						'hierarchical' => true,
						'rewrite' => array ('slug' => 'type', 'with_front' => false)
						);
		register_taxonomy ('em_review_media_type', 'em_review', $args);
	}

	/**
	* Sets up the actions and filters required to add custom columns to the Edit Articles page
	*
	* @return void
	*/
	public static function setup_custom_post_type_columns() {
		global $evangelical_magazine;
		add_filter ('manage_edit-em_article_columns', array ('evangelical_magazine_articles_and_reviews', 'filter_article_columns'));
		add_action ('manage_em_article_posts_custom_column', array ('evangelical_magazine_articles_and_reviews', 'output_columns'), 10, 2);
		add_filter ('manage_edit-em_article_sortable_columns', array ('evangelical_magazine_articles_and_reviews', 'make_columns_sortable'));
		add_filter ('manage_edit-em_review_columns', array ('evangelical_magazine_articles_and_reviews', 'filter_review_columns'));
		add_action ('manage_em_review_posts_custom_column', array ('evangelical_magazine_articles_and_reviews', 'output_columns'), 10, 2);
		add_filter ('manage_edit-em_review_sortable_columns', array ('evangelical_magazine_articles_and_reviews', 'make_columns_sortable'));
		add_action ('pre_get_posts', array ('evangelical_magazine_articles_and_reviews', 'sort_by_columns'));
		add_action ('admin_head', array (__CLASS__, 'add_styles_to_admin_head'));
		add_filter ('post_row_actions', array (__CLASS__, 'adds_recalc_stats_to_actions'), 10, 2);
		if (isset($_GET['recalc_stats']) && is_admin()) {
			/** @var evangelical_magazine_articles_and_reviews */
			$object = evangelical_magazine::get_object_from_id((int)$_GET['recalc_stats']);
			if ($object && $object->is_article_or_review()) {
				delete_transient($object->get_facebook_transient_name());
				if ($evangelical_magazine->use_google_analytics) {
					delete_transient($object->get_google_analytics_transient_name());
				}
				$evangelical_magazine->update_all_stats_if_required ($object->get_id());
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
		} elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'em_review') {
			$review = new evangelical_magazine_review ($post_id);
			$review->save_meta_data();
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
	* Adds an additional separator into the menu
	* @see https://wordpress.stackexchange.com/questions/2666/add-a-separator-to-the-admin-menu
	*
	* @return void
	*/
	public static function add_admin_menu_separator() {
		global $menu;
		$position = 29;
		$index = 0;
		foreach($menu as $offset => $section) {
			if (substr($section[2],0,9)=='separator')
				$index++;
			if ($offset>=$position) {
		    	$menu[$position] = array('','read',"separator{$index}",'','wp-menu-separator');
		    	break;
		    }
		}
		ksort($menu);
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
			if (substr($post->post_type, 0,3) == 'em_') {
				$class = 'evangelical_magazine_'.substr($post->post_type, 3);
				if (class_exists($class)) {
					return new $class ($post);
				} else {
					trigger_error ("Invalid class: {$class}", E_USER_ERROR);
				}
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
	* @return string - the amended title
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
		echo '<style type="text/css">.column-title {width: 30%} .column-views, .column-fb_shares {width: 75px} .column-fb_reactions, .column-fb_comments {width: 100px}</style></style>';
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
			if ($object->is_article_or_review()) {
				return $object->get_author_names();
			} else {
				return 'editor';
			}
		} else {
			return $display_name;
		}
	}

	/**
	* Adds the 'recalc_stats' row action to articles and reviews
	* Filters post_row_actions
	*
	* @param array $actions - the existing actions
	* @param WP_Post $post - the current post
	* @return array - the filtered actions
	*/
	public static function adds_recalc_stats_to_actions ($actions, $post) {
		global $current_screen;
		if ($post->post_type == 'em_article' || $post->post_type == 'em_review') {
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
	* Filters the content of the Mailchimp feed to show an excerpt of 175 words
	*
	* Filters the_content_feed, but only when the feed query is set to 'mailchimp'
	*
	* @param string $content
	* @return string
	*/
	public static function filter_feed_for_mailchimp ($content) {
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
		if ($count >= $max_words) {
			$output .= "&hellip; <a href=\"{$link}\">(continue reading)</a>";
		}
		$content = trim(force_balance_tags($output));
		return $content;
	}

	/**
	* Updates the Facebook stats for multiple objects in one request
	*
	* @param array $ids - an array of post ids or valid evangelical_magazine_* objects
	* @return void
	*/
	public function update_facebook_stats_if_required ($ids) {
		$requests = array();
		foreach ($ids as &$id) {
			if (gettype ($id) == 'object') {
				$id = $id->get_id();
			}
			$transient_name = "em_fb_valid_{$id}";
			$stats = get_transient($transient_name);
			if (!$stats) {
				$url = apply_filters ('evangelical_magazine_url_for_facebook', get_permalink($id));
				$requests[] = array ('method' => 'GET', 'relative_url' => '?id='.urlencode($url).'&fields=engagement');
				$lookup [$url] = $id;
			}
		}
		if ($requests) {
			$request_chunks = array_chunk($requests, 50);
			foreach ($request_chunks as $chunk) {
				$args['access_token'] = evangelical_magazine_fb_access_tokens::get_app_id().'|'.evangelical_magazine_fb_access_tokens::get_app_secret();
				$args['batch'] = json_encode($chunk);
				$stats = wp_remote_post('https://graph.facebook.com/v3.1/', array ('body' => $args));
				if (!is_a($stats, 'WP_Error')) {
					$response = json_decode($stats['body']);
					if (!isset($response->error)) {
						foreach ((array)$response as $r) {
							$stats = json_decode($r->body);
							/**
							* @var evangelical_magazine_template
							*/
							$object = $this->get_object_from_id($lookup [$stats->id]);
							if ($stats !== NULL && isset($stats->engagement)) {
								$object->update_facebook_stats ($stats->engagement);
							} else {
								$object->update_facebook_stats (array());
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
		$all_articles = evangelical_magazine_article::get_all_article_ids(array('post_status' => 'publish'));
		$evangelical_magazine->update_all_stats_if_required($all_articles);
	}

	/**
	* Adds quick access to the custom post types from the front-end toolbar
	*
	* @param WP_Admin_Bar $admin_bar
	* @return void
	*/
	public static function add_toolbar_items($admin_bar) {
		if (!is_admin()) {
			$args = array(
				'id'    => 'em',
				'parent' => 'site-name'
			);
			$admin_bar->add_group ($args);
			$args = array(
				'id'    => 'em_sections',
				'title' => 'Sections',
				'parent' => 'em',
				'href'  => admin_url('edit.php?post_type=em_section')
			);
			$admin_bar->add_node ($args);
			$args = array(
				'id'    => 'em_issues',
				'title' => 'Issues',
				'parent' => 'em',
				'href'  => admin_url('edit.php?post_type=em_issue')
			);
			$admin_bar->add_node ($args);
			$args = array(
				'id'    => 'em_series',
				'title' => 'Series',
				'parent' => 'em',
				'href'  => admin_url('edit.php?post_type=em_series')
			);
			$admin_bar->add_node ($args);
			$args = array(
				'id'    => 'em_authors',
				'title' => 'Authors',
				'parent' => 'em',
				'href'  => admin_url('edit.php?post_type=em_author')
			);
			$admin_bar->add_node ($args);
			$args = array(
				'id'    => 'em_articles',
				'title' => 'Articles',
				'parent' => 'em',
				'href'  => admin_url('edit.php?post_type=em_article')
			);
			$admin_bar->add_node ($args);
			$args = array(
				'id'    => 'em_reviews',
				'title' => 'Reviews',
				'parent' => 'em',
				'href'  => admin_url('edit.php?post_type=em_review')
			);
			$admin_bar->add_node ($args);
		}
	}

	/**
	* Adds the custom fields used for reviews to Relevanssi's index
	*
	* Filters relevanssi_index_custom_fields
	*
	* @param array $custom_fields - the existing fields to index
	* @return array
	*/
	public static function add_custom_fields_to_relevanssi ($custom_fields) {
		$fields_to_add = array (evangelical_magazine_review::REVIEW_CREATOR_META_NAME, evangelical_magazine_review::REVIEW_PUBLISHER_META_NAME);
		return array_merge ((array)$custom_fields, $fields_to_add);
	}

	/**
	* Adds the mailchimp RSS feed, and adds the action to filter the query for that feed
	*
	* Run on 'init'
	*
	* @return void
	*/
	public static function add_mailchimp_rss_feed() {
		add_feed ('mailchimp', array (__CLASS__, 'generate_mailchimp_feed'));
		add_action ('pre_get_posts', array (__CLASS__, 'modify_query_for_mailchimp_feed'));
	}

	/**
	* Generates the mailchimp feed
	*
	* @uses wp-includes/feed-rss2.php
	*
	* @return void
	*/
	public static function generate_mailchimp_feed() {
		include (WPINC.'/feed-rss2.php');
		die();
	}

	/**
	* Modifies the query for the mailchimp feed, and adds a filter for the content of the feed
	*
	* Runs on the pre_get_posts action.
	*
	* @param WP_Query $query - passed by reference
	* @return void
	*/
	public static function modify_query_for_mailchimp_feed($query) {
		if (is_feed() && isset ($query->query['feed']) && $query->query['feed'] == 'mailchimp') {
			$query->query_vars['post_type'] = array ('em_article', 'em_review');
			add_filter ('the_content_feed', array (__CLASS__, 'filter_feed_for_mailchimp'));
		}
	}
}

// Initialise
$evangelical_magazine = new evangelical_magazine();