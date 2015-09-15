<?php
class evangelical_magazine_section {
    
    private $term_data;
    
    public function __construct($term) {
        if (!is_a ($term, 'stdClass')) {
            $term = get_term ((int)$term, evangelical_magazine_article::SECTION_TAXONOMY_NAME);
        }
        $this->term_data = $term;
    }
    
    public function get_id() {
        return $this->term_data->term_id;
    }
        
    public function get_name($link = false) {
        if ($link) {
            return "<a href =\"{$this->get_link()}\">{$this->term_data->name}</a>";
        } else {
            return $this->term_data->name;
        }
    }
        
    public function get_link() {
        return get_term_link($this->term_data);
    }
    
    public function get_articles ($limit = 99, $exclude_article_ids = array()) {
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
    
    public function get_count() {
        return $this->term_data->count;
    }
    
    public function get_info_box($limit, $exlude_article_ids = array()) {
        $articles = $this->get_articles($limit, $exlude_article_ids);
        if ($articles) {
            $ids = array();
            $output = "<div class=\"section-info-box\">";
            $output .= "<h3>{$this->get_name(true)}</h3>";
            $output .= "<ol>";
            $class=' first';
            foreach ($articles as $article) {
                $url = $class == '' ? $article->get_image_url('width_150') : $article->get_image_url('width_400');
                $output .= "<li><a href=\"{$article->get_link()}\"><div class=\"section-info-box-image{$class}\" style=\"background-image: url('{$url}')\"></div></a>";
                $title = $article->get_title();
                $style = strlen($title) > 40 ? ' style="font-size:'.round(40/strlen($title)*1,2).'em"' : '';
                $output .= "<span class=\"section-info-box-title\"><span{$style}>{$article->get_title(true)}</span></span><br/><span class=\"section-info-box-author\">by {$article->get_author_names(true)}</span></li>";
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
}