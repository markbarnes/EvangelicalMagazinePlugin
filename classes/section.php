<?php
class evangelical_magazine_section {
    
    private $term_data;
    
    /**
    * Instantiate the class by passing the WP_Post object or a post_id
    * 
    * @param integer|WP_Post $post
    */
    public function __construct($term) {
        if (!is_a ($term, 'stdClass')) {
            $term = get_term ((int)$term, evangelical_magazine_article::SECTION_TAXONOMY_NAME);
        }
        $this->term_data = $term;
    }
    
    /**
    * Returns the post ID
    * 
    * @return integer
    */
    public function get_id() {
        return $this->term_data->term_id;
    }
        
    /**
    * Returns the name of the section
    * 
    * @param boolean $link - include a HTML link
    * @return string
    */
    public function get_name($link = false) {
        if ($link) {
            return "<a href =\"{$this->get_link()}\">{$this->term_data->name}</a>";
        } else {
            return $this->term_data->name;
        }
    }
        
    /**
    * Returns the link to this section
    * 
    * @return string
    */
    public function get_link() {
        return get_term_link($this->term_data);
    }
    
    /**
    * Returns an array of all articles in this section
    * 
    * @param int $limit
    * @param int[] $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function get_articles ($limit = -1, $exclude_article_ids = array()) {
        $tax_query = array(array('taxonomy' => evangelical_magazine_article::SECTION_TAXONOMY_NAME, 'field' => 'term_id', 'terms' => $this->get_id(), 'operator' => 'IN'));
        $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'tax_query' => $tax_query, 'post__not_in' => (array)$exclude_article_ids);
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
    * Returns the total number of articles in this section
    * 
    */
    public function get_count() {
        return $this->term_data->count;
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
    * Gets all available sections
    * 
    * @param mixed $args
    * @uses get_terms
    * @return evangelical_magazine_section[]
    */
    public static function get_all_sections($args = array()) {
        $taxonomy = evangelical_magazine_article::SECTION_TAXONOMY_NAME;
        $args = wp_parse_args ($args, array ('hide_empty' => false));
        $terms = get_terms ((array)$taxonomy, $args);
        if ($terms) {
            $sections = array();
            foreach ($terms as $term) {
                $sections[] = new evangelical_magazine_section($term);
            }
            return $sections;
        }
    }
}