<?php

/**
* The main class for handling the series custom post type
* 
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_series extends evangelical_magazine_not_articles {
    
    /**
    * Returns all the articles in the series
    * 
    * @param int $limit
    * @param array $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function _get_articles ($args) {
        $meta_query = array(array('key' => evangelical_magazine_article::SERIES_META_NAME, 'value' => $this->get_id(), 'compare' => '='));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => evangelical_magazine_article::ORDER_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'ASC');
        return self::_get_articles_from_query($args, $default_args);
    }

    /**
    * Returns an array of all the series objects
    * 
    * @param string $order_by
    * @return evangelical_magazine_series[]
    */
    public static function get_all_series($args = array()) {
        $default_args = array ('post_type' => 'em_series', 'orderby' => 'post_title', 'order' => 'ASC', 'posts_per_page' => -1);
        return self::_get_series_from_query($args, $default_args);
    }
}