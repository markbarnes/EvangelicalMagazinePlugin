<?php
/**
* Adds integration with Google Analytics, for retrieving page view stats
*
* @package evangelical-magazine-plugin
* @author Mark Barnes
* @access public
*/
class evangelical_magazine_google_analytics {

	/**
	* @var Google_Client $client
	* @var int $profile_id - the unique Analytics view (profile) ID
	* @var int $access_token - the temporary secret access token required to make the request
	* @var GuzzleHttp\ClientInterface $http - the http client object used for interacting with the Google API
	*/
	private $client, $profile_id, $access_token, $http;

	/**
	* Instantiate the class by setting up the http client object
	*
	* @return void
	*/
	public function __construct() {
		$this->client = new Google_Client();
		$this->client->useApplicationDefaultCredentials();
		$this->client->addScope('https://www.googleapis.com/auth/analytics.readonly');
		$credentials_file = evangelical_magazine::plugin_dir_path('google-api-credentials.json');
		putenv('GOOGLE_APPLICATION_CREDENTIALS='.$credentials_file);
		$this->http = $this->client->authorize();
	}

	/**
	* Returns the Google Analytics access token
	*
	* @param boolean $force_renewal - if true, will override the cached value
	* @return string
	*/
	private function get_access_token($force_renewal = false) {
		if (!$force_renewal) {
			$this->access_token = get_transient('em_google_access_token');
		}
		if (!$this->access_token || $force_renewal) {
			$this->client->fetchAccessTokenWithAssertion();
			$access_token = $this->client->getAccessToken();
			set_transient('em_google_access_token', $access_token['access_token'], (int)($access_token['expires_in']*0.9));
			$this->access_token = $access_token['access_token'];
		}
		return $this->access_token;
	}

	/**
	* Returns the Google Analytics profile id
	*
	* @return string
	*/
	private function get_profile_id() {
		if (!$this->profile_id) {
			$this->profile_id = get_transient ('em_google_profile_id');
			if (!$this->profile_id) {
				$url = 'https://www.googleapis.com/analytics/v3/management/accountSummaries';
				$response = json_decode($this->http->get($url)->getBody()->getContents());
				if ($response && isset($response->items) && isset($response->items[0]->webProperties[0]->profiles[0]->id)) {
					$this->profile_id = $response->items[0]->webProperties[0]->profiles[0]->id;
					set_transient ('em_google_profile_id', $this->profile_id, WEEK_IN_SECONDS);
				}
			}
		}
		return $this->profile_id;
	}

	/**
	* Gets the number of Google Analytics page views for a single url or array of urls
	*
	* @param string|string[] $urls - a single url or array of urls
	* @return int|array - returns an integer if $urls was a string, and an arrays if $urls was an array. The array key is set to the path, and the value to the number of views.
	*/
	public function get_page_views($urls) {
		if (!is_array($urls)) {
			$urls = (array)$urls;
			$return_single_value = true;
		} else {
			$return_single_value = false;
		}
		$access_token = $this->get_access_token();
		$profile_id = $this->get_profile_id();
		$filters = array();
		foreach ($urls as $url) {
			$filters[] = urlencode('ga:pagePath=='.wp_parse_url($url, PHP_URL_PATH));
		}
		$filter = implode ($filters, ',');
		$url = "https://www.googleapis.com/analytics/v3/data/ga?ids=ga%3A{$profile_id}&start-date=2016-01-01&end-date=today&metrics=ga%3Apageviews&dimensions=ga%3ApagePath&filters={$filter}&access_token=".$access_token;
		$response = json_decode($this->http->get($url)->getBody()->getContents());
		if (isset($response->rows)) {
			if ($return_single_value) {
				return $response->rows[0][1];
			} else {
				$values = array();
				foreach ($response->rows as $row) {
					$values[$row[0]] = $row[1];
				}
				return $values;
			}
		}
	}
}