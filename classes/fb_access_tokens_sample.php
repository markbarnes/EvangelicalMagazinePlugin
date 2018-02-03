<?php
/**
* Provides Facebook access tokens
*
* You should add the correct access tokens, then rename this file as fb_access_tokens.php
*
* ONCE AMENDED, THIS FILE MUST BE KEPT PRIVATE
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access private
*/
class evangelical_magazine_fb_access_tokens {

	/**
	* Returns the Facebook app_id
	*
	* @return string
	*/
	public static function get_app_id() {
		return '0123456789012345';
	}

	/**
	* Returns the Facebook app_secret
	*
	* @return string
	*/
	public static function get_app_secret() {
		return '0123456789abcdef0123456789abcdef';
	}
}