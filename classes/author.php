<?php
/**
* Accepts either a WP_Post object, or a post_id
*/
class evangelical_magazine_author extends evangelical_magazine_not_articles {
    
    /**
    * Returns the post content of an author post
    * 
    * @param boolean $link_name
    * @return string
    */
    public function get_description($link_name = true) {
        if ($link_name) {
            return str_replace($this->get_name(), $this->get_name(true), $this->post_data->post_content);
        } else {
            return $this->post_data->post_content;
        }
    }
    
    /**
    * Returns the HTML of a thumbnail and name of the author
    * 
    * @return string
    */
    public function get_author_info_html() {
        return "<div class=\"author-info\">".$this->get_link_html("<img class=\"author-image\" src=\"{$this->get_image_url('square_thumbnail_tiny')}\"/>")."<div class=\"author-description\">{$this->get_description()}</div></div>";
    }

    /**
    * Returns all articles by this author
    * 
    * @param integer $limit
    * @param integer[] $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function _get_articles ($args = array()) {
        $meta_query = array(array('key' => self::AUTHOR_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'posts_per_page' => -1, 'meta_query' => $meta_query, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
        return self::_get_articles_from_query($args, $default_args);
    }

    /**
    * Returns an array of all the author objects
    * 
    * @param array $args
    * @return evangelical_magazine_author[]
    */
    public static function get_all_authors($args = array()) {
        $default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
        return self::_get_authors_from_query($args, $default_args);
    }
    
    /**
    * Returns an array of all the author ids
    * 
    * @param array $args
    * @return integer[]
    */
    public static function get_all_author_ids($args = array()) {
        $default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
        return self::_get_object_ids_from_query($args, $default_args);
    }
    
    /**
    * Returns an array of authors, sorted by popularity
    * 
    * @param integer $limit
    * @return evangelical_magazine_author[]
    */
    public static function get_top_authors ($limit = -1) {
        global $wpdb;
        // For performance reasons, we can't do this through WP_Query
        $view_meta_key = self::VIEW_COUNT_META_NAME;
        $author_meta_key = self::AUTHOR_META_NAME;
        $limit_sql = ($limit == -1) ? '' : " LIMIT 0, {$limit}";
        $author_ids = $wpdb->get_col ("SELECT meta_author.meta_value, AVG(meta_views.meta_value/DATEDIFF(NOW(), post_date)) AS average_views_per_day FROM {$wpdb->postmeta} AS meta_views, {$wpdb->postmeta} AS meta_author, {$wpdb->posts} WHERE ID=meta_views.post_id AND ID=meta_author.post_id AND meta_views.meta_key='{$view_meta_key}' AND meta_author.meta_key='{$author_meta_key}' AND post_status='publish' AND post_type = 'em_article' GROUP BY meta_author.meta_value ORDER BY average_views_per_day DESC{$limit_sql}");
        if ($limit == -1 || count($author_ids) < $limit) {
            //Now we need to add authors that have been missed, because they have no views
            $more_authors = evangelical_magazine_author::get_all_author_ids(array ('post__not_in' => (array)$author_ids));
            $author_ids = $more_authors ? array_merge($author_ids, $more_authors) : $author_ids;
            $author_ids = ($limit == -1) ? $author_ids : array_slice($author_ids, 0, $limit);
        }
        if ($author_ids) {
            $authors = array();
            foreach ($author_ids as $id) {
                $authors[] = new evangelical_magazine_author($id);
            }
        }
        return $authors;
    }
    
    public static function _compare_authors_alphabetically ($a, $b) {
        return strcasecmp($a->get_name(), $b->get_name());
    }
    
    /**
    * Sorts an array of authors into alphabetical order (by first name)
    * 
    * @param mixed $authors
    */
    public static function sort_authors_alphabetically(&$authors) {
        uasort($authors, array(__CLASS__, '_compare_authors_alphabetically'));
    }
}