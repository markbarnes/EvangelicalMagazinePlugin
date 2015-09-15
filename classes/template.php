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
class evangelical_magazine_template {
    
    /**
    * All the custom posttype data is stored in $post_data as a WP_Post object
    * 
    * @var WP_Post
    */
    protected $post_data;
    
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
        return wp_strip_all_tags($this->get_content);
    }
}