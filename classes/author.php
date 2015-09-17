<?php
/**
* Accepts either a WP_Post object, or a post_id
*/
class evangelical_magazine_author extends evangelical_magazine_not_articles {
    
    /**
    * Returns the post content of an author post
    * 
    * @param boolean $link_name
    * @return string
    */
    public function get_description($link_name = true) {
        if ($link_name) {
            return str_replace($this->get_name(), $this->get_name(true), $this->post_data->post_content);
        } else {
            return $this->post_data->post_content;
        }
    }
    
    /**
    * Returns the HTML of a thumbnail and name of the author
    * 
    * @return string
    */
    public function get_author_info_html() {
        return "<div class=\"author-info\">".$this->get_link_html("<img class=\"author-image\" src=\"{$this->get_image_url('thumbnail_75')}\"/>")."<div class=\"author-description\">{$this->get_description()}</div></div>";
    }

    /**
    * Returns all articles by this author
    * 
    * @param integer $limit
    * @param integer[] $exclude_article_ids
    * @return evangelical_magazine_article[]
    */
    public function _get_articles ($args = array()) {
        $meta_query = array(array('key' => evangelical_magazine_article::AUTHOR_META_NAME, 'value' => $this->get_id()));
        $default_args = array ('post_type' => 'em_article', 'posts_per_page' => -1, 'meta_query' => $meta_query);
        return self::_get_articles_from_query($args, $default_args);
    }

    /**
    * Returns an array of all the author objects
    * 
    * @param array $args
    * @return evangelical_magazine_author[]
    */
    public static function get_all_authors($args = array()) {
        $default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
        return self::_get_authors_from_query($args, $default_args);
    }
    
    /**
    * Returns an array of all the author ids
    * 
    * @param array $args
    * @return integer[]
    */
    public static function get_all_author_ids($args = array()) {
        $default_args = array ('post_type' => 'em_author', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => -1);
        return self::_get_object_ids_from_query($args, $default_args);
    }
    
    /**
    * Returns an array of authors, with those writing most recently and most frequently ordered first
    * 
    * @param array $args
    * @param integer $issues_to_consider
    * @return evangelical_magazine_author[]
    */
    public static function get_all_authors_weighted_by_recent ($max_authors = -1, $args = array(), $issues_to_consider = 18) {
        $authors = self::get_all_author_ids($args);
        if ($authors) {
            $authors = array_flip ($authors);
            $authors = array_fill_keys (array_keys($authors), 0); // Now we have an array with all the author ids as keys, and the value 0
            $issues = evangelical_magazine_issue::get_all_issues($issues_to_consider);
            if ($issues) {
                $issue_weighting = $issues_to_consider;
                foreach ($issues as $issue) {
                    $issue_authors = $issue->get_author_ids();
                    if ($issue_authors) {
                        foreach ($issue_authors as $issue_author) {
                            $authors[$issue_author] = $authors[$issue_author] + (pow($issue_weighting,2)/pow($issues_to_consider,2));
                        }
                    }
                    $issue_weighting--;
                }
                arsort($authors);
                $authors = ($max_authors == -1) ? $authors : array_slice($authors, 0, $max_authors, true);
                $all_authors = array();
                foreach ($authors as $author => $weighting) {
                    $all_authors[] = new evangelical_magazine_author($author);
                }
                return $all_authors;
            }
        }
    }
}