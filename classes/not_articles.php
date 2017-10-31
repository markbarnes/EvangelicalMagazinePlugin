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
abstract class evangelical_magazine_not_articles extends evangelical_magazine_template {

	/**
	* Helper function to gets the post popular articles in an object
	*
	* @param integer $limit - the maximum number of articles to return
	* @param mixed $object
	* @param array $exclude_article_ids
	* @return evangelical_magazine_article[]
	*/
	protected function _get_top_articles_from_object ($limit = -1, $object, $exclude_article_ids = array()) {
		$articles = $object->get_articles(-1, $exclude_article_ids);
		return self::_get_top_articles ($articles, $limit);
	}

	/**
	* Returns an array of articles
	*
	* @param int $limit
	* @param int[] $exclude_article_ids
	* @return evangelical_magazine_article[]
	*/
	public function get_articles ($limit = -1, $exclude_article_ids = array()) {
		return $this->_get_articles(array ('posts_per_page' => $limit, 'post__not_in' => (array)$exclude_article_ids));
	}

	/**
	* Returns the number of articles in an object
	*
	* @param bool $include_text - whether or not the string ' article(s)' should be appended to the return value
	* @param book $include_likes - whether or not Facebook likes should be appended. Requires $include_text to be true.
	* @return integer
	*/
	public function get_article_count($include_text = false, $include_likes = false) {
		$article_ids = $this->get_article_ids();
		if ($article_ids) {
			$num_articles = count ($article_ids);
			if ($include_text) {
				$return_value = $num_articles.($num_articles == 1 ? ' article' : ' articles');
				if ($include_likes) {
					$likes = 0;
					foreach ($article_ids as $article_id) {
						/**
						* @var evangelical_magazine_article
						*/
						$article = evangelical_magazine::get_object_from_id($article_id);
						$likes += $article->get_facebook_stats();
					}
					if ($likes) {
						$return_value .= ', '.number_format($likes).($likes == 1 ? ' like' : ' likes');
					}
				}
			} else {
				$return_value = $num_articles;
			}
			return $return_value;
		}
	}
}