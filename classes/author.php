<?php
/**
* Accepts either a WP_Post object, or a post_id
*/
class evangelical_magazine_author {
    
    private $post_data;
    
    public function __construct ($post) {
        if (!is_a ($post, 'WP_Post')) {
            $post = get_post ((int)$post);
        }
        $this->post_data = $post;
    }
    
    public function get_id() {
        return $this->post_data->ID;
    }
    
    public function get_name() {
        return $this->post_data->post_title;
    }
    
    public function get_link() {
        return get_permalink($this->post_data->ID);
    }
    
    public function get_slug() {
        return $this->post_data->post_name;
    }
    
    public function get_image_url($image_size = 'thumbnail') {
        if (has_post_thumbnail($this->get_id())) {
            $src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
            if ($src) {
                return $src[0];
            }
        }
    }
    
    public function get_description($link_name = true) {
        if ($link_name) {
            return str_replace($this->get_name(), "<a href=\"{$this->get_link()}\">{$this->get_name()}</a>", $this->post_data->post_content);
        } else {
            return $this->post_data->post_content;
        }
    }
    
    public function get_filtered_description() {
        return wp_strip_all_tags($this->get_description(false));
    }
    
    public function get_author_info_html() {
        return "<div class=\"author-info\"><a href=\"{$this->get_link()}\"><img class=\"author-image\" src=\"{$this->get_image_url('thumbnail_75')}\"/></a><div class=\"author-description\">{$this->get_description()}</div></div>";
    }

    /**
    * Returns an array of all the author objects
    * 
    * @param array $args
    * @return evangelical_magazine_author[]
    */
    public static function get_all_authors($args = array()) {
        $default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
        $args = wp_parse_args ($args, $default_args);
        $query = new WP_Query($args);
        if ($query->posts) {
            $authors = array();
            foreach ($query->posts as $author) {
                $authors[] = new evangelical_magazine_author ($author);
            }
            return $authors;
        }
    }
    
    /**
    * Returns an array of all the author ids
    * 
    * @param array $args
    * @return integer[]
    */
    public static function get_all_author_ids($args = array()) {
        $default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
        $args = wp_parse_args ($args, $default_args);
        $query = new WP_Query($args);
        if ($query->posts) {
            $authors = array();
            foreach ($query->posts as $author) {
                $authors[] = $author->ID;
            }
            return $authors;
        }
    }
    
    /**
    * Returns all articles by this author
    * 
    * @param integer $limit
    * @param integer[] $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function get_articles ($limit = 9999, $exclude_article_ids = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::AUTHOR_META_NAME, 'value' => $this->get_id()));
        $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'post__not_in' => $exclude_article_ids);
        $query = new WP_Query($args);
        if ($query->posts) {
            $also_by = array();
            foreach ($query->posts as $article) {
                $also_by[] = new evangelical_magazine_article($article);
            }
            return $also_by;
        }
    }

    public static function get_all_authors_weighted_by_recent ($args = array()) {
        $authors = self::get_all_author_ids($args);
        if ($authors) {
            $authors = array_flip ($authors);
            $authors = array_fill_keys (array_keys($authors), 0); // Now we have an array with all the author ids as keys, and the value 0
            $issues = evangelical_magazine_issue::get_all_issues(18);
            if ($issues) {
                $issue_weighting = 18;
                foreach ($issues as $issue) {
                    $issue_authors = $issue->get_all_author_ids();
                    if ($issue_authors) {
                        foreach ($issue_authors as $issue_author) {
                            $authors[$issue_author] = $authors[$issue_author] + ($issue_weighting/6);
                        }
                    }
                    $issue_weighting--;
                }
                arsort($authors);
                $all_authors = array();
                foreach ($authors as $author => $weighting) {
                    $all_authors[] = new evangelical_magazine_author($author);
                }
                return $all_authors;
            }
        }
    }
}