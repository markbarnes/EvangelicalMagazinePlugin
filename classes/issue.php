<?php
class evangelical_magazine_issue {
    
    const ISSUE_DATE_META_NAME = 'evangelical_magazine_issue_date';
    const EARLIEST_YEAR = 2010;
    
    private $post_data, $year, $month;
    
    public function __construct($post) {
        if (!is_a ($post, 'WP_Post')) {
            $post = get_post ((int)$post);
        }
        $this->post_data = $post;
        $post_meta = get_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME, true);
        if (strlen($post_meta) == 7) {
            $this->year = substr($post_meta, 0, 4);
            $this->month = substr($post_meta, 5, 2);
        } else {
            $this->year = $this->month = null;
        }
    }
    
    public function get_id() {
        return $this->post_data->ID;
    }
        
    public function get_name($link = false) {
        if ($link) {
            return "<a href=\"{$this->get_link()}\">{$this->post_data->post_title}</a>";
        } else {
            return $this->post_data->post_title;
        }
    }
        
    public function get_link() {
        return get_permalink($this->post_data->ID);
    }
        
    public static function get_possible_issues() {
        return array ('01' => 'January/February', '03' => 'March/April', '05' => 'May/June', '07' => 'July/August', '09' => 'September/October', '11' => 'November/December');
    }
    
    public function get_date() {
        return array ('year' => $this->year, 'month' => $this->month);
    }
    
    public function get_image_url($image_size = 'thumbnail') {
        if (has_post_thumbnail($this->get_id())) {
            $src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
            if ($src) {
                return $src[0];
            }
        }
    }

    public function get_image_html($image_size = 'thumbnail', $class = '', $link=false) {
        if (has_post_thumbnail($this->get_id())) {
            $src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
            $class = (bool)$class ? " class=\"{$class}\"" : '';
            if ($src) {
                $html = "<img src=\"{$src[0]}\" width=\"{$src[1]}\" height=\"{$src[2]}\"{$class}/>";
                return $link ? "<a href=\"{$this->get_link()}\">{$html}</a>" : $html;
            }
        }
    }

    public function save_meta_data() {
        if (isset($_POST['em_issue_month']) && isset($_POST['em_issue_year'])) {
            update_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME, "{$_POST['em_issue_year']}-{$_POST['em_issue_month']}");
        } else {
            delete_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME);
        }
    }
    
    /**
    * Returns an array of all the issue objects
    * 
    * @param string $order_by
    * @return evangelical_magazine_issue[]
    */
    public static function get_all_issues($limit = -1) {
        $args = array ('post_type' => 'em_issue', 'meta_key' => evangelical_magazine_issue::ISSUE_DATE_META_NAME, 'orderby' => 'meta_value', 'order' => 'DESC', 'posts_per_page' => $limit);
        $query = new WP_Query($args);
        if ($query->posts) {
            $issues = array();
            foreach ($query->posts as $issue) {
                $issues[] = new evangelical_magazine_issue ($issue);
            }
            return $issues;
        }
    }
    
    public function get_articles($args = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::ISSUE_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => evangelical_magazine_article::PAGE_NUM_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'posts_per_page' => -1);
        $args = wp_parse_args ($args, $default_args);
        $query = new WP_Query($args);
        if ($query->posts) {
            $articles = array();
            foreach ($query->posts as $article) {
                $articles[] = new evangelical_magazine_article ($article);
            }
            return $articles;
        }
    }
    
    /**
    * Returns an array of article IDs for all articles in the issue
    * 
    * @param array $args
    * @return integer[]
    */
    public function get_article_ids($args = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::ISSUE_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => evangelical_magazine_article::PAGE_NUM_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'posts_per_page' => -1);
        $args = wp_parse_args ($args, $default_args);
        $query = new WP_Query($args);
        if ($query->posts) {
            $articles = array();
            foreach ($query->posts as $article) {
                $articles[] = $article->ID;
            }
            return $articles;
        }
    }
    
    public function get_author_ids() {
        $articles = $this->get_articles();
        if ($articles) {
            $all_authors = array();
            foreach ($articles as $article) {
                $all_authors = array_merge($all_authors, $article->get_author_ids());
            }
            return $all_authors;
        }
    }

    /**
    * Gets the post popular articles in the issue
    * 
    * @param integer $limit - the maximum number of articles to return
    * @return evangelical_magazine_article[]
    */
    public function get_top_articles ($limit = -1) {
        //We can't do this in one query, because WordPress won't return null values when you sort by meta_value
        $article_ids = $this->get_article_ids();
        if ($article_ids) {
            $articles = array();
            foreach ($article_ids as $article_id) {
                $articles[$article_id] = get_post_meta($article_id, evangelical_magazine_article::VIEW_COUNT_META_NAME, true);
            }
            arsort($articles);
            if ($limit != -1) {
                $articles = array_slice ($articles, 0, $limit, true);
            }
            $top_articles = array();
            foreach ($articles as $article => $view_count) {
                $top_articles[] = new evangelical_magazine_article($article);
            }
            return $top_articles;
        }
    }
    
    /**
    * Returns the number of articles in this issue
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