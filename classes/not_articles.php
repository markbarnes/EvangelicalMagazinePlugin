<?php

/**
* A helper class used by most of the other custom post type classes
* 
* Contains common functions such as get_id()
* 
* @package evangelical-magazine-theme
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
        //We can't do this in one query, because WordPress won't return null values when you sort by meta_value
        $articles = $object->get_articles(-1, $exclude_article_ids);
        if ($articles) {
            $index = array();
            foreach ($articles as $key => $article) {
                 $view_count = get_post_meta($article->get_id(), evangelical_magazine_article::VIEW_COUNT_META_NAME, true);
                 $index[$key] = round ($view_count/(time()-strtotime($article->get_post_date()))*84600 , 5);
            }
            arsort($index);
            if ($limit != -1) {
                $index = array_slice ($index, 0, $limit, true);
            }
            $top_articles = array();
            foreach ($index as $key => $view_count) {
                $top_articles[] = $articles[$key];
            }
            return $top_articles;
        }
    }

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