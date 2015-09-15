<?php
class evangelical_magazine_article extends evangelical_magazine_template {
    
    const AUTHOR_META_NAME = 'evangelical_magazine_authors';
    const ISSUE_META_NAME = 'evangelical_magazine_issue';
    const PAGE_NUM_META_NAME = 'evangelical_magazine_page_num';
    const SERIES_META_NAME = 'evangelical_magazine_series';
    const ORDER_META_NAME = 'evangelical_magazine_order';
    const VIEW_COUNT_META_NAME = 'evangelical_magazine_view_count';
    const SECTION_TAXONOMY_NAME = 'em_section';

    private $issue, $authors, $page_num, $order, $sections;
    
    /**
    * Instantiate the class by passing the WP_Post object or a post_id
    * 
    * @param integer|WP_Post $post
    */
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
    
    /**
    * Returns the title of the article
    * 
    * @param boolean $link - include a HTML link
    * @return string
    */
    public function get_title($link = false) {
        if ($link && !$this->is_future()) {
            return "<a class=\"article-link\" href=\"{$this->get_link()}\">{$this->post_data->post_title}</a>";
        } else {
            return $this->post_data->post_title;
        }
    }
    
    /**
    * Wrapper for get_title()
    * 
    * @param boolean $link
    * @return string
    */
    public function get_name ($link = false) {
        return $this->get_title ($link);
    }
    
    /**
    * Returns true if this article has an issue specified
    * 
    * @return bool
    */
    public function has_issue() {
        return is_a($this->issue, 'evangelical_magazine_issue');
    }
    
    /**
    * Returns the post id of the issue
    * 
    * @return integer
    */
    public function get_issue_id() {
        if ($this->has_issue()) {
            return $this->issue->get_id();
        }
    }
    
    /**
    * Returns the name of the issue
    * 
    * @param bool $link
    * @return string
    */
    public function get_issue_name($link = false) {
        if ($this->has_issue()) {
            return $this->issue->get_name($link);
        }
    }
    
    /**
    * Returns the article's page number
    * 
    * @return integer
    */
    public function get_page_num() {
        return $this->page_num;
    }
    
    /**
    * Returns true if the article is part of a series
    * 
    * @return bool
    */
    public function has_series() {
        return is_a($this->series, 'evangelical_magazine_series');
    }
    
    /**
    * Returns the series object
    * 
    * @return evangelical_magazine_series
    * 
    */
    public function get_series() {
        return $this->series;
    }
    
    /**
    * Returns the post ID of the series
    * 
    * @return integer
    */
    public function get_series_id() {
        if ($this->has_series()) {
            return $this->series->get_id();
        }
    }
    
    /**
    * Returns the name of the series
    * 
    * @param bool $link
    * @return string
    */
    public function get_series_name($link = false) {
        if ($this->has_series()) {
            return $this->series->get_name($link);
        }
    }
    
    /**
    * Returns the article's position in the series
    * 
    * @return int
    */
    public function get_series_order() {
        return $this->order;
    }
    
    /**
    * Returns true if the article is in a section
    * 
    * @return bool
    */
    public function has_sections() {
        return (bool)$this->sections;
    }
    
    /**
    * Returns an array of sections objects for this article
    * 
    * @return evangelical_magazine_section[]
    */
    public function get_sections() {
        return $this->sections;
    }
    
    /**
    * Populates $this->sections
    */
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
    
    /**
    * Returns an array of author objects for this article
    * 
    * @return evangelical_magazine_author[]
    */
    public function get_authors() {
        return $this->authors;
    }
    
    /**
    * Returns an array of author post IDs for this article
    * 
    * @return integer[]
    */
    public function get_author_ids() {
        $authors = get_post_meta ($this->get_id(), self::AUTHOR_META_NAME);
        return (array)$authors;
    }
    
    /**
    * Populates $this->authors
    */
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
    
    /**
    * Returns a list of author names
    * 
    * @param bool $link
    * @return string
    */
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

    
    /**
    * Returns the URL of the featured image
    * 
    * @param string $image_size
    * @return string
    */
    public function get_image_url($image_size = 'thumbnail') {
        $image_sizes = get_intermediate_image_sizes();
        if ($this->is_future() && substr($image_size, -3) != '_bw' && in_array("{$image_size}_bw", $image_sizes)) {
            $image_size = "{$image_size}_bw";
        }
        if (has_post_thumbnail($this->get_id())) {
            $src = wp_get_attachment_image_src (get_post_thumbnail_id($this->get_id()), $image_size);
            if ($src) {
                return $src[0];
            }
        }
    }
    
    /**
    * Returns the date of the issue
    * 
    * @return array
    */
    public function get_issue_date() {
        if ($this->has_issue()) {
            return $this->issue->get_date();
        }
    }
    
    /**
    * Returns the date the post was/will be published online
    * 
    * @param string $date_format
    * @return string
    */
    public function get_publish_date ($date_format = 'j F Y') {
        return date($date_format, strtotime($this->post_data->post_date));
    }
    
