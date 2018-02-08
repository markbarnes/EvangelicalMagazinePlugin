<?php

/**
* A helper class used by the other custom post type classes
*
* Contains common functions such as get_id()
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
abstract class evangelical_magazine_template {

	const AUTHOR_META_NAME = 'evangelical_magazine_authors';
	const ISSUE_META_NAME = 'evangelical_magazine_issue';
	const PAGE_NUM_META_NAME = 'evangelical_magazine_page_num';
	const ARTICLE_SORT_ORDER_META_NAME = 'evangelical_magazine_article_sort_order';
	const SERIES_META_NAME = 'evangelical_magazine_series';
	const ORDER_META_NAME = 'evangelical_magazine_order';
	const SECTION_META_NAME = 'evangelical_magazine_section';
	const VIEW_COUNT_META_NAME = 'evangelical_magazine_view_count';
	const ISSUE_DATE_META_NAME = 'evangelical_magazine_issue_date';
	const FB_ENGAGEMENT_META_NAME = 'evangelical_magazine_fb_engagement';
	const GOOGLE_ANALYTICS_META_NAME = 'evangelical_magazine_google_analytics';

	/**
	* @var WP_Post $post_data - All the custom posttype data is stored in $post_data as a WP_Post object
	*/
	protected $post_data;

	/**
	* Instantiate the class by passing the WP_Post object or a post_id
	*
	* @param integer|WP_Post $post - post_id or post object
	* @return void
	*/
	public function __construct($post) {
		if (!is_a ($post, 'WP_Post')) {
			$post = get_post ((int)$post);
		}
		$this->post_data = $post;
	}

	/**
	* Returns the post ID
	*
	* @return integer
	*/
	public function get_id() {
		return $this->post_data->ID;
	}

	/**
	* Returns a friendly class name of the current object
	*
	* @return string
	*/
	protected function get_friendly_class() {
		return str_replace('evangelical_magazine_', '', get_called_class());
	}

	/**
	* Returns the name of the object
	*
	* @param bool $link - whether to add a HTML link to the object around the name
	* @param bool $schema - whether to add schema.org microdata
	* @param bool $edit_link - whether to add a HTML link to edit the object around the name (overrides other options)
	* @return string
	*/
	public function get_name($link = false, $schema = false, $edit_link = false) {
		$name = $this->post_data->post_title;
		if ($edit_link) {
			return $this->get_edit_link_html ($name);
		}
		if ($schema) {
			$name = $this->html_tag('span', $name, array ('itemprop' => 'name'));
			$attributes = array ('itemprop' => 'url');
		} else {
			$attributes = array();
		}
		if ($link) {
			return $this->get_link_html($name, $attributes);
		} else {
			return $name;
		}
	}

	/**
	* Returns the permalink to the object
	*
	* @return string;
	*/
	public function get_link() {
		return get_permalink($this->post_data->ID);
	}

	/**
	* Returns the permalink to the object as HTML
	*
	* @param string $link_content - the text to be linked
	* @param array $attributes - an array of attributes to by added to the link element
	* @return string;
	*/
	public function get_link_html($link_content, $attributes = array()) {
		$attributes = wp_parse_args($attributes, array('class' => ''));
		$attributes['class'] = trim("{$this->get_friendly_class()}-link {$attributes['class']}");
		return "<a href=\"{$this->get_link()}\" {$this->attr_html($attributes)}>{$link_content}</a>";
	}

	/**
	* Returns the link to edit the object as HTML
	*
	* @param string $link_content - the text to be linked
	* @param array $attributes - an array of attributes to by added to the link element
	* @return string;
	*/
	public function get_edit_link_html($link_content, $attributes = array()) {
		$link = get_edit_post_link ($this->get_id());
		$attributes = wp_parse_args($attributes, array('class' => ''));
		$attributes['class'] = trim("{$this->get_friendly_class()}-link {$attributes['class']}");
		return "<a href=\"{$link}\" {$this->attr_html($attributes)}>{$link_content}</a>";
	}

	/**
	* Returns true if the post has a future publish date
	*
	* @return boolean
	*/
	public function is_future() {
		return ($this->post_data->post_status == 'future');
	}

	/**
	* Returns true if the post has been published
	*
	* @return boolean
	*/
	public function is_published() {
		return ($this->post_data->post_status == 'publish');
	}

	/**
	* Returns the page slug
	*
	* @return string
	*/
	public function get_slug() {
		return $this->post_data->post_name;
	}

	/**
	* Returns the post date
	*
	* @return string
	*/
	public function get_post_date() {
		return $this->post_data->post_date;
	}

	/**
	* Returns the post date as a Unix timestamp
	*
	* @return string
	*/
	public function get_post_datetime() {
		return strtotime($this->post_data->post_date);
	}

	/**
	* Returns the URL of the featured image
	*
	* @param string $image_size - the size of the image to be returned
	* @return string
	*/
	public function get_image_url($image_size = 'thumbnail') {
		if (has_post_thumbnail($this->get_id())) {
			$src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
			if ($src) {
				return $src[0];
			}
		}
	}

	/**
	* Returns an array containing the URL, width and height of the featured image
	*
	* @param string $image_size - the size of the image to be returned
	* @return array - with keys 'url', 'width', 'height', 'mimetype' and 'filesize'
	*/
	public function get_image_details($image_size = 'thumbnail') {
		if (has_post_thumbnail($this->get_id())) {
			$thumbnail_id = get_post_thumbnail_id($this->get_id());
			$src = wp_get_attachment_image_src ($thumbnail_id, $image_size);
			$mime_type = get_post_mime_type($thumbnail_id);
			$metadata = wp_get_attachment_metadata($thumbnail_id);
			if (isset($metadata['sizes'][$image_size]['file'])) {
				$filename = $metadata['sizes'][$image_size]['file'];
				$uploads = wp_upload_dir();
				//$filename = $uploads['basedir'] . "/{$filename}";
				$filesize = @filesize(dirname("{$uploads['basedir']}/{$metadata['file']}").'/'.$filename);
			} else {
				$filesize = false;
			}
			if ($src) {
				return array ('url' => $src[0], 'width' => $src[1], 'height' => $src[2], 'mimetype' => $mime_type, 'filesize' => $filesize);
			}
		}
	}

	/**
	* Returns an HTML <img> tag for the featured image
	*
	* @param string $image_size - the size of the image to be returned
	* @param boolean $link - whether or not the image should be hyperlinked
	* @param string $link_class - a CSS class to be added to the link
	* @param string $alt_attribute - the alt attribute for the image
	* @return string
	*/
	public function get_image_html($image_size = 'thumbnail', $link=false, $link_class = '', $alt_attribute = '') {
		if (has_post_thumbnail($this->get_id())) {
			$image_id = get_post_thumbnail_id($this->get_id());
			$src = wp_get_attachment_image_src ($image_id, $image_size);
			if ($alt_attribute == '') {
				$alt_attribute = $this->get_name();
			}
			$alt_attribute = htmlspecialchars($alt_attribute, ENT_HTML5);
			if ($src) {
				$html = "<img src=\"{$src[0]}\" width=\"{$src[1]}\" height=\"{$src[2]}\" alt=\"{$alt_attribute}\"/>";
				return $link ? $this->get_link_html($html, $link_class) : $html;
			}
		}
	}

	/**
	* Returns the post_content
	*
	* @return string
	*/
	public function get_content () {
		return $this->post_data->post_content;
	}

	/**
	* Returns the excerpt
	*
	* @return string
	*/
	public function get_excerpt () {
		return $this->post_data->post_excerpt;
	}

	/**
	* Returns the post_content with all HTML stripped
	*
	* @return string
	*/
	public function get_filtered_content() {
		return wp_strip_all_tags($this->get_content());
	}

	/**
	* Helper function to help subclasses return all the objects from a WP_Query
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @param string $class - the class to return (without the 'evangelical_magazine_')
	* @uses WP_Query
	* @return null|array
	*/
	protected static function _get_objects_from_query ($args, $default_args, $class) {
		$class = "evangelical_magazine_{$class}";
		if (isset($args['posts_per_page']) && $args['posts_per_page'] != -1) {
			$args['nopaging'] = false;
		}
		if (!isset($default_args['nopaging'])) {
			$default_args['nopaging'] = true;
		}
		$args = wp_parse_args($args, $default_args);
		$query = new WP_Query($args);
		if ($query->posts) {
			$objects = array();
			foreach ($query->posts as $post) {
				$objects[] = new $class($post);
			}
			return $objects;
		}
	}

	/**
	* Helper function to help subclasses return all the articles from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|evangelical_magazine_article[]
	*/
	protected static function _get_articles_from_query ($args, $default_args = '') {
		return self::_get_objects_from_query($args, $default_args, 'article');
	}

	/**
	* Helper function to help subclasses return all the sections from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|evangelical_magazine_section[]
	*/
	protected static function _get_sections_from_query ($args, $default_args = '') {
		return self::_get_objects_from_query($args, $default_args, 'section');
	}

	/**
	* Helper function to help subclasses return all the series from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|evangelical_magazine_series[]
	*/
	protected static function _get_series_from_query ($args, $default_args = '') {
		return self::_get_objects_from_query($args, $default_args, 'series');
	}

	/**
	* Helper function to help subclasses return all the issues from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|evangelical_magazine_issue[]
	*/
	protected static function _get_issues_from_query ($args, $default_args = '') {
		return self::_get_objects_from_query($args, $default_args, 'issue');
	}

	/**
	* Helper function to help subclasses return all the authors from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args
	* @param array $default_args
	* @uses evangelical_magazine_template::_get_objects()
	* @return null|evangelical_magazine_author[]
	*/
	protected static function _get_authors_from_query ($args, $default_args = '') {
		return self::_get_objects_from_query($args, $default_args, 'author');
	}

	/**
	* Helper function to help subclasses return all the object_ids from a WP_Query
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|int[]
	*/
	public static function _get_object_ids_from_query ($args, $default_args) {
		$args = wp_parse_args($args, $default_args);
		$query = new WP_Query($args);
		if ($query->posts) {
			$post_ids = array();
			foreach ($query->posts as $post) {
				$post_ids[] = $post->ID;
			}
			return $post_ids;
		}
	}

	/**
	* Returns all the object_ids from an array of objects
	*
	* @param array $objects - any evangelical_magazine_* objects
	* @return null|int[]
	*/
	public static function get_object_ids_from_array ($objects) {
		if ($objects) {
			$post_ids = array();
			foreach ($objects as $object) {
				$post_ids[] = $object->get_id();
			}
			return $post_ids;
		}
	}

	/**
	* Helper function that returns the arguments needed to return future posts in addition to what is normally returned
	*
	* @return array - with the key 'post_status'
	*/
	public static function _future_posts_args () {
		if (is_user_logged_in()) {
			return array ('post_status' => array ('publish', 'future', 'private'));
		} else {
			return array ('post_status' => array ('publish', 'future'));
		}
	}

	/**
	* Helper function that ranks an array of articles by popularity and returns the top $limit articles.
	*
	* Popularity is calculated according to the number of views per day
	*
	* @param evangelical_magazine_article[] $articles - an array of articles
	* @param int $limit - the maximum number of articles to return
	* @return evangelical_magazine_article[]
	*/
	protected function _get_top_articles ($articles, $limit = -1) {
		//We can't do this in one query, because WordPress won't return null values when you sort by meta_value
		if ($articles) {
			$index = array();
			if (self::use_google_analytics()) {
				$view_meta_key = self::GOOGLE_ANALYTICS_META_NAME;
			} else {
				$view_meta_key = self::VIEW_COUNT_META_NAME;
			}
			foreach ($articles as $key => $article) {
				$view_count = get_post_meta($article->get_id(), $view_meta_key, true);
				$index[$key] = round ($view_count/(time()-strtotime($article->get_post_date()))*84600 , 5);
			}
			arsort($index);
			if ($limit != -1) {
				$index = array_slice ($index, 0, $limit, true);
			}
			$top_articles = array();
			foreach ($index as $key => $view_count) {
				$top_articles[] = $articles[$key];
			}
			return $top_articles;
		}
	}

	/**
	* Returns true if the object is an author
	*
	* @return bool
	*/
	public function is_author() {
		return is_a($this, 'evangelical_magazine_author');
	}

	/**
	* Returns true if the object is an article
	*
	* @return bool
	*/
	public function is_article() {
		return is_a($this, 'evangelical_magazine_article');
	}

	/**
	* Returns the name of the transient which indicates whether or not the Facebook engagement stat is cached
	*
	* @return string
	*/
	public function get_facebook_transient_name() {
		return "em_fb_valid_{$this->get_id()}";
	}

	/**
	* Checks whether Facebook stats for this object have already been cached
	*
	* @return bool
	*/
	public function has_valid_facebook_stats() {
		$transient_name = $this->get_facebook_transient_name();
		$stats = get_transient($transient_name);
		return (bool)$stats;
	}

	/**
	* Gets the Facebook stats for this object
	*
	* @return int
	*/
	public function get_facebook_stats() {
		if (!$this->has_valid_facebook_stats()) {
			$url = apply_filters ('evangelical_magazine_url_for_facebook', $this->get_link());
			$json = wp_remote_request('https://graph.facebook.com/v2.8/?id='.urlencode($url).'&fields=og_object{engagement{count}},share&access_token='.evangelical_magazine_fb_access_tokens::get_app_id().'|'.evangelical_magazine_fb_access_tokens::get_app_secret());
			$stats = json_decode(wp_remote_retrieve_body($json), true);
			if ($stats !== NULL && isset($stats['share'])) {
				$this->update_facebook_stats ($stats['share']['share_count']);
			}
		}
		return get_post_meta($this->get_id(), self::FB_ENGAGEMENT_META_NAME, true);
	}

	/**
	* Updates the Facebook engagement metadata for this object
	*
	* @param int $engagement_count
	* @return void
	*/
	public function update_facebook_stats($engagement_count) {
		update_post_meta($this->get_id(), self::FB_ENGAGEMENT_META_NAME, $engagement_count);
		$secs_since_published = time() - strtotime($this->get_post_date());
		$secs_since_published = $secs_since_published < 3600 ? 3600 : $secs_since_published;
		set_transient ($this->get_facebook_transient_name(), true, $secs_since_published > 604800 ? 604800 : $secs_since_published);
	}

	/**
	* Returns the name of the transient which indicates whether or not the Google Analytics engagement stat is cached
	*
	* @return string
	*/
	public function get_google_analytics_transient_name() {
		return "em_ga_valid_{$this->get_id()}";
	}

	/**
	* Checks whether Google Analytics stats for this object have already been cached
	*
	* @return bool
	*/
	public function has_valid_google_analytics_stats() {
		$transient_name = $this->get_google_analytics_transient_name();
		$stats = get_transient($transient_name);
		return (bool)$stats;
	}

	/**
	* Gets the Google Analytics stats for this object
	*
	* @return int
	*/
	public function get_google_analytics_stats() {
		global $evangelical_magazine;
		if (!$this->has_valid_google_analytics_stats()) {
			$url = apply_filters ('evangelical_magazine_url_for_google_analytics', $this->get_link());
			$stats = $evangelical_magazine->analytics->get_page_views($url);
			$this->update_google_analytics_stats ($stats);
		}
		return get_post_meta($this->get_id(), self::GOOGLE_ANALYTICS_META_NAME, true);
	}

	/**
	* Updates the Google Analytics metadata for this object
	*
	* @param int $page_views
	* @return void
	*/
	public function update_google_analytics_stats($page_views) {
		update_post_meta($this->get_id(), self::GOOGLE_ANALYTICS_META_NAME, $page_views);
		$secs_since_published = time() - strtotime($this->get_post_date());
		$secs_since_published = $secs_since_published < 3600 ? 3600 : $secs_since_published;
		set_transient ($this->get_google_analytics_transient_name(), true, $secs_since_published > 604800 ? 604800 : $secs_since_published);
	}

	/**
	* Returns true if Google Analytics is configured and in use
	*
	* @return bool;
	*/
	public static function use_google_analytics() {
		global $evangelical_magazine;
		return $evangelical_magazine->use_google_analytics;
	}

	/**
	* Converts an array of attributes into a string, ready for HTML output
	*
	* e.g. array ('key1' => 'value1', 'key2' => 'value2')
	* will become 'key1="value1" key2="value2"'
	*
	* @param array $attributes
	* @return string
	*/
	protected function attr_html ($attributes) {
		$output = '';
		foreach ($attributes as $key => $value) {
			if ($value) {
				if ($value === true) {
					$output .= esc_html($key).' ';
				} else {
					$output .= sprintf('%s="%s" ', esc_html($key), esc_attr($value));
				}
			}
		}
		return trim($output);
	}

	/**
	* Returns a HTML tag
	*
	* @param string $tag - The name of the HTML tag
	* @param string $contents - The text contained in the tag
	* @param array $attributes - Various attributes and values [class, id, etc.] for the tag. The name of the attribute should be used as the key.
	*/
	protected function html_tag ($tag, $contents, $attributes = array()) {
		return "<{$tag} {$this->attr_html($attributes)}>{$contents}</{$tag}>";
	}
}