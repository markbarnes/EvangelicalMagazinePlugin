<?php
class evangelical_magazine_section extends evangelical_magazine_not_articles {
    
    /**
    * Returns all the articles in the series
    * 
    * @param int $limit
    * @param array $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function get_articles ($limit = -1, $exclude_article_ids = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::SECTION_META_NAME, 'value' => $this->get_id(), 'compare' => '='));
        $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'post__not_in' => (array)$exclude_article_ids);
        return self::_get_articles_from_query($args);
    }

    /**
    * Returns an array of article IDs for all articles a section
    * 
    * @param array $args
    * @return integer[]
    */
    public function get_article_ids($args = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::SECTION_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'meta_query' => $meta_query, 'posts_per_page' => -1);
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
    * Returns the HTML of a list of articles in this section
    * 
    * @param int $limit
    * @param int[] $exlude_article_ids
    * @return string
    */
    public function article_list_box($limit, $exlude_article_ids = array()) {
        $articles = $this->get_articles($limit, $exlude_article_ids);
        if ($articles) {
            $ids = array();
            $output = "<div class=\"article-list-box\">";
            $output .= "<h3>{$this->get_name(true)}</h3>";
            $output .= "<ol>";
            $class=' first';
            foreach ($articles as $article) {
                $url = $class == '' ? $article->get_image_url('width_150') : $article->get_image_url('width_400');
                $output .= "<li><a href=\"{$article->get_link()}\"><div class=\"article-list-box-image{$class}\" style=\"background-image: url('{$url}')\"></div></a>";
                $title = $article->get_title();
                $style = strlen($title) > 40 ? ' style="font-size:'.round(40/strlen($title)*1,2).'em"' : '';
                $output .= "<span class=\"article-list-box-title\"><span{$style}>{$article->get_title(true)}</span></span><br/><span class=\"article-list-box-author\">by {$article->get_author_names(true)}</span></li>";
                $ids[] = $article->get_id();
                $class='';
            }
            $output .= "</ol>";
            $output .= '</div>';
            return array ('output' => $output, 'ids' => $ids);
        } else {
            return array ('output' => null, 'ids' => array());
        }
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