    /**
    * Returns a friendly string with the date that the article will be coming
    * 
    * @return string
    */
    public function get_coming_date() {
        $publish_date = str_replace(' '.date('Y'), '', $this->get_publish_date());
        if ($publish_date == date('j F')) {
            return 'later today';
        } elseif ($publish_date == date('j F', strtotime('tomorrow'))) {
            return 'tomorrow';
        } else {
            return "on {$publish_date}";
        }
        
    }
    
    /**
    * Saves the metadata when the post is edited
    * 
    * Called during the 'save_post' action
    * 
    */
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
    
    /**
    * Returns all articles by the same author(s) as this article
    * 
    * @param int $limit
    * @param int[] $exclude_this_article - an array of post ids
    * @return evangelical_magazine_article[]
    */
    public function get_articles_by_same_authors($limit = 5, $exclude_this_article = true) {
        $author_ids = $this->get_author_ids();
        if ($author_ids) {
            $meta_query = array(array('key' => self::AUTHOR_META_NAME, 'value' => $author_ids, 'compare' => 'IN'));
            $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'orderby' => 'rand');
            if ($exclude_this_article) {
                $args ['post__not_in'] = array($this->get_id());
            }
            return self::_get_articles($args);
        }
    }
    
    /**
    * Returns all articles in the same series as this article
    * 
    * @param int $limit
    * @param int[] $exclude_this_article
    * @return evangelical_magazine_article[]
    */
    public function get_articles_in_same_series($limit = 99, $exclude_this_article = false) {
        $series = $this->get_series();
        $exclude_ids = $exclude_this_article ? (array)$this->get_id() : array();
        return $series->get_articles($limit, $exclude_ids);
    }
    
    /**
    * Gets the number of views
    * 
    * @return integer
    * 
    */
    public function get_view_count() {
        return get_post_meta($this->get_id(), self::VIEW_COUNT_META_NAME, true);
    }
    
    /**
    * Increases the view count by one
    * 
    */
    public function record_view_count()  {
        $view_count = $this->get_view_count();
        update_post_meta ($this->get_id(), self::VIEW_COUNT_META_NAME, $view_count+1);
    }

    /**
    * Returns the HTML which produces the small article box
    * 
    * @param bool $add_links
    * @param string $sub_title
    * @param string $class
    * @return string
    */
    public function get_small_box_html($add_links = true, $sub_title = '', $class = '') {
        if (has_post_thumbnail($this->get_id())) {
            $src = $this->get_image_url($image_size = 'width_210');
            $style = "style=\"background-image: url('{$src}'); width:210px; height:140px; background-position: center center; background-size: cover\"";
        } else {
            $style = '';
        }
        $class = trim("small-article-box {$class}");
        $class .= $this->is_future() ? ' future' : '';
        $sub_title = $sub_title ? "<span class=\"sub-title\">{$sub_title}</span>" : '';
        if ($add_links && !$this->is_future()) {
            return "<aside class=\"{$class}\">{$sub_title}<a href=\"{$this->get_link()}\"><div class=\"article-image\"{$style}></div></a><div class=\"article-title\">{$this->get_title(true)}</div></aside>";
        } else {
            return "<aside class=\"{$class}\"><div class=\"article-image\"{$style}>{$sub_title}</div><div class=\"article-title\">{$this->get_title()}</div></aside>";
        }
    }
    
    /**
    * Adds metaboxes to articles custom post type
    * 
    */
    public static function article_meta_boxes() {
        add_meta_box ('em_issues', 'Issue', array(get_called_class(), 'do_issue_meta_box'), 'em_article', 'side', 'core');
        add_meta_box ('em_authors', 'Author(s)', array(get_called_class(), 'do_author_meta_box'), 'em_article', 'side', 'core');
        add_meta_box ('em_series', 'Series', array(get_called_class(), 'do_series_meta_box'), 'em_article', 'side', 'core');
    }
    
    /**
    * Adds metaboxes to issues custom post type
    * 
    */
    public static function issue_meta_boxes() {
        add_meta_box ('em_issue_date', 'Date', array(get_called_class(), 'do_issue_date_meta_box'), 'em_issue', 'side', 'core');
    }
    
    /**
    * Outputs the author meta box
    * 
    * @param mixed $article
    */
    public static function do_author_meta_box($article) {
        $authors = evangelical_magazine_author::get_all_authors();
        if ($authors) {
            wp_nonce_field ('em_author_meta_box', 'em_author_meta_box_nonce');
            if (!evangelical_magazine::is_creating_post()) {
                $article_id = (int)$_GET['post'];
                $article = new evangelical_magazine_article ($article_id);
                $existing_author_ids = $article->get_author_ids();
            } else {
                $existing_author_ids = array();
            }
            echo '<ul id="em_authorchecklist" data-wp-lists="list:em_section" class="categorychecklist form-no-clear">';
            foreach ($authors as $author) {
                $checked = in_array($author->get_id(), $existing_author_ids) ? ' checked="checked"' : '';
                echo "<li><label class=\"selectit\"><input type=\"checkbox\" name=\"em_authors[]\" value=\"{$author->get_id()}\"{$checked}> {$author->get_name()}</label></li>";
            }
            echo '</ul>';
            echo '<h4><a href="#em_author_add" class="hide-if-no-js">+ Add New Author</a></h4>';
        }
    }
    
    /**
    * Outputs the issue meta box
    * 
    * @param mixed $article
    */
    public static function do_issue_meta_box($post) {
        $issues = evangelical_magazine_issue::get_all_issues();
        if ($issues) {
            wp_nonce_field ('em_issue_meta_box', 'em_issue_meta_box_nonce');
            if (!evangelical_magazine::is_creating_post()) {
                $article = new evangelical_magazine_article ($post);
                $existing_issue = $article->get_issue_id();
                $existing_page_num = $article->get_page_num();
            } else {
                $existing_issue = $existing_page_num = '';
            }
            echo 'Date: <select name="em_issue">';
            foreach ($issues as $issue) {
                $selected = ($existing_issue == $issue->get_id()) ? ' selected="selected"' : '';
                echo "<option value=\"{$issue->get_id()}\"{$selected}> {$issue->get_name()}</option>";
            }
            echo '</select><br/>';
            echo "<label>Page: <input type=\"text\" name=\"em_page_num\" size=\"2\" maxlength=\"2\" autocomplete=\"off\" value=\"{$existing_page_num}\"/></label>";
            echo '<h4><a href="#em_issue_add" class="hide-if-no-js">+ Add New Issue</a></h4>';
        }
    }
    
    /**
    * Outputs the series meta box
    * 
    * @param mixed $article
    */
    public static function do_series_meta_box($post) {
        $series = evangelical_magazine_series::get_all_series();
        if ($series) {
            wp_nonce_field ('em_series_meta_box', 'em_series_meta_box_nonce');
            if (!evangelical_magazine::is_creating_post()) {
                $article = new evangelical_magazine_article ($post);
                $existing_series = $article->get_series_id();
                $existing_order = $article->get_series_order();
                $existing_order = ($existing_order == 0) ? '' : $existing_order;
            } else {
                $existing_series = $existing_order = '';
            }
            echo 'Series: <select name="em_series">';
            $selected = ($existing_series == '') ? ' selected="selected"' : '';
            echo "<option value=\"\"{$selected}></option>";
            foreach ($series as $s) {
                $selected = ($existing_series == $s->get_id()) ? ' selected="selected"' : '';
                echo "<option value=\"{$s->get_id()}\"{$selected}> {$s->get_name()}</option>";
            }
            echo '</select><br/>';
            echo "<label>Order: <input type=\"text\" name=\"em_order\" size=\"2\" maxlength=\"2\" autocomplete=\"off\" value=\"{$existing_order}\"/></label>";
            echo '<h4><a href="#em_series_add" class="hide-if-no-js">+ Add New Series</a></h4>';
        }
    }
    
    /**
    * Outputs the author meta box
    * 
    * @param mixed $article
    */
    public static function do_issue_date_meta_box($post) {
        wp_nonce_field ('em_issue_date_meta_box', 'em_issue_date_meta_box_nonce');
        if (!evangelical_magazine::is_creating_post()) {
            $issue = new evangelical_magazine_issue ($post);
            $existing_issue_date = $issue->get_date();
        } else {
            $this_month = date('n');
            $existing_issue_date = array('year' => date('Y'), 'month' => str_pad($this_month+(($this_month+1) % 2), 2, '0', STR_PAD_LEFT));
        }
        echo '<select name="em_issue_month">';
        $possible_issues = evangelical_magazine_issue::get_possible_issue_dates();
        foreach ($possible_issues as $index => $name) {
            $selected = ($existing_issue_date['month'] == $index) ? ' selected="selected"' : '';
            echo "<option value=\"{$index}\"{$selected}> {$name}</label></li>";
        }
        echo '</select>';
        echo '<select name="em_issue_year">';
        for ($year = date('Y')+1; $year >= evangelical_magazine_issue::EARLIEST_YEAR; $year--) {
            $selected = ($existing_issue_date['year'] == $year) ? ' selected="selected"' : '';
            echo "<option value=\"{$year}\"{$selected}> {$year}</label></li>";
        }
        echo '</select>';
    }
    
    /**
    * Returns the next article to be published
    * 
    * @param array $args
    * @return evangelical_magazine_article
    */
    public static function get_next_future_article($args) {
        $default_args = array ('post_type' => 'em_article', 'orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => 1, 'post_status' => 'future');
        $articles = self::_get_articles($args, $default_args);
        if (is_array($articles)) {
            return $articles[0];
        }
    }
}