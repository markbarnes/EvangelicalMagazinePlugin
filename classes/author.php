<?php
/**
* The main class for handling the author custom post type
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_author extends evangelical_magazine_not_articles_or_reviews {

	/**
	* Returns the author meta name
	*
	*/
	protected function get_meta_name() {
		return self::AUTHOR_META_NAME;
	}

	/**
	* Returns the name of the author
	*
	* @param bool $link - whether to add a HTML link to the author around the name
	* @param bool $schema - whether to add schema.org microdata
	* @param bool $edit_link - whether to add a HTML link to edit the author around the name (overrides other options)
	* @return string
	*/
	public function get_name($link = false, $schema = false, $edit_link = false) {
		$name = parent::get_name($link, $schema, $edit_link);
		if ($schema) {
			$name = $this->html_tag('span', $name, array('itemprop' => 'author', 'itemtype' => 'http://schema.org/Person', 'itemscope' => true));
		}
		return $name;
	}

	/**
	* Returns the description of the author
	*
	* @param boolean $link_name - whether to add a HTML link to the authors' name, if that is included in the description
	* @return string
	*/
	public function get_description($link_name = true) {
		if ($link_name) {
			return str_replace($this->get_name(), $this->get_name(true), $this->post_data->post_content);
		} else {
			return $this->post_data->post_content;
		}
	}

	/**
	* Returns the HTML of a thumbnail and name of the author
	*
	* @var string $image_size - a registered WordPress image size
	* @return string
	*/
	public function get_author_info_html($image_size = 'thumbnail') {
		$alt_text = htmlspecialchars($this->get_name(), ENT_HTML5);
		return "<div class=\"author-info\">".$this->get_link_html("<img class=\"author-image\" alt=\"{$alt_text}\" src=\"{$this->get_image_url($image_size)}\"/>")."<div class=\"author-description\">{$this->get_description()}</div></div>";
	}

	/**
	* Returns an array of all the author objects
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_author[]
	*/
	public static function get_all_authors($args = array()) {
		$default_args = array ('orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
		return self::_get_authors_from_query($args, $default_args);
	}

	/**
	* Returns an array of all the author ids
	*
	* @param array $args - WP_Query arguments
	* @return null|integer[]
	*/
	public static function get_all_author_ids($args = array()) {
		$default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
		return self::_get_object_ids_from_query($args, $default_args);
	}

	/**
	* Returns an array of authors, sorted by popularity
	*
	* @param int $limit - the maximum number of authors to return
	* @return null|evangelical_magazine_author[]
	*/
	public static function get_top_authors ($limit = -1) {
		global $wpdb;
		// For performance reasons, we can't do this through WP_Query
		if (self::use_google_analytics()) {
			$view_meta_key = self::GOOGLE_ANALYTICS_META_NAME;
		} else {
			$view_meta_key = self::VIEW_COUNT_META_NAME;
		}
		$author_meta_key = self::AUTHOR_META_NAME;
		$limit_sql = ($limit == -1) ? '' : " LIMIT 0, {$limit}";
		$today = date('Y-m-d');
		$author_ids = $wpdb->get_col ("SELECT meta_author.meta_value, AVG(meta_views.meta_value/DATEDIFF('{$today}', post_date)) AS average_views_per_day FROM {$wpdb->postmeta} AS meta_views, {$wpdb->postmeta} AS meta_author, {$wpdb->posts} WHERE ID=meta_views.post_id AND ID=meta_author.post_id AND meta_views.meta_key='{$view_meta_key}' AND meta_author.meta_key='{$author_meta_key}' AND post_status='publish' AND post_type = 'em_article' GROUP BY meta_author.meta_value ORDER BY average_views_per_day DESC{$limit_sql}");
		if ($limit == -1 || count($author_ids) < $limit) {
			//Now we need to add authors that have been missed, because they have no views
			$more_authors = evangelical_magazine_author::get_all_author_ids(array ('post__not_in' => (array)$author_ids));
			$author_ids = $more_authors ? array_merge($author_ids, $more_authors) : $author_ids;
			$author_ids = ($limit == -1) ? $author_ids : array_slice($author_ids, 0, $limit);
		}
		if ($author_ids) {
			$authors = array();
			foreach ($author_ids as $id) {
				$authors[] = new evangelical_magazine_author($id);
			}
			return $authors;
		}
	}

	/**
	* Returns an array of all the author objects beginning with a specific letter
	*
	* @param string $initial_letter - The initial letter(s)
	* @return null|evangelical_magazine_author[]
	*/
	public static function get_authors_by_initial_letter($initial_letter) {
		global $wpdb;
		// WP_Query doesn't support LIKE %, so we need our own query
		$sql = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'em_author' AND post_status = 'publish' AND post_title LIKE %s", "{$initial_letter}%");
		$author_ids = $wpdb->get_col ($sql);
		if ($author_ids) {
			return SELF::get_objects_from_ids($author_ids);
		}
	}

	/**
	* Helper function to sort authors alphabetically
	*
	* Designed to be called by one of PHP's array sort functions, such as uasort();
	*
	* @param evangelical_magazine_author $a
	* @param evangelical_magazine_author $b
	* @return int
	*/
	public static function _compare_authors_alphabetically ($a, $b) {
		return strcasecmp($a->get_name(), $b->get_name());
	}

	/**
	* Sorts an array of authors into alphabetical order (by first name)
	*
	* @param evangelical_magazine_author[] $authors
	* @return evangelical_magazine_author[]
	*/
	public static function sort_authors_alphabetically(&$authors) {
		uasort($authors, array(__CLASS__, '_compare_authors_alphabetically'));
	}

	/**
	* Adds columns to the Author admin pages
	*
	* Filters manage_edit-em_author_columns
	*
	* @param array $columns
	* @return array
	*/
	public static function filter_columns ($columns) {
		global $evangelical_magazine;
		$columns ['featured_image'] = 'Image';
        $column_order = array ('cb', 'featured_image', 'title', 'date');
		return array_merge(array_flip($column_order), $columns);
	}

	/**
	* Returns a count of the authors
	*
	* @return integer
	*/
	public static function get_count() {
		global $wpdb;
		// For performance reasons, we don't do this through WP_Query
		$author_count = $wpdb->get_var ("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type = 'em_author'");
		return $author_count;
	}

	/**
	* Returns an array of the initial letters of each author's name
	*
	* @return string[]
	*/
	public static function get_initial_letters_as_array() {
		global $wpdb;
		// For performance reasons, we don't do this through WP_Query
		$initial_letters = $wpdb->get_col ("SELECT substr(post_title,1,1) as initial_letter FROM {$wpdb->posts} WHERE post_type='em_author' GROUP BY initial_letter");
		return $initial_letters;
	}
}