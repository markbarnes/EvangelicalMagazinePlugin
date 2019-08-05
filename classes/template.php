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
	const FB_REACTIONS_META_NAME = 'evangelical_magazine_fb_reactions';
	const FB_SHARES_META_NAME = 'evangelical_magazine_fb_shares';
	const FB_COMMENTS_META_NAME = 'evangelical_magazine_fb_comments';
	const GOOGLE_ANALYTICS_META_NAME = 'evangelical_magazine_google_analytics';
	const GOOGLE_ANALYTICS_INITIAL_META_NAME = 'evangelical_magazine_google_analytics_initial';
	const REVIEW_SORT_ORDER_META_NAME = 'evangelical_magazine_review_sort_order';
	const REVIEW_PRICE_META_NAME = 'evangelical_magazine_price';
	const REVIEW_PUBLISHER_META_NAME = 'evangelical_magazine_publisher';
	const REVIEW_CREATOR_META_NAME = 'evangelical_magazine_creator';
	const REVIEW_PURCHASE_URL_META_NAME = 'evangelical_magazine_purchase_url';
	const REVIEW_MEDIA_TYPE_TAXONOMY_NAME = 'em_review_media_type';

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
		if (is_object ($this->post_data)) {
			return $this->post_data->ID;
		}
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
	* Returns an array containing the URL, width, height, mimetype and filesize of the featured image
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
			$uploads = wp_upload_dir();
			if (isset($metadata['sizes'][$image_size]['file'])) {
				$filename = $metadata['sizes'][$image_size]['file'];
				$filesize = @filesize(dirname("{$uploads['basedir']}/{$metadata['file']}").'/'.$filename);
			} else {
				$filesize = @filesize("{$uploads['basedir']}/{$metadata['file']}");
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
	* @param string $image_class - the CSS class to be added to the image
	* @return string
	*/
	public function get_image_html($image_size = 'thumbnail', $link=false, $link_class = '', $alt_attribute = '', $image_class = '') {
		if (has_post_thumbnail($this->get_id())) {
			$image_id = get_post_thumbnail_id($this->get_id());
			$src = wp_get_attachment_image_src ($image_id, $image_size);
			if ($alt_attribute == '') {
				$alt_attribute = $this->get_name();
			}
			$alt_attribute = htmlspecialchars($alt_attribute, ENT_HTML5);
			if ($src) {
				$image_class = $image_class ? " class=\"{$image_class}\"" : '';
				$html = "<img src=\"{$src[0]}\" width=\"{$src[1]}\" height=\"{$src[2]}\" alt=\"{$alt_attribute}\"{$image_class}/>";
				return $link ? $this->get_link_html($html, array('class' => $link_class)) : $html;
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
	* @uses WP_Query
	* @return null|array
	*/
	protected static function _get_objects_from_query ($args, $default_args) {
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
				$objects[] = evangelical_magazine::get_object_from_post($post);
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
	protected static function _get_articles_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_article';
		return self::_get_objects_from_query($args, $default_args);
	}

	/**
	* Helper function to help subclasses return a single article from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|evangelical_magazine_article
	*/
	protected static function _get_article_from_query ($args, $default_args = array()) {
		$args = wp_parse_args (array('post_type' => 'em_article', 'posts_per_page' => 1), $args);
		$default_args = wp_parse_args (array ('offset' => 0), $default_args);
		$article = self::_get_objects_from_query($args, $default_args);
		if ($article && is_array($article)) {
			return $article[0];
		} else {
			return $article;
		}
	}

	/**
	* Helper function to help subclasses return all the articles and reviews from a WP_Query
	*
	* Wrapper for _get_objects()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|array
	*/
	protected static function _get_articles_and_reviews_from_query ($args, $default_args = array()) {
		$args ['post_type'] = array ('em_article', 'em_review');
		return self::_get_objects_from_query($args, $default_args);
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
	protected static function _get_sections_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_section';
		return self::_get_objects_from_query($args, $default_args);
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
	protected static function _get_series_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_series';
		return self::_get_objects_from_query($args, $default_args);
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
	protected static function _get_issues_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_issue';
		return self::_get_objects_from_query($args, $default_args);
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
	protected static function _get_authors_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_author';
		return self::_get_objects_from_query($args, $default_args);
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
	* Helper function to help subclasses return all the section object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_section_ids_from_query ($args) {
		$args ['post_type'] = 'em_section';
		return self::_get_object_ids_from_query($args, array());
	}

	/**
	* Helper function to help subclasses return all the issue object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_issue_ids_from_query ($args) {
		$args ['post_type'] = 'em_issue';
		return self::_get_object_ids_from_query($args, array());
	}

	/**
	* Helper function to help subclasses return all the series object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_series_ids_from_query ($args) {
		$args ['post_type'] = 'em_series';
		return self::_get_object_ids_from_query($args, array());
	}

	/**
	* Helper function to help subclasses return all the author object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_author_ids_from_query ($args) {
		$args ['post_type'] = 'em_author';
		return self::_get_object_ids_from_query($args, array());
	}

	/**
	* Helper function to help subclasses return all the article object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_article_ids_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_article';
		return self::_get_object_ids_from_query($args, $default_args);
	}

	/**
	* Helper function to help subclasses return all the review object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_review_ids_from_query ($args, $default_args = array()) {
		$args ['post_type'] = 'em_review';
		return self::_get_object_ids_from_query($args, $default_args);
	}

	/**
	* Helper function to help subclasses return all the article and/or review object_ids from a WP_Query
	*
	* Wrapper for _get_object_ids_from_query()
	*
	* @param array $args - WP_Query arguments
	* @param array $default_args - WP_Query arguments
	* @return null|int[]
	*/
	protected static function _get_article_and_review_ids_from_query ($args, $default_args = array()) {
		$args ['post_type'] = array ('em_article', 'em_review');
		return self::_get_object_ids_from_query($args, $default_args);
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
	* Returns an array of objects, given an array of post_ids
	*
	* @param integer[] $ids
	* @return array()
	*/
	public static function get_objects_from_ids ($ids) {
		if ($ids) {
			$objects = array();
			foreach ($ids as $id) {
				$objects[] = evangelical_magazine::get_object_from_id($id);
			}
			return $objects;
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
	protected function _get_top_articles_and_reviews ($objects, $limit = -1) {
		//We can't do this in one query, because WordPress won't return null values when you sort by meta_value
		if ($objects) {
			$index = array();
			if (self::use_google_analytics()) {
				$view_meta_key = self::GOOGLE_ANALYTICS_META_NAME;
			} else {
				$view_meta_key = self::VIEW_COUNT_META_NAME;
			}
			foreach ($objects as $key => $object) {
				$view_count = (int)get_post_meta($object->get_id(), $view_meta_key, true);
				$index[$key] = round ($view_count/(time()-strtotime($object->get_post_date()))*DAY_IN_SECONDS, 5);
			}
			arsort($index);
			if ($limit != -1) {
				$index = array_slice ($index, 0, $limit, true);
			}
			$top_articles_and_reviews = array();
			foreach ($index as $key => $view_count) {
				$top_articles_and_reviews[] = $objects[$key];
			}
			return $top_articles_and_reviews;
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
	* Returns true if the object is an issue
	*
	* @return bool
	*/
	public function is_issue() {
		return is_a($this, 'evangelical_magazine_issue');
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
	* Returns true if the object is an article or a review
	*
	* @return bool
	*/
	public function is_article_or_review() {
		return ($this->is_article() || $this->is_review());
	}

	/**
	* Returns true if the object is a review
	*
	* @return bool
	*/
	public function is_review() {
		return is_a($this, 'evangelical_magazine_review');
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
	* Returns an array of the Facebook stats metanames
	*
	* @return array
	*/
	private function get_facebook_metanames() {
		return array ('reactions' => self::FB_REACTIONS_META_NAME, 'comments' => self::FB_COMMENTS_META_NAME, 'shares' => self::FB_SHARES_META_NAME);
	}

	/**
	* Gets the Facebook stats for this object
	*
	* @param string $stat_type - one of 'reactions', 'comments', or 'shares'
	* @return int
	*/
	public function get_facebook_stats($stat_type = 'reactions') {
		global $evangelical_magazine;
		$evangelical_magazine->update_facebook_stats_if_required(array($this->get_id()));
		$meta_names = $this->get_facebook_metanames();
		return get_post_meta($this->get_id(), $meta_names[$stat_type], true);
	}

	/**
	* Updates the Facebook engagement metadata for this object
	*
	* @param int $engagement_count
	* @param string $stat_type - one of 'reactions', 'comments', or 'shares'
	* @return void
	*/
	public function update_facebook_stats($engagement_count) {
		if ($engagement_count) {
			$meta_names = $this->get_facebook_metanames();
			$elements = array ('comment_count' => 'comments', 'reaction_count' => 'reactions', 'share_count' => 'shares');
			foreach ($elements as $k => $v) {
				update_post_meta($this->get_id(), $meta_names[$v], $engagement_count->$k);
			}
			$secs_since_published = time() - strtotime($this->get_post_date());
			$secs_since_published = $secs_since_published < HOUR_IN_SECONDS ? HOUR_IN_SECONDS : $secs_since_published;
			set_transient ($this->get_facebook_transient_name(), true, $secs_since_published > WEEK_IN_SECONDS ? WEEK_IN_SECONDS : $secs_since_published);
		}
	}

	/**
	* Returns the name of the metadata where the Google Analytics engagement stat is cached
	*
	* @param string $start_date - the date stats should be calculated from, in the format 'yyyy-mm-dd'
	* @param string $end_date - the date stats should be calculated to, in the format 'yyyy-mm-dd' (or use 'today')
	* @return string
	*/
	public function get_google_analytics_metadata_name($start_date = '2016-01-01', $end_date = 'today') {
		if ($start_date == '2016-01-01' && $end_date == 'today') {
			return self::GOOGLE_ANALYTICS_META_NAME;
		} else {
			return self::GOOGLE_ANALYTICS_META_NAME."-{$start_date}-{$end_date}";
		}
	}

	/**
	* Returns the name of the transient which indicates whether or not the Google Analytics engagement stat is cached
	*
	* @param string $start_date - the date stats should be calculated from, in the format 'yyyy-mm-dd'
	* @param string $end_date - the date stats should be calculated to, in the format 'yyyy-mm-dd' (or use 'today')
	* @return string
	*/
	public function get_google_analytics_transient_name($start_date = '2016-01-01', $end_date = 'today') {
		return "em_ga_valid_{$this->get_id()}_{$start_date}_{$end_date}";
	}

	/**
	* Checks whether Google Analytics stats for this object have already been cached
	*
	* @param string $start_date - the date stats should be calculated from, in the format 'yyyy-mm-dd'
	* @param string $end_date - the date stats should be calculated to, in the format 'yyyy-mm-dd' (or use 'today')
	* @return bool
	*/
	public function has_valid_google_analytics_stats($start_date = '2016-01-01', $end_date = 'today') {
		$transient_name = $this->get_google_analytics_transient_name($start_date, $end_date);
		$stats = get_transient($transient_name);
		return (bool)$stats;
	}

	/**
	* Gets the Google Analytics stats for this object
	*
	* @param string $start_date - the date stats should be calculated from, in the format 'yyyy-mm-dd'
	* @param string $end_date - the date stats should be calculated to, in the format 'yyyy-mm-dd' (or use 'today')
	* @return int
	*/
	public function get_google_analytics_stats($start_date = '2016-01-01', $end_date = 'today') {
		global $evangelical_magazine;
		if (!$this->has_valid_google_analytics_stats($start_date, $end_date)) {
			$url = apply_filters ('evangelical_magazine_url_for_google_analytics', $this->get_link());
			$stats = $evangelical_magazine->analytics->get_page_views($url, $start_date, $end_date);
			$this->update_google_analytics_stats ($stats, $start_date, $end_date);
		}
		return get_post_meta($this->get_id(), $this->get_google_analytics_metadata_name($start_date, $end_date), true);
	}

	/**
	* Gets the initial Google Analytics stats for this object (i.e. stats for the first two weeks after publication)
	*
	* @param string $start_date - the date stats should be calculated from, in the format 'yyyy-mm-dd'
	* @param string $end_date - the date stats should be calculated to, in the format 'yyyy-mm-dd' (or use 'today')
	* @return int
	*/
	public function get_initial_google_analytics_stats() {
		global $evangelical_magazine;
		$post_date = strtotime($this->get_post_date());
		$start_date = date('Y-m-d', $post_date);
		$end_date = strtotime ('+2 weeks', $post_date);
		if ($end_date > time()) {
			$end_date = 'today';
		} else {
			$end_date = date ('Y-m-d', $end_date);
		}
		if (!$this->has_valid_google_analytics_stats($start_date, $end_date)) {
			$url = apply_filters ('evangelical_magazine_url_for_google_analytics', $this->get_link());
			$stats = $evangelical_magazine->analytics->get_page_views($url, $start_date, $end_date);
			$this->update_google_analytics_stats ($stats, $start_date, $end_date);
			update_post_meta($this->get_id(), self::GOOGLE_ANALYTICS_INITIAL_META_NAME, $stats);
		}
		return get_post_meta($this->get_id(), $this->get_google_analytics_metadata_name($start_date, $end_date), true);
	}

	/**
	* Updates the Google Analytics metadata for this object
	*
	* @param int $page_views
	* @param string $start_date - the date stats should be calculated from, in the format 'yyyy-mm-dd'
	* @param string $end_date - the date stats should be calculated to, in the format 'yyyy-mm-dd' (or use 'today')
	* @return void
	*/
	public function update_google_analytics_stats($page_views, $start_date = '2016-01-01', $end_date = 'today') {
		$meta_name = $this->get_google_analytics_metadata_name ($start_date, $end_date);
		update_post_meta($this->get_id(), $meta_name, $page_views);
		$secs_since_published = time() - strtotime($this->get_post_date());
		$secs_since_published = max($secs_since_published, DAY_IN_SECONDS/4);
		set_transient ($this->get_google_analytics_transient_name($start_date, $end_date), true, min ($secs_since_published, WEEK_IN_SECONDS));
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

	/**
	* Outputs the additional columns on the admin pages
	*
	* Filters manage_em_*_posts_custom_column
	*
	* @param string $column - the name of the column
	* @param int $post_id - the post_id for this row
	* @return void
	*/
	public static function output_columns ($column, $post_id) {
		global $evangelical_magazine, $post;
		/** @var evangelical_magazine_template */
		$object = evangelical_magazine::get_object_from_post($post);
		if ($column == 'featured_image') {
			$image_size = $object->is_issue() ? 'issue_small' : 'author_tiny';
			$image_details = $object->get_image_html($image_size, false, '', $object->get_name());
			if ($image_details) {
				echo $object->get_edit_link_html($image_details);
			}
		}
		if ($object->is_article_or_review()) {
			if ($object->is_published()) {
				if ($column == 'fb_reactions' || $column == 'fb_shares' || $column == 'fb_comments') {
					echo number_format($object->get_facebook_stats(substr($column,3)));
				}
				elseif ($column == 'views') {
					echo number_format($object->get_google_analytics_stats());
				}
				elseif ($column == 'initial_views') {
					echo number_format($object->get_initial_google_analytics_stats());
				}
			}
			if ($column == 'article_author' || $column == 'review_author' ) {
				$authors = $object->get_authors();
				if ($authors) {
					$author_names = array();
					foreach ($authors as $author) {
						$author_names[] = $author->get_name (false, false, true);
					}
					echo implode('<br/>', $author_names);
				}
			}
			elseif ($column == 'issue_details') {
				echo $object->get_issue_name(false, false, true).',&nbsp;pg&nbsp;'.$object->get_page_num();
			}
			elseif ($column == 'section') {
				echo $object->get_section_name(false, false, true);
			}
			elseif ($column == 'series') {
				echo $object->get_series_name(false, false, true);
			}
		}
	}

	/**
	* Returns the word count of post_content
	*
	* @return string
	*/
	public function get_word_count() {
		return str_word_count($this->get_filtered_content());
	}

	/**
	* Returns the estimated reading time of post_content, rounded to the nearest minute
	*
	* @return string
	*/
	public function get_reading_time() {
		$time = round($this->get_word_count()/283,0); // For 283wpm see https://psyarxiv.com/xynwg/
		return ($time == 0) ? 1 : $time;
	}
}