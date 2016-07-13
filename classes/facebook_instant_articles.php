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
        add_filter ('instant_articles_post_types', create_function('', 'return array ("em_article");'));
        add_filter ('instant_articles_authors', array (__CLASS__, 'filter_author'));
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
}