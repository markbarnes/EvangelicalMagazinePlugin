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
		add_filter ('instant_articles_authors', array (__CLASS__, 'filter_author'), 10, 2);
	}

	/**
	* Filters the author name for instant articles
	*
	* @param object $author
	* @return object
	*/
	public static function filter_author ($authors, $post_id) {
		/** @var evangelical_magazine_article */
		$object = evangelical_magazine::get_object_from_id($post_id);
		if ($object && $object->is_article() && isset($authors[0])) {
			$authors[0]->display_name = $object->get_author_names();
		}
		return $authors;
	}
}