<?php

/**
* A helper class used by the other custom post type classes
* 
* Contains common functions such as get_id()
* 
* @package evangelical-magazine-theme
* @author Mark Barnes
* @access public
*/
abstract class evangelical_magazine_template {
    
    /**
    * All the custom posttype data is stored in $post_data as a WP_Post object
    * 
    * @var WP_Post
    */
    protected $post_data;
    
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
    }
    
    /**
    * Returns the post ID
    * 
    * @return integer
    */
    public function get_id() {
        return $this->post_data->ID;
    }
    
    protected function get_friendly_class() {
        return str_replace('evangelical_magazine_', '', get_called_class());
    }
        
    /**
    * Returns the name of the object
    * 
    * @param boolean $link - include a HTML link
    * @return string
    */
    public function get_name($link = false) {
        if ($link) {
            return "<a class=\"{$this->get_friendly_class()}-link\" href=\"{$this->get_link()}\">{$this->post_data->post_title}</a>";
        } else {
            return $this->post_data->post_title;
        }
    }
        
    /**
    * Returns the permalink to the object
    * 
    * @return string;
    */
    public function get_link() {
        return get_permalink($this->post_data->ID);
    }

    /**
    * Returns true if the post has a future publish date
    * 
    * @return boolean
    */
    public function is_future() {
        return ($this->post_data->post_status == 'future');
    }
    
    /**
    * Returns the page slug
    * 
    * @return string
    */
    public function get_slug() {
        return $this->post_data->post_name;
    }
    
    /**
    * Returns the post date
    * 
    * @return string
    */
    public function get_post_date() {
        return $this->post_data->post_date;
    }
    
    /**
    * Returns the URL of the featured image
    * 
    * @param string $image_size
    * @return string
    */
    public function get_image_url($image_size = 'thumbnail') {
        if (has_post_thumbnail($this->get_id())) {
            $src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
            if ($src) {
                return $src[0];
            }
        }
    }
    
    /**
    * Returns an HTML <img> tag for the featured image
    * 
    * @param string $image_size
    * @param string $class
    * @param boolean $link
    * @return string
    */
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
    
    /** 
    * Returns the post_content
    * 
    * @return string
    */
    public function get_content () {
        return $this->post_data->post_content;
    }
    
    /**
    * Returns the post_content with all HTML stripped
    * 
    * @return string
    */
    public function get_filtered_content() {
        return wp_strip_all_tags($this->get_content());
    }

    /**
    * Returns the HTML for a list of articles with thumbnails, title and author
    * 
    * @param array $articles
    */
    public function get_html_article_list($articles, $add_class_to_first = true) {
        if ($articles) {
            $output = "<div class=\"article-list-box\">";
            $output .= "<ol>";
            $class = $add_class_to_first ? ' first' : '';
            foreach ($articles as $article) {
                $url = ($class == '') ? $article->get_image_url('width_150') : $article->get_image_url('width_400');
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
    * Helper function to help subclasses return all the objects from a WP_Query
    * 
    * @param array $args
    * @param array $default_args
    * @param string $class - the class to return (without the 'evangelical_magazine_')
    * @uses WP_Query
    * @return array
    */
    protected static function _get_objects_from_query ($args, $default_args, $class) {
        $class = "evangelical_magazine_{$class}";
        $args = wp_parse_args($args, $default_args);
        $query = new WP_Query($args);
        if ($query->posts) {
            $objects = array();
            foreach ($query->posts as $post) {
                $objects[] = new $class($post);
            }
            return $objects;
        }
    }

    /**
    * Helper function to help subclasses return all the articles from a WP_Query
    * 
    * Wrapper for _get_objects()
    * 
    * @param array $args
    * @param array $default_args
    * @uses evangelical_magazine_template::_get_objects()
    * @return evangelical_magazine_article[]
    */
    protected static function _get_articles_from_query ($args, $default_args = '') {
        return self::_get_objects_from_query($args, $default_args, 'article');
    }

    /**
    * Helper function to help subclasses return all the sections from a WP_Query
    * 
    * Wrapper for _get_objects()
    * 
    * @param array $args
    * @param array $default_args
    * @uses evangelical_magazine_template::_get_objects()
    * @return evangelical_magazine_section[]
    */
    protected static function _get_sections_from_query ($args, $default_args = '') {
        return self::_get_objects_from_query($args, $default_args, 'section');
    }

    /**
    * Helper function to help subclasses return all the series from a WP_Query
    * 
    * Wrapper for _get_objects()
    * 
    * @param array $args
    * @param array $default_args
    * @uses evangelical_magazine_template::_get_objects()
    * @return evangelical_magazine_series[]
    */
    protected static function _get_series_from_query ($args, $default_args = '') {
        return self::_get_objects_from_query($args, $default_args, 'series');
    }

   /**
    * Helper function to help subclasses return all the issues from a WP_Query
    * 
    * Wrapper for _get_objects()
    * 
    * @param array $args
    * @param array $default_args
    * @uses evangelical_magazine_template::_get_objects()
    * @return evangelical_magazine_issue[]
    */
    protected static function _get_issues_from_query ($args, $default_args = '') {
        return self::_get_objects_from_query($args, $default_args, 'issue');
    }

   /**
    * Helper function to help subclasses return all the authors from a WP_Query
    * 
    * Wrapper for _get_objects()
    * 
    * @param array $args
    * @param array $default_args
    * @uses evangelical_magazine_template::_get_objects()
    * @return evangelical_magazine_author[]
    */
    protected static function _get_authors_from_query ($args, $default_args = '') {
        return self::_get_objects_from_query($args, $default_args, 'author');
    }

    /**
    * Helper function to help subclasses return all the object_ids from a WP_Query
    * 
    * @param array $args
    * @param array $default_args
    * @param string $class - the class to return (without the 'evangelical_magazine_')
    * @uses WP_Query
    * @return array
    */
    protected static function _get_object_ids_from_query ($args, $default_args) {
        $args = wp_parse_args($args, $default_args);
        $query = new WP_Query($args);
        if ($query->posts) {
            $post_ids = array();
            foreach ($query->posts as $post) {
                $post_ids[] = $post->ID;
            }
            return $post_ids;
        }
    }

    /**
    * Helper function that returns the arguments needed to return future posts in addition to what is normally returned
    * 
    * @return array
    */
    public static function _future_posts_args () {
        if (is_user_logged_in()) {
            return array ('post_status' => array ('publish', 'future', 'private'));
        } else {
            return array ('post_status' => array ('publish', 'future'));
        }
    }

}