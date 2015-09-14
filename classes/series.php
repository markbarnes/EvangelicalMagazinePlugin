<?php
class evangelical_magazine_series {
    
    private $post_data;
    
    public function __construct($post) {
        if (!is_a ($post, 'WP_Post')) {
            $post = get_post ((int)$post);
        }
        $this->post_data = $post;
    }
    
    public function get_id() {
        return $this->post_data->ID;
    }
        
    public function get_name($link = false) {
        if ($link) {
            return "<a class=\"series-link\" href=\"{$this->get_link()}\">{$this->post_data->post_title}</a>";
        } else {
            return $this->post_data->post_title;
        }
    }
        
    public function get_link() {
        return get_permalink($this->post_data->ID);
    }
    
    public function get_articles_in_this_series ($limit = 99, $exclude_article_ids = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::SERIES_META_NAME, 'value' => $this->get_id(), 'compare' => '='));
        $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'meta_key' => evangelical_magazine_article::ORDER_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'ASC', 'post__not_in' => (array)$exclude_article_ids);
        $query = new WP_Query($args);
        if ($query->posts) {
            $also_in = array();
            foreach ($query->posts as $article) {
                $also_in[] = new evangelical_magazine_article($article);
            }
            return $also_in;
        }
    }

    /**
    * Returns an array of all the series objects
    * 
    * @param string $order_by
    * @return evangelical_magazine_series[]
    */
    public static function get_all_series() {
        $args = array ('post_type' => 'em_series', 'orderby' => 'post_title', 'order' => 'ASC', 'posts_per_page' => -1);
        $query = new WP_Query($args);
        if ($query->posts) {
            $series = array();
            foreach ($query->posts as $s) {
                $series[] = new evangelical_magazine_series ($s);
            }
            return $series;
        }
    }


}