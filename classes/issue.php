<?php
class evangelical_magazine_issue extends evangelical_magazine_template {
    
    const ISSUE_DATE_META_NAME = 'evangelical_magazine_issue_date';
    const EARLIEST_YEAR = 2010;
    
    private $year, $month;
    
    /**
    * Instantiate the class by passing the WP_Post object or a post_id
    * 
    * @param integer|WP_Post $post
    */
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
    
    /**
    * Returns an array contain the date of this issue
    * 
    * @return array
    */
    public function get_date() {
        return array ('year' => $this->year, 'month' => $this->month);
    }
    
    /**
    * Saves the metadata when a post is edited
    * 
    * Called on the 'save_post_action
    * 
    */
    public function save_meta_data() {
        if (isset($_POST['em_issue_month']) && isset($_POST['em_issue_year'])) {
            update_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME, "{$_POST['em_issue_year']}-{$_POST['em_issue_month']}");
        } else {
            delete_post_meta ($this->get_id(), self::ISSUE_DATE_META_NAME);
        }
    }
    
    /**
    * Returns the articles from this issue
    * 
    * @param array $args
    * @return evangelical_magazine_article[]
    */
    public function get_articles($args = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::ISSUE_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'meta_key' => evangelical_magazine_article::PAGE_NUM_META_NAME, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'posts_per_page' => -1);
        return self::_get_articles($args, $default_args);
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
        return self::_get_object_ids($args, $default_args);
    }
    
    /**
    * Returns an array of author IDs for all authors in the issue
    * 
    * @return integer[]
    */
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
        return $this->_get_top_articles ($limit, $this);
    }
    
    /**
    * Gets the articles with future post_dates in this issue
    * 
    * @return evangelical_magazine_article[]
    */
    public function get_future_articles($args = array()) {
        $default_args = array ('post_status' => array ('future'));
        $args = wp_parse_args($args, $default_args);
        return $this->get_articles($args);
    }
    
    /**
    * Returns the HTML for a list of articles with thumbnails, title and author
    * 
    * @param integer $limit - the maximum number to return
    * @param boolean $include_future
    */
    public function get_html_article_list($args = array()) {
        $default_args = self::_future_posts_args();
        $default_args['order'] = 'ASC';
        $args = wp_parse_args($args, $default_args);
        $articles = $this->get_articles ($args);
        if ($articles) {
            $output = "<div class=\"article-list-box\">";
            $output .= "<ol>";
            $class=' first';
            foreach ($articles as $article) {
                $url = $class == '' ? $article->get_image_url('width_150') : $article->get_image_url('width_400');
                if ($article->is_future()) {
                    $output .= "<li class=\"future\"><div class=\"article-list-box-image{$class}\" style=\"background-image: url('{$url}')\"></div>";
                } else {
                    $output .= "<li><a href=\"{$article->get_link()}\"><div class=\"article-list-box-image{$class}\" style=\"background-image: url('{$url}')\"></div></a>";
                }
                $title = $article->get_title();
                $style = ($class & strlen($title) > 40) ? ' style="font-size:'.round(40/strlen($title)*1,2).'em"' : '';
                $output .= "<span class=\"article-list-box-title\"><span{$style}>{$article->get_title(true)}</span></span><br/><span class=\"article-list-box-author\">by {$article->get_author_names(!$article->is_future())}</span>";
                if ($article->is_future()) {
                    $output .= "<br/><span class=\"article-list-box-coming-soon\">Coming {$article->get_coming_date()}</span>";
                }
                "</li>";
                $class='';
            }
            $output .= "</ol>";
            $output .= '</div>';
            return $output;
        }
    }

    /**
    * Returns an array of the possible values for the issue date
    * 
    * @return array
    */
    public static function get_possible_issue_dates() {
        return array ('01' => 'January/February', '03' => 'March/April', '05' => 'May/June', '07' => 'July/August', '09' => 'September/October', '11' => 'November/December');
    }
    
    /**
    * Returns an array of all the issue objects
    * 
    * @param string $order_by
    * @return evangelical_magazine_issue[]
    */
    public static function get_all_issues($limit = -1) {
        $args = array ('post_type' => 'em_issue', 'meta_key' => evangelical_magazine_issue::ISSUE_DATE_META_NAME, 'orderby' => 'meta_value', 'order' => 'DESC', 'posts_per_page' => $limit);
        return self::_get_issues($args);
    }
}