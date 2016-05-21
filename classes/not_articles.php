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
    * @return integer
    */
    public function get_article_count() {
        $article_ids = $this->get_article_ids();
        if ($article_ids) {
            return count ($article_ids);
        }
    }
}