<?php
/**
* A helper class used by the article and review classes
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
abstract class evangelical_magazine_articles_and_reviews extends evangelical_magazine_template {
	/**
	* @var evangelical_magazine_author[] $authors - an array of authors of this article
	* @var evangelical_magazine_issue $issue - the issue this article is in
	* @var int $page_num - the page number of this article
	* @var evangelical_magazine_section[] $sections - an array of sections this article is in
	*/
	protected $authors, $issue, $page_num, $sections;

	/**
	* Returns true if this article has an issue specified
	*
	* @return bool
	*/
	public function has_issue() {
		return is_a($this->issue, 'evangelical_magazine_issue');
	}

	/**
	* Returns the post id of the issue
	*
	* @return integer
	*/
	public function get_issue_id() {
		if ($this->has_issue()) {
			return $this->issue->get_id();
		}
	}

	/**
	* Returns the name of the issue
	*
	* @param bool $link - whether to add a HTML link to the issue around the issue name
	* @param bool $schema - whether to add schema.org microdata
	* @param bool $edit_link - whether to add a HTML link to edit the issue around the issue name
	* @return string
	*/
	public function get_issue_name($link = false, $schema = false, $edit_link = false) {
		if ($this->has_issue()) {
			return $this->issue->get_name($link, $schema, $edit_link);
		}
	}

	/**
	* Returns the permalink of the issue
	*
	* @return string
	*/
	public function get_issue_link() {
		if ($this->has_issue()) {
			return $this->issue->get_link();
		}
	}

	/**
	* Returns the article's page number
	*
	* @return integer
	*/
	public function get_page_num() {
		return $this->page_num;
	}

	/**
	* Returns an array of author objects for this article
	*
	* @return evangelical_magazine_author[]
	*/
	public function get_authors() {
		return $this->authors;
	}

	/**
	* Returns an array of author post IDs for this article
	*
	* @return integer[]
	*/
	public function get_author_ids() {
		$authors = get_post_meta ($this->get_id(), self::AUTHOR_META_NAME);
		return array_unique((array)$authors);
	}

	/**
	* Populates $this->authors
	*
	* @return void
	*/
	protected function generate_authors_array() {
		$author_ids = $this->get_author_ids();
		if ($author_ids) {
			foreach ($author_ids as $author_id) {
				$this->authors[] = new evangelical_magazine_author($author_id);
			}
		}
	}

	/**
	* Returns all articles/reviews by the same author(s) as this article/review
	*
	* @param int $limit - the maximum number of articles/reviews to return
	* @param bool $exclude_ids - an array of article/review ids to be excluded
	* @return evangelical_magazine_article[]|evangelical_magazine_review[]
	*/
	public function get_articles_and_reviews_by_same_authors($limit = 5, $exclude_ids = array()) {
		$author_ids = $this->get_author_ids();
		if ($author_ids) {
			$meta_query = array(array('key' => self::AUTHOR_META_NAME, 'value' => $author_ids, 'compare' => 'IN'));
			$args = array ('posts_per_page' => $limit, 'meta_query' => $meta_query, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
			if ($exclude_ids) {
				$args ['post__not_in'] = $exclude_ids;
			}
			return self::_get_articles_and_reviews_from_query($args);
		}
	}

	/**
	* Returns a list of author names
	*
	* @param bool $link - whether to add a HTML link to the author around the author's name
	* @param bool $schema - whether to add schema.org microdata
	* @param string $prefix - text to prepend to the output (ignored if there are no authors)
	* @return string
	*/
	public function get_author_names($link = false, $schema = false, $prefix = '') {
		if (is_array($this->authors)) {
			$output = array();
			foreach ($this->authors as $author) {
				$output[] = $author->get_name ($link, $schema);
			}
			$last = (count($output) > 1) ? ' and '.array_pop ($output) : '';
			return $prefix.implode (', ', $output).$last;
		}
	}

	/**
	* Returns the most recently published articles or reviews
	*
	* @param int $number_of_articles - the maximum number of article to be returned
	* @return null|evangelical_magazine_article[]|evangelical_magazine_review[]
	*/
	public static function get_recent_articles_and_reviews($number_of_articles = 10) {
		$default_args = array ('orderby' => 'date', 'order' => 'DESC', 'posts_per_page' => $number_of_articles, 'paged' => 1, 'post_status' => 'publish');
		return self::_get_articles_and_reviews_from_query($default_args);
	}

	/**
	* Returns the next article to be published
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article|evangelical_magazine_review
	*/
	public static function get_next_future_article_or_review($args = array()) {
		$default_args = array ('orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => 1, 'post_status' => 'future');
		$articles = self::_get_articles_and_reviews_from_query($args, $default_args);
		if (is_array($articles)) {
			return $articles[0];
		}
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
	protected function generate_sections_array() {
		$section_ids = $this->get_section_ids();
		if ($section_ids) {
			foreach ($section_ids as $section_id) {
				$this->sections[] = new evangelical_magazine_section($section_id);
			}
		}
	}

	/**
	* Returns the date of the issue
	*
	* @return array - an array with the keys 'year' and 'month'
	*/
	public function get_issue_date() {
		if ($this->has_issue()) {
			return $this->issue->get_date();
		}
	}

	/**
	* Returns the date of the issue as a Unix timestamp
	*
	* @return int
	*/
	public function get_issue_datetime() {
		if ($this->has_issue()) {
			$date = $this->issue->get_date();
			return strtotime("{$date['year']}-{$date['month']}-01");
		}
	}

	/**
	* Returns the date the post was/will be published online
	*
	* @param string $date_format
	* @return string
	*/
	public function get_publish_date ($date_format = 'j F Y') {
		return date($date_format, strtotime($this->post_data->post_date));
	}

	/**
	* Returns a friendly string with the date that the article will be coming
	*
	* @return string
	*/
	public function get_coming_date() {
		$publish_date = str_replace(' '.date('Y'), '', $this->get_publish_date());
		if ($publish_date == date('j F')) {
			return 'later today';
		} elseif ($publish_date == date('j F', strtotime('tomorrow'))) {
			return 'tomorrow';
		} else {
			return "on {$publish_date}";
		}

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
	* Helper function to return the HTML of a meta box where the user can choose multiple items of another post type
	*
	* @param array $objects - an array of evangelical_magazine_* objects
	* @param string $name - the unique name/id of this metabox
	* @return string
	*/
	public static function _get_checkbox_meta_box($objects, $name) {
		if ($objects) {
			wp_nonce_field ("em_{$name}_meta_box", "em_{$name}_meta_box_nonce");
			if (!evangelical_magazine::is_creating_post()) {
				$article_id = (int)$_GET['post'];
				$article = new evangelical_magazine_article ($article_id);
				$method_name = "get_{$name}_ids";
				$existing_object_ids = $article->$method_name();
			} else {
				$existing_object_ids = array();
			}
			$output = "<ul id=\"em_{$name}checklist\" style=\"max-height: 275px; overflow-y: auto\" data-wp-lists=\"list:em_{$name}\" class=\"categorychecklist form-no-clear\">";
			foreach ($objects as $object) {
				$checked = in_array($object->get_id(), $existing_object_ids) ? ' checked="checked"' : '';
				$output .= "<li><label class=\"selectit\"><input type=\"checkbox\" name=\"em_{$name}s[]\" value=\"{$object->get_id()}\"{$checked}> {$object->get_name()}</label></li>";
			}
			$output .= '</ul>';
			$output .= "<h4><a href=\"#em_{$name}_add\" class=\"hide-if-no-js\">+ Add new {$name}</a></h4>";
			return $output;
		}
	}

	/**
	* Outputs the author meta box
	*
	* Called by the add_meta_box function
	*
	* @param WP_Post $article
	* @return void
	*/
	public static function do_author_meta_box($article) {
		$authors = evangelical_magazine_author::get_all_authors();
		echo self::_get_checkbox_meta_box ($authors, 'author');
	}

	/**
	* Outputs the issue meta box
	*
	* Called by the add_meta_box function
	*
	* @param WP_Post $article
	* @return void
	*/
	public static function do_issue_meta_box($post) {
		$issues = evangelical_magazine_issue::get_all_issues();
		if ($issues) {
			wp_nonce_field ('em_issue_meta_box', 'em_issue_meta_box_nonce');
			if (!evangelical_magazine::is_creating_post()) {
				$article = new evangelical_magazine_article ($post);
				$existing_issue = $article->get_issue_id();
				$existing_page_num = $article->get_page_num();
			} else {
				$existing_issue = $existing_page_num = '';
			}
			echo 'Date: <select name="em_issue">';
			foreach ($issues as $issue) {
				$selected = ($existing_issue == $issue->get_id()) ? ' selected="selected"' : '';
				echo "<option value=\"{$issue->get_id()}\"{$selected}> {$issue->get_name()}</option>";
			}
			echo '</select><br/>';
			echo "<label>Page: <input type=\"text\" name=\"em_page_num\" size=\"2\" maxlength=\"2\" autocomplete=\"off\" value=\"{$existing_page_num}\"/></label>";
			echo '<h4><a id="em_issue_add" href="#" class="hide-if-no-js">+ Add new issue</a></h4>';
		}
	}

	/**
	* Gets the number times this article has been viewed
	*
	* @return integer
	*/
	public function get_view_count() {
		if (self::use_google_analytics()) {
			return (int)get_post_meta($this->get_id(), self::GOOGLE_ANALYTICS_META_NAME, true);
		} else {
			return (int)get_post_meta($this->get_id(), self::VIEW_COUNT_META_NAME, true);
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
	* Adds columns to the Articles admin pages
	*
	* Filters manage_edit-em_article_columns
	*
	* @param array $columns
	* @return array
	*/
	public static function filter_article_columns ($columns) {
		global $evangelical_magazine;
		$columns ['article_author'] = 'Author';
		$columns ['issue_details'] = 'Issue';
		$columns ['section'] = 'Section';
		$columns ['series'] = 'Series';
        $columns ['fb_reactions'] = 'Reactions';
        $columns ['fb_shares'] = 'Shares';
        $columns ['fb_comments'] = 'Comments';
        if (self::use_google_analytics()) {
			$columns ['views'] = 'Views';
			$column_order = array ('cb', 'title', 'article_author', 'issue_details', 'section', 'series', 'views', 'fb_reactions', 'fb_shares', 'fb_comments', 'date');
		} else {
			$column_order = array ('cb', 'title', 'article_author', 'issue_details', 'section', 'series', 'fb_reactions', 'fb_shares', 'fb_comments', 'date');
		}
		return array_merge(array_flip($column_order), $columns);
	}

	/**
	* Adds columns to the Articles admin pages
	*
	* Filters manage_edit-em_review_columns
	*
	* @param array $columns
	* @return array
	*/
	public static function filter_review_columns ($columns) {
		global $evangelical_magazine;
		$columns ['article_author'] = 'Author';
		$columns ['issue_details'] = 'Issue';
        $columns ['fb_reactions'] = 'Reactions';
        $columns ['fb_shares'] = 'Shares';
        $columns ['fb_comments'] = 'Comments';
        if (self::use_google_analytics()) {
			$columns ['views'] = 'Views';
			$column_order = array ('cb', 'title', 'taxonomy-em_review_media_type', 'article_author', 'issue_details', 'views', 'fb_reactions', 'fb_shares', 'fb_comments', 'date');
		} else {
			$column_order = array ('cb', 'title', 'taxonomy-em_review_media_type', 'article_author', 'issue_details', 'fb_reactions', 'fb_shares', 'fb_comments', 'date');
		}
		return array_merge(array_flip($column_order), $columns);
	}

	/**
	* Outputs the additional columns on the Articles and Reviews admin pages
	*
	* Filters manage_em_article_posts_custom_column and manage_em_review_posts_custom_column
	*
	* @param string $column - the name of the column
	* @param int $post_id - the post_id for this row
	* @return void
	*/
	public static function output_columns ($column, $post_id) {
		global $evangelical_magazine, $post;
		/** @var evangelical_magazine_articles_and_reviews */
		$object = evangelical_magazine::get_object_from_post($post);
		if ($object->is_article_or_review()) {
			if ($object->is_published()) {
				if ($column == 'fb_reactions' || $column == 'fb_shares' || $column == 'fb_comments') {
					echo number_format($object->get_facebook_stats(substr($column,3)));
				}
				elseif ($column == 'views') {
					echo number_format($object->get_google_analytics_stats());
				}
			}
			if ($column == 'article_author') {
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
	* Sets the custom columns to be sortable
	*
	* Filters manage_edit-em_article_sortable_columns and manage_edit-em_review_sortable_columns
	*
	* @param array $columns
	* @return array
	*/
	public static function make_columns_sortable ($columns) {
		global $evangelical_magazine;
		$columns ['fb_reactions'] = 'fb_reactions';
		$columns ['fb_shares'] = 'fb_shares';
		$columns ['fb_comments'] = 'fb_comments';
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
			if ($screen->id == 'edit-em_article' || $screen->id == 'edit-em_review') {
				$orderby = $query->get('orderby');
				if ($orderby && $orderby == 'fb_reactions') {
					$query->set ('meta_key', self::FB_REACTIONS_META_NAME);
					$query->set ('orderby','meta_value_num');
				} elseif ($orderby && $orderby == 'fb_shares') {
					$query->set ('meta_key', self::FB_SHARES_META_NAME);
					$query->set ('orderby','meta_value_num');
				} elseif ($orderby && $orderby == 'fb_comments') {
					$query->set ('meta_key', self::FB_COMMENTS_META_NAME);
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