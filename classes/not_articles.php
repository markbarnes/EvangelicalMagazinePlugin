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
        $articles = $this->get_articles ();
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