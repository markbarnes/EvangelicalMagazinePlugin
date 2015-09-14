<?php
/**
Plugin Name: Evangelical Magazine
Description: Customisations for the Evangelical Magazine
Plugin URI: http://www.evangelicalmagazine.com/
Version: 0.1
Author: Mark Barnes
Author URI: http://www.markbarnes.net/
*/

/**
* This class contains all the main functions for the plugin, mostly as static methods.
* We're using a class mostly to avoid name clashes.
*/
class evangelical_magazine {
    
    /**
    * Main hooks and activation
    * 
    */
    public function __construct() {
        //Make sure classes autoload
        spl_autoload_register(array(__CLASS__, 'autoload_classes'));
        
        // Register activation hook
        register_activation_hook (__FILE__, array(__CLASS__, 'do_on_activation'));

        // Add main actions
        add_action ('evangelicalmagazine_activate', array(__CLASS__, 'flush_rewrite_rules'));
        add_action ('init', array (__CLASS__, 'register_custom_post_types'));
        add_action ('init', array (__CLASS__, 'register_custom_taxonomies'));
        add_action ('save_post', array(__CLASS__, 'save_cpt_data'));
        add_action ('admin_menu', array(__CLASS__, 'remove_admin_menus'));
    }

    /**
    * Runs when plugin is activated. Setup in this way so it can be extended through actions.
    * 
    */
    public static function on_activation() {
        do_action ('evangelical_magazine_activate');
    }

    /**
    * Autoloads classes
    * 
    * @param string $class_name
    */
    public static function autoload_classes ($class_name) {
        $prefix  = 'evangelical_magazine_';
        if (strpos($class_name, $prefix) === 0) {
            require (plugin_dir_path(__FILE__).'classes/'.substr($class_name, strlen($prefix))).'.php';
        }
    }
    /**
    * Returns the URL of this plugin
    * 
    * @param string $path
    * @return string
    */
    function plugin_url($path) {
        return plugins_url($path, __FILE__);
    }

    /** 
    * Returns the path to the plugin, or to a specified file or folder within it
    * 
    * @param string $relative_path - a file or folder within the plugin
    * @return string
    */
    function plugin_dir_path ($relative_path = '') {
        return plugin_dir_path(__FILE__).$relative_path;
    }

    /**
    * Flushes the rewrite rules
    */
    function flush_rewrite_rules () {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();
    }

    /**
    * Generates an array of labels for custom post types
    *
    * @param string $plural - the plural form of the word used in the labels
    * @param string $singular - the singular form of the word used in the labels
    * @return array - the label array
    */
    static function generate_post_label ($plural, $singular) {
        return array('name' => $plural, 'singular_name' => $singular, 'add_new' => 'Add New', 'add_new_item' => "Add New {$singular}", 'edit_item' => "Edit {$singular}", 'new_item' => "New {$singular}", 'view_item' => "View {$singular}", 'search_items' => "Search {$plural}", 'not_found' => "No {$plural} Found", 'not_found_in_trash' => "No {$plural} Found in Trash", 'parent_item_colon' => "Parent {$singular}:", 'menu_name' => $plural);
    }

    /**
    * Generates an array of labels for custom taxonomies
    *
    * @param string $plural - the plural form of the word used in the labels
    * @param string $singular - the singular form of the word used in the labels
    * @return array - the label array
    */
    static function generate_taxonomy_label ($plural, $singular) {
        return array('name' => $plural, 'singular_name' => $singular, 'search_items' => "Search {$plural}", 'popular_items' => "Popular {$plural}", 'all_items' => "All {$plural}", 'parent_item' => "Parent {$singular}", 'edit_item' => "Edit {$singular}", 'update_item' => "Update {$singular}", 'add_new_item' => "Add New {$singular}", 'new_item_name' => "New {$singular}", 'separate_items_with_commas' => "Separate {$plural} with a comma", 'add_or_remove_items' => "Add or remove {$plural}", 'choose_from_most_used' => "Choose from the most used {$plural}");
    }

