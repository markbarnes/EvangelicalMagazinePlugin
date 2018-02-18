<?php
/**
* The main class for handling the article custom post type
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_article extends evangelical_magazine_articles_and_reviews {

	/**
	* @var int $order_in_series - the order of this article within a series
	* @var evangelical_magazine_section[] $sections - an array of sections this article is in
	* @var evangelical_magazine_series $series - the series this article is in
	*/
	private $order_in_series, $sections, $series;

	/**
	* Instantiate the class by passing the WP_Post object or a post_id
	*
	* @param integer|WP_Post $post - the post_id of WP_Post object
	* @return void
	*/
	public function __construct ($post) {
		if (!is_a ($post, 'WP_Post')) {
			$post = get_post ((int)$post);
		}
		$this->post_data = $post;
		$issue_id = get_post_meta($this->get_id(), self::ISSUE_META_NAME, true);
		if ($issue_id) {
			$this->issue = new evangelical_magazine_issue($issue_id);
		} else {
			$this->issue = null;
		}
		$this->page_num = get_post_meta($this->get_id(), self::PAGE_NUM_META_NAME, true);
		$series_id = get_post_meta($this->get_id(), self::SERIES_META_NAME, true);
		if ($series_id) {
			$this->series = new evangelical_magazine_series($series_id);
		} else {
			$this->series = null;
		}
		$this->order_in_series = get_post_meta($this->get_id(), self::ORDER_META_NAME, true);
		$this->generate_sections_array();
		$this->generate_authors_array();
	}

	/**
	* Returns an array of all the article objects
	*
	* @param array $args
	* @return evangelical_magazine_article[]
	*/
	public static function get_all_articles($args = array()) {
		$default_args = array ('post_type' => 'em_article', 'orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => -1);
		return self::_get_articles_from_query($args, $default_args);
	}

	/**
	* Returns true if the article is part of a series
	*
	* @return bool
	*/
	public function has_series() {
		return is_a($this->series, 'evangelical_magazine_series');
	}

	/**
	* Returns the series object
	*
	* @return evangelical_magazine_series
	*
	*/
	public function get_series() {
		return $this->series;
	}

	/**
	* Returns the post ID of the series
	*
	* @return integer
	*/
	public function get_series_id() {
		if ($this->has_series()) {
			return $this->series->get_id();
		}
	}

	/**
	* Returns the name of the series
	*
	* @param bool $link - whether to add a HTML link to the series around the series name
	* @param bool $schema - whether to add schema.org microdata
	* @param bool $edit_link - whether to add a HTML link to edit the series around the series text
	* @return string
	*/
	public function get_series_name($link = false, $schema = false, $edit_link = false) {
		if ($this->has_series()) {
			return $this->series->get_name($link, $schema, $edit_link);
		}
	}

	/**
	* Returns the article's position in the series
	*
	* @return int
	*/
	public function get_series_order() {
		return $this->order_in_series;
	}

	/**
	* Returns true if the article is in a section
	*
	* @return bool
	*/
	public function has_sections() {
		return (bool)$this->sections;
	}

	/**
	* Returns an array of sections objects for this article
	*
	* @return evangelical_magazine_section[]
	*/
	public function get_sections() {
		return $this->sections;
	}

	/**
	* Returns an array of section post IDs for this article
	*
	* @return integer[]
	*/
	public function get_section_ids() {
		$section = get_post_meta ($this->get_id(), self::SECTION_META_NAME);
		return (array)$section;
	}

	/**
	* Returns the name the first section
	*
	* @return string
	*/
	public function get_section_name($link = false, $schema = false, $edit_link = false) {
		if ($this->has_sections()) {
			$sections = $this->get_sections();
			return $sections[0]->get_name($link, $schema, $edit_link);
		}
	}

	/**
	* Populates $this->sections
	*
	* @return void
	*/
	private function generate_sections_array() {
		$section_ids = $this->get_section_ids();
		if ($section_ids) {
			foreach ($section_ids as $section_id) {
				$this->sections[] = new evangelical_magazine_section($section_id);
			}
		}
	}

	/**
	* Saves the metadata when the post is edited
	*
	* Called during the 'save_post' action
	*
	* @return void
	*/
	public function save_meta_data() {
		if (defined ('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;
		if (!current_user_can('edit_posts'))
			return;
		// Authors
		if (isset ($_POST['em_author_meta_box_nonce']) && wp_verify_nonce ($_POST['em_author_meta_box_nonce'], 'em_author_meta_box')) {
			delete_post_meta ($this->get_id(), self::AUTHOR_META_NAME);
			if (isset($_POST['em_authors'])) {
				if (is_array($_POST['em_authors'])) {
					foreach ($_POST['em_authors'] as $author) {
						add_post_meta ($this->get_id(), self::AUTHOR_META_NAME, $author);
					}
				}
			}
			$this->generate_authors_array();
		}
		// Sections
		if (isset ($_POST['em_section_meta_box_nonce']) && wp_verify_nonce ($_POST['em_section_meta_box_nonce'], 'em_section_meta_box')) {
			delete_post_meta ($this->get_id(), self::SECTION_META_NAME);
			if (isset($_POST['em_sections'])) {
				if (is_array($_POST['em_sections'])) {
					foreach ($_POST['em_sections'] as $section) {
						add_post_meta ($this->get_id(), self::SECTION_META_NAME, $section);
					}
				}
			}
			$this->generate_sections_array();
		}
		if (isset($_POST['em_issue_meta_box_nonce']) && wp_verify_nonce ($_POST['em_issue_meta_box_nonce'], 'em_issue_meta_box')) {
			$article_sort_order = '';
			// Issue
			if (isset($_POST['em_issue'])) {
				update_post_meta ($this->get_id(), self::ISSUE_META_NAME, $_POST['em_issue']);
				$this->issue = new evangelical_magazine_issue($_POST['em_issue']);
				$article_sort_order = $this->issue->get_date();
				if ($article_sort_order) {
					$article_sort_order = "{$article_sort_order['year']}-{$article_sort_order['month']}";
				}
			} else {
				delete_post_meta ($this->get_id(), self::ISSUE_META_NAME);
				$this->issue = null;
			}
			// Page number
			if (isset($_POST['em_page_num'])) {
				$this->page_num = (int)$_POST['em_page_num'];
				update_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME, $this->page_num);
				$article_sort_order .= $this->page_num ? '-'.str_pad($this->page_num, 2, '0', STR_PAD_LEFT) : '';
			} else {
				delete_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME);
				$this->page_num = null;
			}
			// Sort order
			if ($article_sort_order) {
				update_post_meta ($this->get_id(), self::ARTICLE_SORT_ORDER_META_NAME, $article_sort_order);
			} else {
				delete_post_meta ($this->get_id(), self::ARTICLE_SORT_ORDER_META_NAME);
			}
		}
		// Series
		if (isset($_POST['em_series_meta_box_nonce']) && wp_verify_nonce ($_POST['em_series_meta_box_nonce'], 'em_series_meta_box')) {
			if (isset($_POST['em_series'])) {
				update_post_meta ($this->get_id(), self::SERIES_META_NAME, $_POST['em_series']);
				$this->series = new evangelical_magazine_series($_POST['em_series']);
			} else {
				delete_post_meta ($this->get_id(), self::SERIES_META_NAME);
				$this->series = null;
			}
			// Series order
			if (isset($_POST['em_order'])) {
				$this->order_in_series = (int)$_POST['em_order'];
				update_post_meta ($this->get_id(), self::ORDER_META_NAME, $this->order_in_series);
			} else {
				delete_post_meta ($this->get_id(), self::ORDER_META_NAME);
				$this->series = null;
			}
		}
	}

	/**
	* Returns all articles by the same author(s) as this article
	*
	* @param int $limit - the maximum number of articles to return
	* @param bool $exclude_this_article - true if the present article should be excluded
	* @return evangelical_magazine_article[]
	*/
	public function get_articles_by_same_authors($limit = 5, $exclude_this_article = true) {
		$author_ids = $this->get_author_ids();
		if ($author_ids) {
			$meta_query = array(array('key' => self::AUTHOR_META_NAME, 'value' => $author_ids, 'compare' => 'IN'));
			$args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
			if ($exclude_this_article) {
				$args ['post__not_in'] = array($this->get_id());
			}
			return self::_get_articles_from_query($args);
		}
	}

	/**
	* Returns all articles in the same series as this article
	*
	* @param int $limit - the maximum number of articles to return
	* @param bool $exclude_this_article - true if the present article should be excluded
	* @return bool|evangelical_magazine_article[] - returns false if the article is not in a series
	*/
	public function get_articles_in_same_series($limit = 99, $exclude_this_article = false) {
		$series = $this->get_series();
		if ($series) {
			$exclude_ids = $exclude_this_article ? (array)$this->get_id() : array();
			return $series->get_articles($limit, $exclude_ids);
		} else {
			return false;
		}
	}

	/**
	* Gets the number times this article has been viewed
	*
	* @return integer
	*/
	public function get_view_count() {
		if (self::use_google_analytics()) {
			return get_post_meta($this->get_id(), self::GOOGLE_ANALYTICS_META_NAME, true);
		} else {
			return get_post_meta($this->get_id(), self::VIEW_COUNT_META_NAME, true);
		}
	}

	/**
	* Increases the view count by one
	*
	* @return void
	*/
	public function record_view_count()  {
		$view_count = $this->get_view_count();
		update_post_meta ($this->get_id(), self::VIEW_COUNT_META_NAME, $view_count+1);
	}

	/**
	* Returns the HTML which produces the small article box
	*
	* @param bool $add_links - whether links should be added to the article name and image
	* @param string $sub_title - any subtitle to be added
	* @param string $class - any CSS classes to be added
	* @return string
	*/
	public function get_small_box_html($add_links = true, $sub_title = '', $class = '') {
		if (has_post_thumbnail($this->get_id())) {
			$src = $this->get_image_url($image_size = 'article_large');
			$style = "style=\"background-image: url('{$src}'); background-position: center center; background-size: cover\"";
		} else {
			$style = '';
		}
		$class = trim("small-article-box {$class}");
		$class .= $this->is_future() ? ' future' : '';
		$sub_title = $sub_title ? "<span class=\"sub-title\">{$sub_title}</span>" : '';
		if ($add_links && !$this->is_future()) {
			return "<aside class=\"{$class}\">{$sub_title}".$this->get_link_html("<div class=\"article-image\" {$style}></div>")."<div class=\"article-title\">{$this->get_title(true)}</div></aside>";
		} else {
			return "<aside class=\"{$class}\"><div class=\"article-image\" {$style}>{$sub_title}</div><div class=\"article-title\">{$this->get_title()}</div></aside>";
		}
	}

	/**
	* Adds metaboxes to articles custom post type
	*
	* @return void
	*/
	public static function article_meta_boxes() {
		add_meta_box ('em_issues', 'Issue', array(get_called_class(), 'do_issue_meta_box'), 'em_article', 'side', 'core');
		add_meta_box ('em_sections', 'Section(s)', array(get_called_class(), 'do_section_meta_box'), 'em_article', 'side', 'core');
		add_meta_box ('em_authors', 'Author(s)', array(get_called_class(), 'do_author_meta_box'), 'em_article', 'side', 'core');
		add_meta_box ('em_series', 'Series', array(get_called_class(), 'do_series_meta_box'), 'em_article', 'side', 'core');
	}

	/**
	* Outputs the section meta box
	*
	* Called by the add_meta_box function
	*
	* @param WP_Post $article
	* @return void
	*/
	public static function do_section_meta_box($article) {
		$sections = evangelical_magazine_section::get_all_sections();
		echo self::_get_checkbox_meta_box ($sections, 'section');
	}

	/**
	* Outputs the series meta box
	*
	* Called by the add_meta_box function
	*
	* @param WP_Post $article
	* @return void
	*/
	public static function do_series_meta_box($post) {
		$series = evangelical_magazine_series::get_all_series();
		if ($series) {
			wp_nonce_field ('em_series_meta_box', 'em_series_meta_box_nonce');
			if (!evangelical_magazine::is_creating_post()) {
				$article = new evangelical_magazine_article ($post);
				$existing_series = $article->get_series_id();
				$existing_order = $article->get_series_order();
				$existing_order = ($existing_order == 0) ? '' : $existing_order;
			} else {
				$existing_series = $existing_order = '';
			}
			echo 'Series: <select name="em_series">';
			$selected = ($existing_series == '') ? ' selected="selected"' : '';
			echo "<option value=\"\"{$selected}></option>";
			foreach ($series as $s) {
				$selected = ($existing_series == $s->get_id()) ? ' selected="selected"' : '';
				echo "<option value=\"{$s->get_id()}\"{$selected}> {$s->get_name()}</option>";
			}
			echo '</select><br/>';
			echo "<label>Order: <input type=\"text\" name=\"em_order\" size=\"2\" maxlength=\"2\" autocomplete=\"off\" value=\"{$existing_order}\"/></label>";
			echo '<h4><a href="#em_series_add" class="hide-if-no-js">+ Add new series</a></h4>';
		}
	}

	/**
	* Returns the most recently published articles
	*
	* @param int $number_of_articles - the maximum number of article to be returned
	* @return null|evangelical_magazine_article
	*/
	public static function get_recent_articles($number_of_articles = 10) {
		$default_args = array ('post_type' => 'em_article', 'orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => $number_of_articles, 'paged' => 1, 'post_status' => 'publish');
		return self::_get_articles_from_query($default_args);
	}

	/**
	* Returns the next article to be published
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article
	*/
	public static function get_next_future_article($args = array()) {
		$default_args = array ('post_type' => 'em_article', 'orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => 1, 'post_status' => 'future');
		$articles = self::_get_articles_from_query($args, $default_args);
		if (is_array($articles)) {
			return $articles[0];
		}
	}

	/**
	* Returns the most popular articles
	*
	* @param integer $limit - the maximum number of articles to return
	* @param array $exclude_article_ids - an array of article ids to be exlcuded
	* @return null|evangelical_magazine_article[]
	*/
	public static function get_top_articles ($limit = -1, $exclude_article_ids = array()) {
		global $wpdb;
		// For performance reasons, we can't do this through WP_Query
		if (self::use_google_analytics()) {
			$meta_key = self::GOOGLE_ANALYTICS_META_NAME;
		} else {
			$meta_key = self::VIEW_COUNT_META_NAME;
		}
		$limit = ($limit == -1) ? '' : " LIMIT 0, {$limit}";
		$not_in = ($exclude_article_ids) ? " AND post_id NOT IN(".implode(', ', $exclude_article_ids).')' : '';
		$article_ids = $wpdb->get_col ("SELECT post_id, (meta_value/DATEDIFF(NOW(), post_date)) AS views_per_day FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE ID=post_id AND meta_key='{$meta_key}' AND post_status='publish' AND post_type = 'em_article'{$not_in} GROUP BY post_id ORDER BY views_per_day DESC{$limit}", 0);
		if ($article_ids) {
			$articles = array();
			foreach ($article_ids as $id) {
				$articles[] = new evangelical_magazine_article($id);
			}
			return $articles;
		}
	}

	/**
	* Adds columns to the Articles admin page
	*
	* Filters manage_edit-em_article_columns
	*
	* @param array $columns
	* @return array
	*/
	public static function filter_columns ($columns) {
		global $evangelical_magazine;
		$columns ['article_author'] = 'Author';
		$columns ['issue_details'] = 'Issue';
		$columns ['section'] = 'Section';
		$columns ['series'] = 'Series';
		$columns ['fb_engagement'] = 'FB Engagement';
		if (self::use_google_analytics()) {
			$columns ['views'] = 'Views';
			$column_order = array ('cb', 'title', 'article_author', 'issue_details', 'section', 'series', 'views', 'fb_engagement', 'date');
		} else {
			$column_order = array ('cb', 'title', 'article_author', 'issue_details', 'section', 'series', 'fb_engagement', 'date');
		}
		return array_merge(array_flip($column_order), $columns);
	}

	/**
	* Outputs the additional columns on the Articles admin page
	*
	* Filters manage_em_article_posts_custom_column
	*
	* @param string $column - the name of the column
	* @param int $post_id - the post_id for this row
	* @return void
	*/
	public static function output_columns ($column, $post_id) {
		global $post;
		$article = new evangelical_magazine_article($post);
		if ($article->is_published() && $column == 'fb_engagement') {
			echo number_format($article->get_facebook_stats());
		}
		elseif ($article->is_published() && $column == 'views') {
			echo number_format($article->get_google_analytics_stats());
		}
		elseif ($column == 'article_author') {
			$authors = $article->get_authors();
			if ($authors) {
				$author_names = array();
				foreach ($authors as $author) {
					$author_names[] = $author->get_name (false, false, true);
				}
				echo implode('<br/>', $author_names);
			}
		}
		elseif ($column == 'issue_details') {
			echo $article->get_issue_name(false, false, true).',&nbsp;pg&nbsp;'.$article->get_page_num();
		}
		elseif ($column == 'section') {
			echo $article->get_section_name(false, false, true);
		}
		elseif ($column == 'series') {
			echo $article->get_series_name(false, false, true);
		}
	}

	/**
	* Sets the custom columns to be sortable
	*
	* Filters manage_edit-em_article_sortable_columns
	*
	* @param array $columns
	* @return array
	*/
	public static function make_columns_sortable ($columns) {
		global $evangelical_magazine;
		$columns ['fb_engagement'] = 'fb_engagement';
		$columns ['issue_details'] = 'issue_details';
		if (self::use_google_analytics()) {
			$columns ['views'] = 'views';
		}
		return $columns;
	}

	/**
	* Modifies the query to sort by columns, if requested
	*
	* Runs on the pre_get_posts action
	*
	* @param WP_Query $query
	* @return void
	*/
	public static function sort_by_columns ($query) {
		if  (is_admin()) {
			$screen = get_current_screen();
			if ($screen->id == 'edit-em_article') {
				$orderby = $query->get('orderby');
				if ($orderby && $orderby == 'fb_engagement') {
					$query->set ('meta_key', self::FB_ENGAGEMENT_META_NAME);
					$query->set ('orderby','meta_value_num');
				} elseif ($orderby && $orderby == 'views') {
					$query->set ('meta_key', self::GOOGLE_ANALYTICS_META_NAME);
					$query->set ('orderby','meta_value_num');
				} elseif ($orderby && $orderby == 'issue_details') {
					$query->set ('meta_key', self::ARTICLE_SORT_ORDER_META_NAME);
					$query->set ('orderby','meta_value');
				}
			}
		}
	}
}