<?php
class evangelical_magazine_article {
    
    const AUTHOR_META_NAME = 'evangelical_magazine_authors';
    const ISSUE_META_NAME = 'evangelical_magazine_issue';
    const PAGE_NUM_META_NAME = 'evangelical_magazine_page_num';
    const SERIES_META_NAME = 'evangelical_magazine_series';
    const ORDER_META_NAME = 'evangelical_magazine_order';
    const SECTION_TAXONOMY_NAME = 'em_section';

    private $post_data, $issue, $authors, $page_num, $order, $sections;
    
    public function __construct ($post) {
        if (!is_a ($post, 'WP_Post')) {
            $post = get_post ((int)$post);
        }
        $this->post_data = $post;
        $issue_id = get_post_meta($this->get_id(), self::ISSUE_META_NAME, true);
        if ($issue_id) {
            $this->issue = new evangelical_magazine_issue($issue_id);
        } else {
            $this->issue = null;
        }
        $this->page_num = get_post_meta($this->get_id(), self::PAGE_NUM_META_NAME, true);
        $series_id = get_post_meta($this->get_id(), self::SERIES_META_NAME, true);
        if ($series_id) {
            $this->series = new evangelical_magazine_series($series_id);
        } else {
            $this->series = null;
        }
        $this->order = get_post_meta($this->get_id(), self::ORDER_META_NAME, true);
        $this->generate_sections_array();
        $this->generate_authors_array();
    }
    
    public function get_id() {
        return $this->post_data->ID;
    }
    
    public function get_title($link = false) {
        if ($link) {
            return "<a href=\"{$this->get_link()}\">{$this->post_data->post_title}</a>";
        } else {
            return $this->post_data->post_title;
        }
    }
    
    public function get_link() {
        return get_permalink($this->get_id());
    }
    
    public function has_issue() {
        return is_a($this->issue, 'evangelical_magazine_issue');
    }
    
    public function get_issue_id() {
        if ($this->has_issue()) {
            return $this->issue->get_id();
        }
    }
    
    public function get_issue_name($link = false) {
        if ($this->has_issue()) {
            if ($link) {
                return "<a class=\"issue-link\" href=\"{$this->issue->get_link()}\">{$this->issue->get_name()}</a>";
            } else {
                return $this->issue->get_name();
            }
        }
    }
    
    public function get_issue_page_num() {
        return $this->page_num;
    }
    
    public function has_series() {
        return is_a($this->series, 'evangelical_magazine_series');
    }
    
    public function get_series() {
        return $this->series;
    }
    
    public function get_series_id() {
        if ($this->has_series()) {
            return $this->series->get_id();
        }
    }
    
    public function get_series_name($link = false) {
        if ($this->has_series()) {
            return $this->series->get_name($link);
        }
    }
    
    public function get_series_order() {
        return $this->order;
    }
    
    public function has_sections() {
        return (bool)$this->sections;
    }
    
    public function get_sections() {
        return $this->sections;
    }
    
    private function generate_sections_array() {
        $sections = get_the_terms($this->get_id(), self::SECTION_TAXONOMY_NAME);
        if ($sections) {
            $this->sections = array();
            foreach ($sections as $section) {
                $this->sections[$section->name] = new evangelical_magazine_section($section);
            }
        } else {
            $this->sections = null;
        }
    }
    
    public function get_authors() {
        return $this->authors;
    }
    
    public function get_author_ids() {
        $authors = get_post_meta ($this->get_id(), self::AUTHOR_META_NAME);
        return (array)$authors;
    }
    
    private function generate_authors_array() {
        $authors_ids = $this->get_author_ids();
        if ($authors_ids) {
            $authors = array();
            foreach ($authors_ids as $author_id) {
                $author = new evangelical_magazine_author($author_id);
                $authors[$author->get_name()] = $author;
            }
            $this->authors = $authors;
        } else {
            $this->authors = null;
        }
    }
    
    public function get_author_names($link = false) {
        if (is_array($this->authors)) {
            $output = array();
            foreach ($this->authors as $author) {
                if ($link) {
                    $output[] = "<a class=\"author-link\" href=\"{$author->get_link()}\">{$author->get_name()}</a>";
                } else {
                    $output[] = $author->get_name();
                }
            }
            if (count($output) > 1) {
                $last = ' and '.array_pop ($output);
            } else {
                $last = '';
            }
            return implode (', ', $output).$last;
        }
    }

