<?php
/**
* The main class for handling the issue custom post type
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_issue extends evangelical_magazine_not_articles {

	const EARLIEST_YEAR = 2010;

	private $year, $month;

	/**
	* Instantiate the class by passing the WP_Post object or a post_id
	*
	* @param integer|WP_Post $post
	*/
	public function __construct($post) {
		if (!is_a ($post, 'WP_Post')) {
			$post = get_post ((int)$post);
		}
		$this->post_data = $post;
		$post_meta = get_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME, true);
		if (strlen($post_meta) == 7) {
			$this->year = substr($post_meta, 0, 4);
			$this->month = substr($post_meta, 5, 2);
		} else {
			$this->year = $this->month = null;
		}
	}

	/**
	* Returns an array contain the date of this issue
	*
	* @return array
	*/
	public function get_date() {
		return array ('year' => $this->year, 'month' => $this->month);
	}

	/**
	* Saves the metadata when a post is edited
	*
	* Called on the 'save_post_action
	*
	*/
	public function save_meta_data() {
		if (isset($_POST['em_issue_date_meta_box_nonce']) && wp_verify_nonce($_POST['em_issue_date_meta_box_nonce'], 'em_issue_date_meta_box')) {
			if (isset($_POST['em_issue_month']) && isset($_POST['em_issue_year'])) {
				update_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME, "{$_POST['em_issue_year']}-{$_POST['em_issue_month']}");
			} else {
				delete_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME);
			}
		}
	}

	/**
	* Helper function to return the articles from this issue
	*
	* @param array $args
	* @return evangelical_magazine_article[]
	*/
	public function _get_articles ($args = array()) {
		$meta_query = array(array('key' => self::ISSUE_META_NAME, 'value' => $this->get_id()));
		$default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => self::PAGE_NUM_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'posts_per_page' => -1);
		return self::_get_articles_from_query($args, $default_args);
	}

	/**
	* Returns an array of article IDs for all articles in the issue
	*
	* @param array $args
	* @return integer[]
	*/
	public function get_article_ids($args = array()) {
		$meta_query = array(array('key' => self::ISSUE_META_NAME, 'value' => $this->get_id()));
		$default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => self::PAGE_NUM_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'posts_per_page' => -1);
		return self::_get_object_ids_from_query($args, $default_args);
	}

	/**
	* Returns an array of author IDs for all authors in the issue
	*
	* @return integer[]
	*/
	public function get_author_ids() {
		$articles = $this->get_articles();
		if ($articles) {
			$all_authors = array();
			foreach ($articles as $article) {
				$all_authors = array_merge($all_authors, $article->get_author_ids());
			}
			return $all_authors;
		}
	}

	/**
	* Gets the post popular articles in the issue
	*
	* @param integer $limit - the maximum number of articles to return
	* @return evangelical_magazine_article[]
	*/
	public function get_top_articles ($limit = -1) {
		return $this->_get_top_articles_from_object ($limit, $this);
	}

	/**
	* Gets the articles with future post_dates in this issue
	*
	* @return evangelical_magazine_article[]
	*/
	public function get_future_articles($args = array()) {
		$default_args = array ('post_status' => array ('future'));
		$args = wp_parse_args($args, $default_args);
		return $this->get_articles($args);
	}

	/**
	* Returns an array of the possible values for the issue date
	*
	* @return array
	*/
	public static function get_possible_issue_dates() {
		return array ('01' => 'January/February', '03' => 'March/April', '05' => 'May/June', '07' => 'July/August', '09' => 'September/October', '11' => 'November/December');
	}

	/**
	* Returns an array of all the issue objects, with the most recent first.
	*
	* @param int $limit
	* @return evangelical_magazine_issue[]
	*/
	public static function get_all_issues($limit = -1) {
		$args = array ('post_type' => 'em_issue', 'meta_key' => self::ISSUE_DATE_META_NAME, 'orderby' => 'meta_value', 'order' => 'DESC', 'posts_per_page' => $limit);
		return self::_get_issues_from_query($args);
	}

	/**
	* Adds metaboxes to issues custom post type
	*
	*/
	public static function issue_meta_boxes() {
		add_meta_box ('em_issue_date', 'Date', array(get_called_class(), 'do_issue_date_meta_box'), 'em_issue', 'side', 'core');
	}

	/**
	* Outputs the issue meta box
	*
	* @param mixed $article
	*/
	public static function do_issue_date_meta_box($post) {
		wp_nonce_field ('em_issue_date_meta_box', 'em_issue_date_meta_box_nonce');
		if (!evangelical_magazine::is_creating_post()) {
			$issue = new evangelical_magazine_issue ($post);
			$existing_issue_date = $issue->get_date();
		} else {
			$this_month = date('n');
			$existing_issue_date = array('year' => date('Y'), 'month' => str_pad($this_month+(($this_month+1) % 2), 2, '0', STR_PAD_LEFT));
		}
		echo '<select name="em_issue_month">';
		$possible_issues = evangelical_magazine_issue::get_possible_issue_dates();
		foreach ($possible_issues as $index => $name) {
			$selected = ($existing_issue_date['month'] == $index) ? ' selected="selected"' : '';
			echo "<option value=\"{$index}\"{$selected}> {$name}</label></li>";
		}
		echo '</select>';
		echo '<select name="em_issue_year">';
		for ($year = date('Y')+1; $year >= evangelical_magazine_issue::EARLIEST_YEAR; $year--) {
			$selected = ($existing_issue_date['year'] == $year) ? ' selected="selected"' : '';
			echo "<option value=\"{$year}\"{$selected}> {$year}</label></li>";
		}
		echo '</select>';
	}

	/**
	* Returns an HTML <img> tag for the featured image
	*
	* @param string $image_size
	* @param string $link_class
	* @param boolean $link
	* @param string $alt_attribute
	* @return string
	*/
	public function get_image_html($image_size = 'thumbnail', $link=false, $link_class = '', $alt_attribute = '') {
		if ($alt_attribute == '') {
			$alt_attribute = $this->get_name();
		}
		return parent::get_image_html($image_size, $link, $link_class, $alt_attribute);
	}
}