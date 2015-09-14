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
        
        public function get_description() {
            return $this->post_data->post_content;
        }
        
        public function get_author_info_html() {
            return "<div class=\"author-info\"><a href=\"{$this->get_link()}\"><img class=\"author-image\" src=\"{$this->get_image_url('thumbnail_75')}\"/></a><div class=\"author-description\">{$this->get_description()}</div></div>";
        }

        /**
        * Returns an array of all the author objects
        * 
        * @param string $order_by
        * @return evangelical_magazine_author[]
        */
        private static function get_all_authors($order_by = 'title') {
            $args = array ('post_type' => 'em_author', 'orderby' => $order_by, 'order' => 'ASC', 'posts_per_page' => -1);
            $query = new WP_Query($args);
            if ($query->posts) {
                $authors = array();
                foreach ($query->posts as $author) {
                    $authors[] = new evangelical_magazine_author ($author);
                }
                return $authors;
            }
        }

    }