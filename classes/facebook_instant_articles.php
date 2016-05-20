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
        add_filter ('instant_articles_content', array (__CLASS__, 'remove_onpage_hyperlinks'));
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
    
    /**
    * Filters the author name for instant articles
    * 
    * @param object $author
    * @return object
    */
    public static function filter_author ($author) {
        global $post;
        /** @var evangelical_magazine_article */
        $object = evangelical_magazine::get_object_from_post($post);
        if ($object && $object->is_article() && isset($author[0])) {
            $author[0]->display_name = $object->get_author_names();
        }
        return $author;
    }

    /**
    * Filters the content for instant articles to remove onpage hyperlinks
    * 
    * (.e.g. hyperlinks that begin with a #)
    * 
    * @param string $content
    * @return string
    */
    public static function remove_onpage_hyperlinks ($content) {
        preg_match_all ('/<a href=\\"([^\\"]*)\\">(.*)<\\/a>/iU', $content, $matches);
        foreach ($matches[0] as $link) {
            preg_match_all ('/(?<=href=\").+(?=\")/', $link, $matches2);
            $href = isset ($matches2[0][0]) ? $matches2[0][0] : false;
            if (0 === strpos ($href, '#')) {
                $content = str_replace ($link, strip_tags($link), $content);
            }
        }
        return $content;
    }
}