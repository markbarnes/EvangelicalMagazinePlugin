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
        
    public function get_name() {
        return $this->post_data->post_title;
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
    public static function get_all_issues($limit = 999) {
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
    
    public function get_all_articles() {
        $meta_query = array(array('key' => evangelical_magazine_article::ISSUE_META_NAME, 'value' => $this->get_id()));
        $args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => evangelical_magazine_article::PAGE_NUM_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'DESC');
        $query = new WP_Query($args);
        if ($query->posts) {
            $articles = array();
            foreach ($query->posts as $article) {
                $articles[] = new evangelical_magazine_article ($article);
            }
            return $articles;
        }
        
    }

}