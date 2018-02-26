<?php

/**
* A helper class used by most of the other custom post type classes
*
* Contains a few common functions such as get_articles()
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
abstract class evangelical_magazine_not_articles_or_reviews extends evangelical_magazine_template {

	/**
	* Should return the metaname associated with the specific class
	*
	*/
	abstract protected function get_meta_name();

	/**
	* Returns all articles linked to the current object
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article[]
	*/
	public function _get_articles ($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id()));
		$default_args = array ('posts_per_page' => -1, 'meta_query' => $meta_query);
		return self::_get_articles_from_query($args, $default_args);
	}

	/**
	* Returns all reviews linked to the current object
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_review[]
	*/
	final public function _get_reviews ($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id()));
		$default_args = array ('posts_per_page' => -1, 'meta_query' => $meta_query);
		return self::_get_reviews_from_query($args, $default_args);
	}

	/**
	* Returns all articles/reviews linked to the current object
	*
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article[]|evangelical_magazine_review[]
	*/
	public function _get_articles_and_reviews ($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id()));
		$default_args = array ('posts_per_page' => -1, 'meta_query' => $meta_query);
		return self::_get_articles_and_reviews_from_query($args, $default_args);
	}

	/**
	* Helper function to gets the post popular articles in an object
	*
	* @param int $limit - the maximum number of articles to return
	* @param mixed $object - any evangelical_magazine_* object (except articles or reviews)
	* @param int[] $exclude_article_ids - article ids to be excluded
	* @return null|evangelical_magazine_article[]
	*/
	protected function _get_top_articles_from_object ($limit = -1, $object, $exclude_article_ids = array()) {
		$articles = $object->get_articles(-1, $exclude_article_ids);
		return self::_get_top_articles_and_reviews ($articles, $limit);
	}

	/**
	* Helper function to gets the post popular articles/reviews in an object
	*
	* @param int $limit - the maximum number of articles/reviews to return
	* @param mixed $object - any evangelical_magazine_* object (except articles or reviews)
	* @param int[] $exclude_ids - article/review ids to be excluded
	* @return null|evangelical_magazine_article[]|evangelical_magazine_review[]
	*/
	protected function _get_top_articles_and_reviews_from_object ($limit = -1, $object, $exclude_ids = array()) {
		$objects = $object->get_articles_and_reviews(-1, $exclude_ids);
		return self::_get_top_articles_and_reviews ($objects, $limit);
	}

	/**
	* Returns an array of articles
	*
	* @param int $limit - the maximum number of articles to return
	* @param int[] $exclude_article_ids - article ids to be excluded
	* @param array $args - WP_Query arguments
	* @return null|evangelical_magazine_article[]
	*/
	public function get_articles ($limit = -1, $exclude_article_ids = array(), $args = array()) {
		$default_args = array ('posts_per_page' => $limit, 'post__not_in' => (array)$exclude_article_ids);
		$args = wp_parse_args($args, $default_args);
		return $this->_get_articles($args);
	}

	/**
	* Returns an array of article IDs for all articles from an object, ordered by date
	*
	* @param array $args - WP_Query arguments
	* @return null|integer[]
	*/
	final public function get_article_ids($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id()));
		$default_args = array ('meta_query' => $meta_query, 'posts_per_page' => -1, 'orderby' => 'date');
		return self::_get_article_ids_from_query($args, $default_args);
	}

	/**
	* Returns an array of reviews
	*
	* @param int $limit - the maximum number of reviews to return
	* @param int[] $exclude_review_ids - review ids to be excluded
	* @return null|evangelical_magazine_review[]
	*/
	public function get_reviews ($limit = -1, $exclude_review_ids = array()) {
		return $this->_get_reviews (array ('posts_per_page' => $limit, 'post__not_in' => (array)$exclude_review_ids));
	}

	/**
	* Returns an array of review IDs for all reviews from an object, ordered by date
	*
	* @param array $args - WP_Query arguments
	* @return null|integer[]
	*/
	final public function get_review_ids($args = array()) {
		$meta_query = array(array('key' => $this->get_meta_name(), 'value' => $this->get_id()));
		$default_args = array ('meta_query' => $meta_query, 'posts_per_page' => -1, 'orderby' => 'date');
		return self::_get_review_ids_from_query($args, $default_args);
	}

	/**
	* Returns an array of articles and reviews
	*
	* @param int $limit - the maximum number of articles/reviews to return
	* @param int[] $exclude_ids - article/review ids to be excluded
	* @return null|evangelical_magazine_article[]|evangelical_magazine_review[]
	*/
	public function get_articles_and_reviews ($limit = -1, $exclude_ids = array()) {
		return $this->_get_articles_and_reviews (array ('posts_per_page' => $limit, 'post__not_in' => (array)$exclude_ids));
	}

	/**
	* Returns an array of article IDs for all articles from an object, ordered by date
	*
	* @param array $args - WP_Query arguments
	* @return null|integer[]
	*/
	final public function get_article_and_review_ids($args = array()) {
		$meta_query = array(array('key' => self::get_meta_name(), 'value' => $this->get_id()));
		$default_args = array ('meta_query' => $meta_query, 'posts_per_page' => -1, 'orderby' => 'date');
		return self::_get_article_and_review_ids_from_query($args, $default_args);
	}

	/**
	* Returns the number of articles in an object
	*
	* @uses get_article_and_review_count();
	*
	* @param bool $include_text - whether or not the string ' article(s)' or 'review(s)' should be appended to the return value
	* @param book $include_likes - whether or not Facebook likes should be appended. Requires $include_text to be true.
	* @return integer
	*/
	public function get_article_count($include_text = false, $include_likes = false) {
		return $this->get_article_and_review_count($include_text, $include_likes, false);
	}
	/**
	* Returns the number of articles and/or reviews in an object
	*
	* @param bool $include_text - whether or not the string ' article(s)' or 'review(s)' should be appended to the return value
	* @param book $include_likes - whether or not Facebook likes should be appended. Requires $include_text to be true.
	* @param book $include_reviews - whether or not reviews should be inclued.
	* @return integer
	*/
	public function get_article_and_review_count($include_text = false, $include_likes = false, $include_reviews = true) {
		$article_ids = (array)$this->get_article_ids();
		$review_ids =  $include_reviews ? (array)$this->get_review_ids() : array();
		if ($article_ids || $review_ids) {
			$num_articles = count ($article_ids);
			$num_reviews = count ($review_ids);
			if ($include_text) {
				$return_values = array();
				if ($num_articles) {
					$return_values[] = $num_articles.($num_articles == 1 ? ' article' : ' articles');
				}
				if ($num_reviews) {
					$return_values[] = $num_reviews.($num_reviews == 1 ? ' review' : ' reviews');
				}
				if ($include_likes) {
					$likes = 0;
					$ids = array_merge ($article_ids, $review_ids);
					foreach ($ids as $id) {
						/**	@var evangelical_magazine_article */
						$object = evangelical_magazine::get_object_from_id($id);
						$likes += $object->get_facebook_stats();
					}
					if ($likes) {
						$return_values[] = number_format($likes).($likes == 1 ? ' like' : ' likes');
					}
					$last = (count($return_values) > 1) ? ' and '.array_pop ($return_values) : '';
					$return_value = implode (', ', $return_values).$last;
				}
			} else {
				$return_value = $num_articles+$num_reviews;
			}
			return $return_value;
		}
	}
}