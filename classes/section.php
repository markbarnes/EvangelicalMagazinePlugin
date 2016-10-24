<?php
/**
* The main class for handling the section custom post type
* 
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_section extends evangelical_magazine_not_articles {
    
    /**
    * Returns all the articles in the series
    * 
    * @param int $limit
    * @param array $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function _get_articles ($args) {
        $meta_query = array(array('key' => self::SECTION_META_NAME, 'value' => $this->get_id(), 'compare' => '='));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
        return self::_get_articles_from_query($args, $default_args);
    }

    /**
    * Returns an array of article IDs for all articles in a section
    * 
    * @param array $args
    * @return integer[]
    */
    public function get_article_ids($args = array()) {
        $meta_query = array(array('key' => self::SECTION_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'posts_per_page' => -1, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
        return self::_get_object_ids_from_query($args, $default_args);
    }
    
    /**
    * Gets the post popular articles in the issue
    * 
    * @param integer $limit - the maximum number of articles to return
    * @return evangelical_magazine_article[]
    */
    public function get_top_articles ($limit = -1, $exclude_article_ids = array()) {
        return $this->_get_top_articles_from_object ($limit, $this, $exclude_article_ids);
    }
    
    /**
    * Returns an array of all the section objects
    * 
    * @param string $order_by
    * @return evangelical_magazine_section[]
    */
    public static function get_all_sections($args = array()) {
        $default_args = array ('post_type' => 'em_section', 'orderby' => 'post_title', 'order' => 'ASC', 'posts_per_page' => -1);
        return self::_get_sections_from_query($args, $default_args);
    }
}