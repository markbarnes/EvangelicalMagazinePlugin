<?php

/**
* The main class for handling the series custom post type
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_series extends evangelical_magazine_not_articles_or_reviews {

	/**
	* Returns the series meta name
	*
	*/
	protected function get_meta_name() {
		return self::SERIES_META_NAME;
	}

	/**
	* Returns all the articles in the series
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article[]
	*/
	public function _get_articles ($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id(), 'compare' => '='));
		$default_args = array ('meta_query' => $meta_query, 'meta_key' => self::ORDER_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'ASC');
		return self::_get_articles_from_query($args, $default_args);
	}

	/**
	* Returns an array of all the series objects
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_series[]
	*/
	public static function get_all_series($args = array()) {
		$default_args = array ('orderby' => 'post_title', 'order' => 'ASC', 'posts_per_page' => -1);
		return self::_get_series_from_query($args, $default_args);
	}
}