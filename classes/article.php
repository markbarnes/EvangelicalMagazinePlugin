<?php
/**
* The main class for handling the article custom post type
* 
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_article extends evangelical_magazine_template {
    /**
    * @var evangelical_magazine_issue
    */
    private $issue;
    
    /**
    * @var evangelical_magazine_author[]
    */
    private $authors;
    
    
    /**
    * @var evangelical_magazine_section[]
    */
    private $sections;

    /**
    * @var int
    */
    private $page_num, $order;
    
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
            return $this->get_link_html($this->post_data->post_title);
        } else {
            return $this->post_data->post_title;
        }
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
    * Returns the permalink of the issue
    * 
    * @return string
    */
    public function get_issue_link() {
        if ($this->has_issue()) {
            return $this->issue->get_link();
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
    public function get_series_name() {
        if ($this->has_series()) {
            return $this->series->get_name();
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
    * Returns an array of section post IDs for this article
    * 
    * @return integer[]
    */
    public function get_section_ids() {
        $section = get_post_meta ($this->get_id(), self::SECTION_META_NAME);
        return (array)$section;
    }
    
    /**
    * Returns the name the first section
    * 
    * @return string
    */
    public function get_section_name() {
        if ($this->has_sections()) {
            $sections = $this->get_sections();
            return key($sections);
        }
    }
    
    /**
    * Helper function to generate author and section data
    * 
    * @param int[] $ids
    * @param mixed $object_class
    */
    private function _generate_objects_array($ids, $object_class) {
        if ($ids) {
            $objects = array();
            $object_class = "evangelical_magazine_{$object_class}";
            foreach ($ids as $id) {
                $object = new $object_class ($id);
                $objects[$object->get_name()] = $object;
            }
            return $objects;
        } else {
            return null;
        }
    }
    
    /**
    * Populates $this->sections
    */
    private function generate_sections_array() {
        $this->sections = $this->_generate_objects_array ($this->get_section_ids(), 'section');
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
        $this->authors = $this->_generate_objects_array ($this->get_author_ids(), 'author');
    }
    
    /**
    * Returns a list of author names
    * 
    * @param bool $link - Make the names into links
    * @param bool $schema - Add schema.org markup
    * @return string
    */
    public function get_author_names($link = false, $schema = false) {
        if (is_array($this->authors)) {
            $output = array();
            foreach ($this->authors as $author) {
                if ($schema) {
                    $attributes = array ('itemprop' => 'author', 'itemtype' => 'http://schema.org/Person', 'itemscope' => true);
                    $this_author = '<span '.$this->attr_html($attributes).'>';
                    $attributes = array ('itemprop' => 'name');
                    $this_author .= $author->get_name($link, $schema);
                    $this_author .= '</span>';
                } else {
                    $this_author = $author->get_name($link);
                }
                $output[] = $this_author;
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
    * Returns the date of the issue as a Unix timestamp
    * 
    */
    public function get_issue_datetime() {
        if ($this->has_issue()) {
            $date = $this->issue->get_date();
            return strtotime("{$date['year']}-{$date['month']}-01");
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
        if (defined ('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (!current_user_can('edit_posts'))
            return;
        // Authors
        if (isset ($_POST['em_author_meta_box_nonce']) && wp_verify_nonce ($_POST['em_author_meta_box_nonce'], 'em_author_meta_box')) {
            delete_post_meta ($this->get_id(), self::AUTHOR_META_NAME);
            if (isset($_POST['em_authors'])) {
                if (is_array($_POST['em_authors'])) {
                    foreach ($_POST['em_authors'] as $author) {
                        add_post_meta ($this->get_id(), self::AUTHOR_META_NAME, $author);
                    }
                }
            }
            $this->generate_authors_array();
        }
        // Sections
        if (isset ($_POST['em_section_meta_box_nonce']) && wp_verify_nonce ($_POST['em_section_meta_box_nonce'], 'em_section_meta_box')) {
            delete_post_meta ($this->get_id(), self::SECTION_META_NAME);
            if (isset($_POST['em_sections'])) {
                if (is_array($_POST['em_sections'])) {
                    foreach ($_POST['em_sections'] as $section) {
                        add_post_meta ($this->get_id(), self::SECTION_META_NAME, $section);
                    }
                }
            }
            $this->generate_sections_array();
        }
        if (isset($_POST['em_issue_meta_box_nonce']) && wp_verify_nonce ($_POST['em_issue_meta_box_nonce'], 'em_issue_meta_box')) {
            $article_sort_order = '';
            // Issue
            if (isset($_POST['em_issue'])) {
                update_post_meta ($this->get_id(), self::ISSUE_META_NAME, $_POST['em_issue']);
                $this->issue = new evangelical_magazine_issue($_POST['em_issue']);
                $article_sort_order = $this->issue->get_date();
                if ($article_sort_order) {
                    $article_sort_order = "{$article_sort_order['year']}-{$article_sort_order['month']}";
                }
            } else {
                delete_post_meta ($this->get_id(), self::ISSUE_META_NAME);
                $this->issue = null;
            }
            // Page number
            if (isset($_POST['em_page_num'])) {
                $this->page_num = (int)$_POST['em_page_num'];
                update_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME, $this->page_num);
                $article_sort_order .= $this->page_num ? '-'.str_pad($this->page_num, 2, '0', STR_PAD_LEFT) : '';
            } else {
                delete_post_meta ($this->get_id(), self::PAGE_NUM_META_NAME);
                $this->page_num = null;
            }
            // Sort order
            if ($article_sort_order) {
                update_post_meta ($this->get_id(), self::ARTICLE_SORT_ORDER_META_NAME, $article_sort_order);
            } else {
                delete_post_meta ($this->get_id(), self::ARTICLE_SORT_ORDER_META_NAME);
            }
        }
        // Series
        if (isset($_POST['em_series_meta_box_nonce']) && wp_verify_nonce ($_POST['em_series_meta_box_nonce'], 'em_series_meta_box')) {
            if (isset($_POST['em_series'])) {
                update_post_meta ($this->get_id(), self::SERIES_META_NAME, $_POST['em_series']);
                $this->series = new evangelical_magazine_series($_POST['em_series']);
            } else {
                delete_post_meta ($this->get_id(), self::SERIES_META_NAME);
                $this->series = null;
            }
            // Series order
            if (isset($_POST['em_order'])) {
                $this->order = (int)$_POST['em_order'];
                update_post_meta ($this->get_id(), self::ORDER_META_NAME, $this->order);
            } else {
                delete_post_meta ($this->get_id(), self::ORDER_META_NAME);
                $this->series = null;
            }
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
            $args = array ('post_type' => 'em_article', 'posts_per_page' => $limit, 'meta_query' => $meta_query, 'meta_key' => self::ARTICLE_SORT_ORDER_META_NAME, 'orderby' => 'meta_value');
            if ($exclude_this_article) {
                $args ['post__not_in'] = array($this->get_id());
            }
            return self::_get_articles_from_query($args);
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
            $src = $this->get_image_url($image_size = 'article_large');
            $style = "style=\"background-image: url('{$src}'); background-position: center center; background-size: cover\"";
        } else {
            $style = '';
        }
        $class = trim("small-article-box {$class}");
        $class .= $this->is_future() ? ' future' : '';
        $sub_title = $sub_title ? "<span class=\"sub-title\">{$sub_title}</span>" : '';
        if ($add_links && !$this->is_future()) {
            return "<aside class=\"{$class}\">{$sub_title}".$this->get_link_html("<div class=\"article-image\"{$style}></div>")."<div class=\"article-title\">{$this->get_title(true)}</div></aside>";
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
        add_meta_box ('em_sections', 'Section(s)', array(get_called_class(), 'do_section_meta_box'), 'em_article', 'side', 'core');
        add_meta_box ('em_authors', 'Author(s)', array(get_called_class(), 'do_author_meta_box'), 'em_article', 'side', 'core');
        add_meta_box ('em_series', 'Series', array(get_called_class(), 'do_series_meta_box'), 'em_article', 'side', 'core');
    }
    
    /**
    * Helper function to return a meta box where the user can choose multiple items of another post type
    * 
    * @param array $objects
    * @param string $name
    * @return string
    */
    public static function _get_checkbox_meta_box($objects, $name) {
        if ($objects) {
            wp_nonce_field ("em_{$name}_meta_box", "em_{$name}_meta_box_nonce");
            if (!evangelical_magazine::is_creating_post()) {
                $article_id = (int)$_GET['post'];
                $article = new evangelical_magazine_article ($article_id);
                $method_name = "get_{$name}_ids";
                $existing_object_ids = $article->$method_name();
            } else {
                $existing_object_ids = array();
            }
            $output = "<ul id=\"em_{$name}checklist\" style=\"max-height: 275px; overflow-y: auto\" data-wp-lists=\"list:em_{$name}\" class=\"categorychecklist form-no-clear\">";
            foreach ($objects as $object) {
                $checked = in_array($object->get_id(), $existing_object_ids) ? ' checked="checked"' : '';
                $output .= "<li><label class=\"selectit\"><input type=\"checkbox\" name=\"em_{$name}s[]\" value=\"{$object->get_id()}\"{$checked}> {$object->get_name()}</label></li>";
            }
            $output .= '</ul>';
            $output .= "<h4><a href=\"#em_{$name}_add\" class=\"hide-if-no-js\">+ Add new {$name}</a></h4>";
            return $output;
        }
    }
    
    /**
    * Outputs the author meta box
    * 
    * @param mixed $article
    */
    public static function do_author_meta_box($article) {
        $authors = evangelical_magazine_author::get_all_authors();
        echo self::_get_checkbox_meta_box ($authors, 'author');
    }
    
    /**
    * Outputs the section meta box
    * 
    * @param mixed $article
    */
    public static function do_section_meta_box($article) {
        $sections = evangelical_magazine_section::get_all_sections();
        echo self::_get_checkbox_meta_box ($sections, 'section');
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
    * Returns the next article to be published
    * 
    * @param array $args
    * @return evangelical_magazine_article
    */
    public static function get_next_future_article($args = array()) {
        $default_args = array ('post_type' => 'em_article', 'orderby' => 'date', 'order' => 'ASC', 'posts_per_page' => 1, 'post_status' => 'future');
        $articles = self::_get_articles_from_query($args, $default_args);
        if (is_array($articles)) {
            return $articles[0];
        }
    }
    
    /**
    * Returns the most popular articles
    * 
    * @param integer $limit - the maximum number of articles to return
    * @param array $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public static function get_top_articles ($limit = -1, $exclude_article_ids = array()) {
        global $wpdb;
        // For performance reasons, we can't do this through WP_Query
        $meta_key = self::VIEW_COUNT_META_NAME;
        $limit = ($limit == -1) ? '' : " LIMIT 0, {$limit}";
        $not_in = ($exclude_article_ids) ? " AND post_id NOT IN(".implode(', ', $exclude_article_ids).')' : '';
        $article_ids = $wpdb->get_col ("SELECT post_id, (meta_value/DATEDIFF(NOW(), post_date)) AS views_per_day FROM {$wpdb->postmeta}, {$wpdb->posts} WHERE ID=post_id AND meta_key='{$meta_key}' AND post_status='publish' AND post_type = 'em_article'{$not_in} ORDER BY views_per_day DESC{$limit}", 0);
        if ($article_ids) {
            $articles = array();
            foreach ($article_ids as $id) {
                $articles[] = new evangelical_magazine_article($id);
            }
            return $articles;
        }
    }
    
    /**
    * Adds columns to the Articles admin page
    * 
    * Filters manage_edit-em_article_columns
    * 
    * @param mixed $columns
    */
    public static function filter_columns ($columns) {
        $columns ['fb_engagement'] = 'Facebook Engagement';
        return $columns;
    }
    
    /**
    * Outputs the additional columns on the Articles admin page
    * 
    * Filters manage_em_article_posts_custom_column
    * 
    * @param string $column
    * @param int $post_id
    */
    public static function output_columns ($column, $post_id) {
        global $post;
        $article = new evangelical_magazine_article($post);
        if ($article->is_published()) {
            $fb_stats = $article->get_facebook_stats();
            if (is_array ($fb_stats) && in_array ($column, array ('fb_engagement'))) {
                echo $fb_stats[substr($column, 3)];
            }
        }
    }
    
    /**
    * Sets the custom columns to be sortable
    * 
    * Filters manage_edit-em_article_sortable_columns
    * 
    * @param array $columns
    * @return array
    */
    public static function make_columns_sortable ($columns) {
        $columns ['fb_engagement'] = 'fb_engagement';
        return $columns;
    }
    
    /**
    * Modifies the query to sort by columns, if requested
    * 
    * Runs on the pre_get_posts action 
    * 
    * @param WP_Query $query
    */
    public static function sort_by_columns ($query) {
        if  (is_admin()) {
            $screen = get_current_screen();
            if ($screen->id == 'edit-em_article') {
                $columns = array ( 'fb_engagement' => self::FB_ENGAGEMENT_META_NAME);
                $orderby = $query->get('orderby');
                if ($orderby && isset($columns[$orderby])) {
                    $query->set ('meta_key', $columns[$orderby]);
                    $query->set ('orderby','meta_value_num');
                }
            }
        }
    }
}