    /**
    * Registers the custom post types
    * 
    */
    static function register_custom_post_types() {
        //Issues
        $args = array ( 'label' => 'Issues',
                        'labels' => self::generate_post_label ('Issues', 'Issue'),
                        'description' => 'Information about each issue is stored here',
                        'public' => true,
                        'show_in_menu' => true,
                        'menu_position' => 5,
                        'menu_icon' => 'dashicons-id-alt',
                        'supports' => array ('title', 'thumbnail', 'editor'),
                        'has_archive' => true,
                        'query_var' => 'issue',
                        'register_meta_box_cb' => array (get_called_class(), 'issue_meta_boxes'),
                        'rewrite' => array('slug' => 'issue', 'with_front' => false));
        register_post_type ('em_issue', $args);
        //Series
        $args = array ( 'label' => 'Series',
                        'labels' => self::generate_post_label ('Series', 'Series'),
                        'description' => 'Information about each series is stored here',
                        'public' => true,
                        'show_in_menu' => true,
                        'menu_position' => 6,
                        'menu_icon' => 'dashicons-index-card',
                        'supports' => array ('title', 'editor'),
                        'has_archive' => true,
                        'query_var' => 'series',
                        'rewrite' => array('slug' => 'series', 'with_front' => false));
        register_post_type ('em_series', $args);
        //Authors
        $args = array ( 'label' => 'Authors',
                        'labels' => self::generate_post_label ('Authors', 'Author'),
                        'description' => 'Information about each author is stored here',
                        'public' => true,
                        'show_in_menu' => true,
                        'menu_position' => 7,
                        'menu_icon' => 'dashicons-admin-users',
                        'supports' => array ('title', 'thumbnail', 'editor'),
                        'has_archive' => true,
                        'query_var' => 'authors',
                        'rewrite' => array('slug' => 'authors', 'with_front' => false));
        register_post_type ('em_author', $args);
        //Articles
        $args = array ( 'label' => __('Articles', 'evangelical-magazine'),
                        'labels' => self::generate_post_label (__('Articles', 'evangelical-magazine'), __('Article', 'evangelical-magazine')),
                        'description' => __('Information about each article is stored here', 'evangelical-magazine'),
                        'public' => true,
                        'show_in_menu' => true,
                        'menu_position' => 8,
                        'menu_icon' => 'dashicons-media-text',
                        'supports' => array ('title', 'thumbnail', 'editor'),
                        'taxonomies' => array ('em_section'),
                        'has_archive' => true,
                        'query_var' => 'article',
                        'register_meta_box_cb' => array (get_called_class(), 'article_meta_boxes'),
                        'rewrite' => array('slug' => 'article', 'with_front' => false));
        register_post_type ('em_article', $args);
    }
    
    /**
    * Registers the custom taxonomies
    * 
    */
    public static function register_custom_taxonomies () {
        // Sections
        $args = array ( 'label' => __('Sections', 'evangelical-magazine'),
                        'labels' => self::generate_taxonomy_label(__('Sections', 'evangelical-magazine'), __('Section', 'evangelical-magazine')),
                        'hierarchical' => true,
                        'rewrite' => array ('slug' => '/section')
                      );
        register_taxonomy(evangelical_magazine_article::SECTION_TAXONOMY_NAME, 'em_article', $args);
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
        $authors = self::get_all_authors();
        if ($authors) {
            wp_nonce_field ('em_author_meta_box', 'em_author_meta_box_nonce');
            if (!self::is_creating_post()) {
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
            if (!self::is_creating_post()) {
                $article = new evangelical_magazine_article ($post);
                $existing_issue = $article->get_issue_id();
                $existing_page_num = $article->get_issue_page_num();
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
        $series = self::get_all_series();
        if ($series) {
            wp_nonce_field ('em_series_meta_box', 'em_series_meta_box_nonce');
            if (!self::is_creating_post()) {
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
        if (!self::is_creating_post()) {
            $issue = new evangelical_magazine_issue ($post);
            $existing_issue_date = $issue->get_date();
        } else {
            $this_month = date('n');
            $existing_issue_date = array('year' => date('Y'), 'month' => str_pad($this_month+(($this_month+1) % 2), 2, '0', STR_PAD_LEFT));
        }
        echo '<select name="em_issue_month">';
        $possible_issues = evangelical_magazine_issue::get_possible_issues();
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

    /**
    * Returns an array of all the series objects
    * 
    * @param string $order_by
    * @return evangelical_magazine_series[]
    */
    private static function get_all_series() {
        $args = array ('post_type' => 'em_series', 'orderby' => 'post_title', 'order' => 'ASC');
        $query = new WP_Query($args);
        if ($query->posts) {
            $series = array();
            foreach ($query->posts as $s) {
                $series[] = new evangelical_magazine_series ($s);
            }
            return $series;
        }
    }

    /**
    * Makes sure all the additional metabox data is saved.
    * 
    * @param integer $post_id
    */
    public static function save_cpt_data ($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['post_type']) && $_POST['post_type'] == 'em_article') {
            $article = new evangelical_magazine_article ($post_id);
            $article->save_meta_data();
        } elseif (isset($_POST['post_type']) && $_POST['post_type'] == 'em_issue') {
            $issue = new evangelical_magazine_issue ($post_id);
            $issue->save_meta_data();
        }
    }
    
    /**
    * Returns true if we're creating a post in admin
    * 
    * @return boolean
    */
    public static function is_creating_post() {
        $screen = get_current_screen();
        if ($screen->action == 'add' && $screen->base == 'post') {
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * Removes unwanted admin menus
    * 
    */
    public static function remove_admin_menus() {
        remove_menu_page ('edit.php');
        remove_menu_page ('edit-comments.php');
    }
}

// Initialise
$evangelical_magazine = new evangelical_magazine();