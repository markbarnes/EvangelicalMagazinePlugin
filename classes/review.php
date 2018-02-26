<?php
/**
* The main class for handling the review custom post type
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_review extends evangelical_magazine_articles_and_reviews {

	/**
	* @var string $creator - the name of the author or artist who created the item being reviewed
	* @var float $price - the retail price of the item being reviewed
	* @var string $publisher - the publisher of the item being reviewed
	* @var string $purchase_url - a URL at which the item being reviewed can be purchased
	*/
	private $creator, $media_type, $price, $publisher, $purchase_url;

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
		$this->creator = get_post_meta($this->get_id(), self::REVIEW_CREATOR_META_NAME, true);
		$this->media_type = get_the_terms ($this->get_id(), SELF::REVIEW_MEDIA_TYPE_TAXONOMY_NAME);;
		$this->page_num = get_post_meta($this->get_id(), self::PAGE_NUM_META_NAME, true);
		$this->price = get_post_meta($this->get_id(), self::REVIEW_PRICE_META_NAME, true);
		$this->publisher = get_post_meta($this->get_id(), self::REVIEW_PUBLISHER_META_NAME, true);
		$this->purchase_url = get_post_meta($this->get_id(), self::REVIEW_PURCHASE_URL_META_NAME, true);
		$this->generate_sections_array();
		$this->generate_authors_array();
	}

	/**
	* Returns an array of all the review objects
	*
	* @param array $args
	* @return int[]
	*/
	public static function get_all_review_ids($args = array()) {
		$default_args = array ('orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => -1);
		return self::_get_review_ids_from_query($args, $default_args);
	}

	/**
	* Returns the title of the review
	*
	* @param boolean $link - whether to add a HTML link to the article around the title text
	* @return string
	*/
	public function get_title($link = false) {
		if ($media_type = $this->get_media_type_name()) {
			$title = "{$media_type} review — {$this->post_data->post_title}";
		} else {
			$title = "Review — {$this->post_data->post_title}";
		}
		if ($link && !$this->is_future()) {
			return $this->get_link_html($title);
		} else {
			return $title;
		}
	}

	/**
	* Returns the creator of the item being reviewed (e.g. author or artist)
	*
	* @param string $prefix - a string to be prepended to the creator
	* @param string $suffix - a string to be appended to the creator
	* @return string|void
	*/
	public function get_creator($prefix = '', $suffix = '') {
		if ($this->creator) {
			return $prefix.$this->creator.$suffix;
		}
	}

	/**
	* Returns the price of the item being reviewed
	*
	* @param string $prefix - a string to be prepended to the price
	* @param string $suffix - a string to be appended to the price
	* @return string|void
	*/
	public function get_price($prefix = '', $suffix = '') {
		if ($this->price) {
			return $prefix.$this->price.$suffix;
		}
	}

	/**
	* Returns the publisher of the item being reviewed
	*
	* @param string $prefix - a string to be prepended to the publisher
	* @param string $suffix - a string to be appended to the publisher
	* @return string|void
	*/
	public function get_publisher($prefix = '', $suffix = '') {
		if ($this->publisher) {
			return $prefix.$this->publisher.$suffix;
		}
	}

	/**
	* Returns the purchase URL of the item being reviewed
	*
	* @param string $prefix - a string to be prepended to the purchase URL
	* @param string $suffix - a string to be appended to the purchase URL
	* @return string|void
	*/
	public function get_purchase_url($prefix = '', $suffix = '') {
		if ($this->purchase_url) {
			return $prefix.$this->purchase_url.$suffix;
		}
	}

	/**
	* Returns the media type of the item being reviewed
	*
	* @param string $prefix - a string to be prepended to the media type
	* @param string $suffix - a string to be appended to the media type
	* @return string|void
	*/
	public function get_media_type_name($prefix = '', $suffix = '') {
		if ($this->media_type) {
			return $prefix.$this->media_type[0]->name.$suffix;
		}
	}

	/**
	* Returns the creator type of them item being reviewed (e.g. author, artist, creator, etc.)
	*
	* return string
	*/
	public function get_creator_type() {
		$mtn = $this->get_media_type_name();
		switch (strtolower($mtn)) {
			case 'book' : return 'Author';
			case 'music' : return 'Artist';
			default: return 'Creator';
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
		if (isset($_POST['em_issue_meta_box_nonce']) && wp_verify_nonce ($_POST['em_issue_meta_box_nonce'], 'em_issue_meta_box')) {
			$review_sort_order = '';
			// Issue
			if (isset($_POST['em_issue'])) {
				update_post_meta ($this->get_id(), self::ISSUE_META_NAME, $_POST['em_issue']);
				$this->issue = new evangelical_magazine_issue($_POST['em_issue']);
				$review_sort_order = $this->issue->get_date();
				if ($review_sort_order) {
					$review_sort_order = "{$review_sort_order['year']}-{$review_sort_order['month']}";
				}
			} else {
				delete_post_meta ($this->get_id(), self::ISSUE_META_NAME);
				$this->issue = null;
			}
			// Page number
			if (isset($_POST['em_page_num'])) {
				$this->page_num = (int)$_POST['em_page_num'];
				update_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME, $this->page_num);
				$review_sort_order .= $this->page_num ? '-'.str_pad($this->page_num, 2, '0', STR_PAD_LEFT) : '';
			} else {
				delete_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME);
				$this->page_num = null;
			}
			// Sort order
			if ($review_sort_order) {
				update_post_meta ($this->get_id(), self::REVIEW_SORT_ORDER_META_NAME, $review_sort_order);
			} else {
				delete_post_meta ($this->get_id(), self::REVIEW_SORT_ORDER_META_NAME);
			}
		}
		if (isset($_POST['em_resource_meta_box_nonce']) && wp_verify_nonce ($_POST['em_resource_meta_box_nonce'], 'em_resource_meta_box')) {
			//Creator
			if (isset($_POST['em_creator'])) {
				$this->creator = $_POST['em_creator'];
				update_post_meta ($this->get_id(), self::REVIEW_CREATOR_META_NAME, $this->creator);
			} else {
				delete_post_meta ($this->get_id(), self::REVIEW_CREATOR_META_NAME);
				$this->creator = null;
			}
			//Publisher
			if (isset($_POST['em_publisher'])) {
				$this->publisher = $_POST['em_publisher'];
				update_post_meta ($this->get_id(), self::REVIEW_PUBLISHER_META_NAME, $this->publisher);
			} else {
				delete_post_meta ($this->get_id(), self::REVIEW_PUBLISHER_META_NAME);
				$this->publisher = null;
			}
			//Price
			if (isset($_POST['em_price'])) {
				$this->price = (float)str_replace('£', '', $_POST['em_price']);
				update_post_meta ($this->get_id(), self::REVIEW_PRICE_META_NAME, $this->price);
			} else {
				delete_post_meta ($this->get_id(), self::REVIEW_PRICE_META_NAME);
				$this->price = null;
			}
			//Purchase URL
			if (isset($_POST['em_purchase_url'])) {
				$this->purchase_url = $_POST['em_purchase_url'];
				update_post_meta ($this->get_id(), self::REVIEW_PURCHASE_URL_META_NAME, $this->purchase_url);
			} else {
				delete_post_meta ($this->get_id(), self::REVIEW_PURCHASE_URL_META_NAME);
				$this->purchase_url = null;
			}
		}
		// Sections
		// There's no sections metabox for reviews. The 'review' section is automatically applied.
		delete_post_meta ($this->get_id(), self::SECTION_META_NAME);
		add_post_meta ($this->get_id(), self::SECTION_META_NAME, $this->get_reviews_section_id());
		$this->generate_sections_array();
	}

	/**
	* Adds metaboxes to articles custom post type
	*
	* @return void
	*/
	public static function review_meta_boxes() {
		add_meta_box ('em_resource', 'Details of the resource being reviewed', array(get_called_class(), 'do_resource_meta_box'), 'em_review', 'normal', 'core');
		add_meta_box ('em_issues', 'Issue', array(get_called_class(), 'do_issue_meta_box'), 'em_review', 'side', 'core');
		add_meta_box ('em_authors', 'Author(s)', array(get_called_class(), 'do_author_meta_box'), 'em_review', 'side', 'core');
	}

	/**
	* Outputs the resource meta box
	*
	* Called by the add_meta_box function
	*
	* @param WP_Post $article
	* @return void
	*/

	//$creator, $price, $publisher, $purchase_url

	public static function do_resource_meta_box($post) {
		wp_nonce_field ('em_resource_meta_box', 'em_resource_meta_box_nonce');
		if (!evangelical_magazine::is_creating_post()) {
			$review = new evangelical_magazine_review ($post);
			$existing_creator = $review->get_creator();
			$existing_price = $review->get_price();
			$existing_publisher = $review->get_publisher();
			$existing_purchase_url = $review->get_purchase_url();
		} else {
			$existing_creator = $existing_price = $existing_publisher = $existing_purchase_url = '';
		}
		echo '<table class="form-table"><tbody>';
		echo '<tr><th scope="row"><label for="em_creator">Creator</label><br/>(Author or artist)</th><td><input class="large-text" type="text" name="em_creator" id="em_creator" value="'.$existing_creator.'"></input></td></tr>';
		echo '<tr><th scope="row"><label for="em_publisher">Publisher</label></th><td><input class="large-text" type="text" name="em_publisher" id="em_publisher" value="'.$existing_publisher.'"></input></td></tr>';
		echo '<tr><th scope="row"><label for="em_price">Price (£)</label></th><td><input type="text" name="em_price" id="em_price" size="8" value="'.$existing_price.'"></input></td></tr>';
		echo '<tr><th scope="row"><label for="em_purchase_url">Purchase URL</label></th><td><input class="large-text" type="text" name="em_purchase_url" id="em_purchase_url" value="'.$existing_purchase_url.'"></input></td></tr>';
		echo '</tbody></table>';
	}

	/**
	* Modifies the title placeholder when creating new reviews
	*
	* Filters enter_title_here
	*
	* @param string $placeholder - the existing placeholder
	* @return string - the placeholder
	*/
	public static function filter_title_placeholder($placeholder) {
		$screen = get_current_screen();
		if ($screen->post_type == 'em_review') {
			return 'Enter the title of the resource being reviewed';
		} else {
			return $placeholder;
		}
	}

	/**
	* Returns the ID of the 'reviews' section
	* If the section doesn't exist, it is created
	*
	* @return int - the section post_id
	*/
	public static function get_reviews_section_id() {
		$args = array ('title' => 'Reviews', 'posts_per_page' => 1);
		$reviews_id = self::_get_section_ids_from_query ($args);
		if (!$reviews_id) {
			// Remove save_post action to avoid infinite loop
			remove_action ('save_post', array('evangelical_magazine', 'save_cpt_data'));
			$post_array = array (	'post_title' => 'Reviews',
									'post_status' => 'publish',
									'post_type' => 'em_section'
								);
			$reviews_id = wp_insert_post ($post_array);
			// Re-add save_post action
			add_action ('save_post', array('evangelical_magazine', 'save_cpt_data'));
			if (is_a($reviews_id, 'WP_Error')) {
				trigger_error ('Failed to create Reviews section', E_USER_ERROR);
			}
		} else {
			$reviews_id = $reviews_id[0];
		}
		return $reviews_id;
	}
	/**
	*
	* Adds the review media type to the page title
	*
	* Used in various filters
	*
	* @param string $title - the current title
	* @return string - the title witht the media type added
	*/
	public static function add_review_type_to_title ($title, $post_id = null) {
		if ($post_id === null) {
			global $post;
			$post_id = $post;
		}
		$review = evangelical_magazine::get_object_from_post($post_id);
		if ($review && $review->is_review())
			return $review->get_title();
		else {
			return $title;
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
}