    public function get_issue_date() {
        if ($this->has_issue()) {
            return $this->issue->get_date();
        }
    }
    
    public function save_meta_data() {
        delete_post_meta ($this->get_id(), self::AUTHOR_META_NAME);
        if (isset($_POST['em_authors'])) {
            if (is_array($_POST['em_authors'])) {
                foreach ($_POST['em_authors'] as $author) {
                    add_post_meta ($this->get_id(), self::AUTHOR_META_NAME, $author);
                }
            }
        }
        $this->generate_authors_array();
        if (isset($_POST['em_issue'])) {
            update_post_meta ($this->get_id(), self::ISSUE_META_NAME, $_POST['em_issue']);
            $this->issue = new evangelical_magazine_issue($_POST['em_issue']);
        } else {
            delete_post_meta ($this->get_id(), self::ISSUE_META_NAME);
            $this->issue = null;
        }
        if (isset($_POST['em_page_num'])) {
            $this->page_num = (int)$_POST['em_page_num'];
            update_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME, $this->page_num);
        } else {
            delete_post_meta ($this->get_id(), PAGE_NUM_META_NAME);
            $this->page_num = null;
        }
        if (isset($_POST['em_series'])) {
            update_post_meta ($this->get_id(), self::SERIES_META_NAME, $_POST['em_series']);
            $this->series = new evangelical_magazine_series($_POST['em_series']);
        } else {
            delete_post_meta ($this->get_id(), self::SERIES_META_NAME);
            $this->series = null;
        }
        if (isset($_POST['em_order'])) {
            $this->order = (int)$_POST['em_order'];
            update_post_meta ($this->get_id(), self::ORDER_META_NAME, $this->order);
        } else {
            delete_post_meta ($this->get_id(), ORDER_META_NAME);
            $this->series = null;
        }
    }
    
    public function get_articles_by_same_authors($limit = 5, $exclude_this_article = true) {
        $author_ids = $this->get_author_ids();
        if ($author_ids) {
            $meta_query = array(array('key' => self::AUTHOR_META_NAME, 'value' => $author_ids, 'compare' => 'IN'));
            $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'orderby' => 'rand');
            if ($exclude_this_article) {
                $args ['post__not_in'] = array($this->get_id());
            }
            $query = new WP_Query($args);
            if ($query->posts) {
                $also_by = array();
                foreach ($query->posts as $article) {
                    $also_by[] = new evangelical_magazine_article($article);
                }
                return $also_by;
            }
        }
    }
    
    public function get_articles_in_same_series($limit = 99, $exclude_this_article = false) {
        $series = $this->get_series();
        $exclude_ids = $exclude_this_article ? (array)$this->get_id() : array();
        return $series->get_articles_in_this_series($limit, $exclude_ids);
    }
    
    public function get_image_url($image_size = 'thumbnail') {
        if (has_post_thumbnail($this->get_id())) {
            $src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
            if ($src) {
                return $src[0];
            }
        }
    }
    
    public function get_small_box_html($add_links = true, $sub_title = '', $class = '') {
        if (has_post_thumbnail($this->get_id())) {
            $src = $this->get_image_url($image_size = 'width_210');
            $style = "style=\"background-image: url('{$src}'); width:210px; height:140px; background-position: center center; background-size: cover\"";
        } else {
            $style = '';
        }
        $class = trim("small-article-box {$class}");
        $sub_title = $sub_title ? "<span class=\"sub-title\">{$sub_title}</span>" : '';
        if ($add_links) {
            return "<aside class=\"{$class}\">{$sub_title}<a href=\"{$this->get_link()}\"><div class=\"article-image\"{$style}></div></a><div class=\"article-title\">{$this->get_title(true)}</div></aside>";
        } else {
            return "<aside class=\"{$class}\"><div class=\"article-image\"{$style}>{$sub_title}</div><div class=\"article-title\">{$this->get_title()}</div></aside>";
        }
    }

}