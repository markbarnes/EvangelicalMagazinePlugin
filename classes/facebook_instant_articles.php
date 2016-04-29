<?php
/**
* Adds Facebook Instant Articles functionality
* 
* Requires the "Instant Articles for WP" plugin.
* 
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/

class evangelical_magazine_facebook_instant_articles {

    /**
    * Adds all the actions and filters required for Facebook Instant Articles
    */
    public function __construct() {
        add_action ('pre_get_posts', array (__CLASS__, 'modify_query'), 11, 1);
        add_filter ('instant_articles_authors', array (__CLASS__, 'filter_author'));
    }

    /**
    * Modifies the WordPress query when the instant articles feed is being requested
    * 
    * @param WP_Query $query  The WP_Query object. Passed by reference.
    */
    public static function modify_query ($query) {
        if (defined('INSTANT_ARTICLES_SLUG') && $query->is_main_query() && $query->is_feed(INSTANT_ARTICLES_SLUG)) {
            $query->set ('post_type', 'em_article');
        }
    }
    
    public static function filter_author ($author) {
        global $post;
        /** @var evangelical_magazine_article */
        $article = evangelical_magazine::get_object_from_post($post);
        if ($article && isset($author[0])) {
            $author[0]->display_name = $article->get_author_names();
        }
        return $author;
    }

}