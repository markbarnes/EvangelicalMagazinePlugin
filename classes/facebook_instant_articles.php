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
		add_filter ('instant_articles_authors', array (__CLASS__, 'replace_author_names'), 10, 2);
		add_filter ('instant_articles_content', array (__CLASS__, 'add_author_descriptions_to_content'), 10, 2);
	}

	/**
	* Filters the author name for instant articles
	*
	* @param object $author
	* @return object
	*/
	public static function replace_author_names ($authors, $post_id) {
		/** @var evangelical_magazine_article */
		$object = evangelical_magazine::get_object_from_id($post_id);
		if ($object && $object->is_article() && isset($authors[0])) {
			$authors[0]->display_name = $object->get_author_names();
		}
		return $authors;
	}

	public static function add_author_descriptions_to_content ($content, $post_id) {
		/** @var evangelical_magazine_article */
		$object = evangelical_magazine::get_object_from_id($post_id);
		if ($object && $object->is_article()) {
			$authors = $object->get_authors();
			if ($authors) {
				$is_single_author = (count($authors) == 1);
				$content .= "<h2>About the author".($is_single_author ? '' : 's')."</h2>\r\n";
				foreach ($authors as $author) {
					$content .= '<p><i>'.wp_strip_all_tags($author->get_description(), true)."</i></p>\r\n";
				}
			}
		}
		return $content;

	}
}