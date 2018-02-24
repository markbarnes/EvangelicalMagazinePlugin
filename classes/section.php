<?php
/**
* The main class for handling the section custom post type
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_section extends evangelical_magazine_not_articles_or_reviews {

	/**
	* Returns the section meta name
	*
	*/
	protected function get_meta_name() {
		return self::SECTION_META_NAME;
	}

	/**
	* Returns all the articles in the series
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article[]
	*/
	public function _get_articles ($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id(), 'compare' => '='));
		$default_args = array ('meta_query' => $meta_query, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
		return self::_get_articles_from_query($args, $default_args);
	}

	/**
	* Gets the most popular articles in the issue
	*
	* @param int $limit - the maximum number of articles to return
	* @return null|evangelical_magazine_article[]
	*/
	public function get_top_articles ($limit = -1, $exclude_article_ids = array()) {
		return $this->_get_top_articles_from_object ($limit, $this, $exclude_article_ids);
	}

	/**
	* Gets the most popular articles/reviews in the issue
	*
	* @param int $limit - the maximum number of articles to return
	* @return null|evangelical_magazine_article[]
	*/
	public function get_top_articles_and_reviews ($limit = -1, $exclude_ids = array()) {
		return $this->_get_top_articles_and_reviews_from_object ($limit, $this, $exclude_ids);
	}

	/**
	* Returns an array of all the section objects
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_section[]
	*/
	public static function get_all_sections($args = array()) {
		$default_args = array ('orderby' => 'post_title', 'order' => 'ASC', 'posts_per_page' => -1);
		return self::_get_sections_from_query($args, $default_args);
	}
}