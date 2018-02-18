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
	*/
	protected $authors, $issue, $page_num;

	/**
	* Returns the title of the article or review
	*
	* @param boolean $link - whether to add a HTML link to the article around the title text
	* @return string
	*/
	public function get_title($link = false) {
		if ($link && !$this->is_future()) {
			return $this->get_link_html($this->post_data->post_title);
		} else {
			return $this->post_data->post_title;
		}
	}

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
			if (count($output) > 1) {
				$last = ' and '.array_pop ($output);
			} else {
				$last = '';
			}
			return $prefix.implode (', ', $output).$last;
		}
	}


	/**
	* Returns the URL of the featured image
	*
	* @param string $image_size - the size of the image to be returned
	* @return string
	*/
	public function get_image_url($image_size = 'thumbnail') {
		$image_sizes = get_intermediate_image_sizes();
		if ($this->is_future() && substr($image_size, -3) != '_bw' && in_array("{$image_size}_bw", $image_sizes)) {
			$image_size = "{$image_size}_bw";
		}
		if (has_post_thumbnail($this->get_id())) {
			$src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
			if ($src) {
				return $src[0];
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
}