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
        add_action ('admin_init', array (__CLASS__, 'setup_custom_post_type_columns'));
        add_action ('widgets_init', array ('evangelical_magazine_widgets', 'register_widgets'));
        add_action ('save_post', array(__CLASS__, 'save_cpt_data'));
        add_action ('admin_menu', array(__CLASS__, 'remove_admin_menus'));
        add_action ('rss2_ns', array(__CLASS__, 'add_mediarss_namespace'));
        add_action ('atom_ns', array(__CLASS__, 'add_mediarss_namespace'));
        add_action ('rss2_item', array(__CLASS__, 'add_featured_image_to_rss'));
        add_action ('atom_entry', array(__CLASS__, 'add_featured_image_to_rss'));
        
        //Add filters
        add_filter ('sanitize_title', array(__CLASS__, 'pre_sanitize_title'), 9, 3);
        add_filter ('the_author', array (__CLASS__, 'filter_author_name'));
        
        add_image_size ('article_rss', 560, 373, true);
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
        $singular_l = strtolower($singular);
        $plural_l = strtolower($plural);
        return array(   'name' => $plural,
                        'singular_name' => $singular,
                        'add_new' => 'Add New',
                        'add_new_item' => "Add New {$singular}",
                        'edit_item' => "Edit {$singular}",
                        'new_item' => "New {$singular}",
                        'view_item' => "View {$singular}",
                        'search_items' => "Search {$plural}",
                        'not_found' => "No {$plural} Found",
                        'not_found_in_trash' => "No {$plural} Found in Trash",
                        'parent_item_colon' => "Parent {$singular}:",
                        'menu_name' => $plural,
                        'featured_image' => "{$singular} image",
                        'set_featured_image' => "Set {$singular_l} image",
                        'remove_featured_image' => "Remove {$singular_l} image",
                        'use_featured_image' => "Use {$singular_l} image",
                        'archives' => "{$singular} Archive",
                        'insert_into_item' => "Insert into {$singular_l}",
                        'uploaded_to_this_item' => "Uploaded to this {$singular_l}",
                        'filter_items_list' => "Filter {$plural_l} list",
                        'items_list_navigation' => "{$plural} list navigation",
                        'items_list' => "{$plural} list");
    }

    /**
    * Registers the custom post types
    * 
    */
    static function register_custom_post_types() {
        //Sections
        $args = array ( 'label' => 'Sections',
                        'labels' => self::generate_post_label ('Sections', 'Section'),
                        'description' => 'Information about each section is stored here',
                        'public' => true,
                        'show_in_menu' => true,
                        'exclude_from_search' => true,
                        'menu_position' => 4,
                        'menu_icon' => 'dashicons-portfolio',
                        'supports' => array ('title', 'editor'),
                        'has_archive' => true,
                        'query_var' => 'sections',
                        'rewrite' => array('slug' => 'sections', 'with_front' => false));
        register_post_type ('em_section', $args);
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
                        'query_var' => 'issues',
                        'register_meta_box_cb' => array ('evangelical_magazine_issue', 'issue_meta_boxes'),
                        'rewrite' => array('slug' => 'issues', 'with_front' => false));
        register_post_type ('em_issue', $args);
        //Series
        $args = array ( 'label' => 'Series',
                        'labels' => self::generate_post_label ('Series', 'Series'),
                        'description' => 'Information about each series is stored here',
                        'public' => false,
                        'show_ui' => true,
                        'show_in_menu' => true,
                        'menu_position' => 6,
                        'menu_icon' => 'dashicons-index-card',
                        'supports' => array ('title', 'editor'),
                        'has_archive' => false);
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
        $args = array ( 'label' => 'Articles',
                        'labels' => self::generate_post_label ('Articles', 'Article'),
                        'description' => 'Information about each article is stored here',
                        'public' => true,
                        'show_in_menu' => true,
                        'menu_position' => 8,
                        'menu_icon' => 'dashicons-media-text',
                        'supports' => array ('title', 'thumbnail', 'editor'),
                        'has_archive' => true,
                        'query_var' => 'articles',
                        'register_meta_box_cb' => array ('evangelical_magazine_article', 'article_meta_boxes'),
                        'rewrite' => array('slug' => 'article', 'with_front' => false));
        register_post_type ('em_article', $args);
    }
    
    /**
    * Sets up the actions and filters required to add custom columns to the Edit Articles page
    */
    public static function setup_custom_post_type_columns() {
        add_filter ('manage_edit-em_article_columns', array ('evangelical_magazine_article', 'filter_columns'));
        add_action ('manage_em_article_posts_custom_column', array ('evangelical_magazine_article', 'output_columns'), 10, 2);
        add_filter ('manage_edit-em_article_sortable_columns', array ('evangelical_magazine_article', 'make_columns_sortable'));
        add_action ('pre_get_posts', array ('evangelical_magazine_article', 'sort_by_columns'));
        add_action ('admin_head', array (__CLASS__, 'add_styles_to_admin_head'));
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
    
    /**
    * Returns the appropriate object when all we know is the ID
    * 
    * @param int $id
    * @return mixed
    */
    public static function get_object_from_id($id) {
        $post = get_post($id);
        return self::get_object_from_post($post);
    }
    
    /**
    * Converts a WP_Post object to the correct magazine object
    * 
    * @param WP_Post $post
    * @return mixed
    */
    public static function get_object_from_post($post) {
        if (is_a($post, 'WP_Post')) {
            if (substr ($post->post_type,0,3) == 'em_') {
                $class_name = 'evangelical_magazine_'.substr($post->post_type, 3);
                return new $class_name ($post);
            }
        }
        return false;
    }
    
    /**
    * Makes sure '/' characters are replaced with '-' characters when sanitising.
    * 
    * Useful for creating slugs for magazine issues (e.g. 'September/October')
    * Filters sanitize_title.
    * 
    * @param string $title
    * @param string $raw_title
    * @param string $context
    * @return string
    */
    public static function pre_sanitize_title ($title, $raw_title = '', $context = 'display') {
        return str_replace ('/', '-', $title);
    }
    
    /**
    * Adds styling to the admin head.
    * 
    */
    public static function add_styles_to_admin_head () {
        echo '<style type="text/css">.column-fb_likes, .column-fb_shares, .column-fb_comments, .column-fb_total {width: 10%}</style>';
    }
    
    public static function add_mediarss_namespace() {
        echo "xmlns:media=\"http://search.yahoo.com/mrss/\"\r\n";
    }
    
    public static function add_featured_image_to_rss () {
        global $post;
        if ($object = self::get_object_from_post($post)) {
            $image = $object->get_image_details ('article_rss');
            if ($image) {
                echo "<media:content url=\"{$image['url']}\" type=\"{$image['mimetype']}\" height=\"{$image['height']}\" width=\"{$image['width']}\" />\r\n";
                echo "<enclosure url=\"{$image['url']}\" type=\"{$image['mimetype']}\" length=\"{$image['filesize']}/>\r\n";
            }
        }
    }
    
    public static function filter_author_name ($display_name) {
        global $post;
        if ($object = self::get_object_from_post($post)) {
            if ($object->is_article()) {
                return $object->get_author_names();
            } else {
                return 'editor';
            }
        } else {
            return $display_name;
        }
    }
}

// Initialise
$evangelical_magazine = new evangelical_magazine();