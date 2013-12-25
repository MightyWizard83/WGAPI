<?php
/*
 * WG API Communication class
 * @Author Tim Roden <tim@timroden.ca>
 * 
 * Simple class to communicate with the Wargaming API
 * Various methods to get various data
 *
 */
 
class WGAPI {	
	private $apikey = NULL;
	private $language = "en";
	private $server = "na";
	private $tld = "com";
	private $method = "GET";
	private $use_https = false;
	
	private $api_format = "api.worldoftanks.%s/wot/%s/%s/";
	
	/* 
	 * Construct a new instance of the class
	 * @param String $apikey - Your application's API Key
	 * @param String $server - The server to make HTTP Requests to 
	 * 		Valid server strings: NA, RU, EU, SEA | ASIA
	 */
	function __construct($apikey, $server) {
		if(!function_exists('curl_version'))
			throw new Exception('WGAPI Class needs the cURL library to function. Cannot continue');
		
		if($apikey == NULL || !is_string($apikey)) 
			throw new InvalidArgumentException('apikey parameter may not be null, and must be a String');
		
		if($server == NULL) 
			throw new InvalidArgumentException('server parameter may not be null');
		
		$server = strtolower($server);
		
		$this->server = $server;
		
		if($server == "na") {
			$this->tld = "com";
		} elseif($server == "ru") {			
			$this->tld = "ru";
		} elseif($server == "eu") {
			$this->tld = "eu";
		} elseif($server == "sea" || $server == "asia") {
			$this->tld = "sea";
		} else {
			$this->server = "na";
			throw new InvalidArgumentException('invalid server specified');	
		}	
		
		$this->apikey = $apikey;
	}	
	
	/*
	 * Set the desired response language
	 * @param String $language - The language to use 	 
	 */
	function setLang($language) {
		if($language == NULL)
			throw new InvalidArgumentException('language parameter may not be null');
		
		$this->language = $language;
	}
	
	/* 
	 * Set the desired method for querying the API
	 * @param String $method - The method to use. Must be POST or GET
	 */
	function setMethod($method) {
		if(($method != "GET" && $method != "POST") || $method == NULL)
			throw new InvalidArgumentException('invalid method specified - must be POST or GET');
			
		$this->method = $method;		
	}
	
	/* Account Functions */
		
	/* Clan Functions */
	
	
	/*
	 * Get a partial list of clans filtered by name or tag
	 * @see https://na.wargaming.net/developers/api_reference/wot/clan/list/ (or your applicable developer docs) for more information
	 *
	 * @param String $search - Initial characters of the clan name or tag used for search
	 * @param int $limit - Number of returned entries. Max value: 100 if value != int || value > 100, 100 is used
	 * @param String $order_by - Sorting. See developer docs for valid values
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 * @return jsonString - The data returned from the Wargaming API	 
	 */
	function clanList($search, $limit = 100, $order_by = "", $fields = array()) {
		if($limit > 100 || !is_int($limit)) 
			$limit = 100;
		
		if($search == NULL) 
			throw new InvalidArgumentException('search parameter may not be null');
				
		$request_data = array('search' => $search);
		
		if($limit != 100)
			$request_data['limit'] = $limit;
			
		if($order_by != "")
			$request_data['order_by'] = $order_by;
			
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		return $this->doRequest(sprintf($this->api_format, $this->tld, "clan", "list"), $request_data);
	}
	
	/*
	 * Get details of a clan
	 * @see https://na.wargaming.net/developers/api_reference/wot/clan/info/
	 *
	 * @param mixed $clan_id - A single clan or a list of clans
	 * @param String $access_token - Access token obtained from authentication. See developer docs for more information	
	 * @param Array $fields - List of response fields. See developer docs for more information	
	 */
	function clanInfo($clan_id, $access_token = "", $fields = array()) {
		if($clan_id == NULL) 
			throw new InvalidArgumentException('clan_id parameter may not be null');
			
		$request_data = array('clan_id' => $clan_id);
		
		if($acess_token != "")
			$request_data['access_token'] = $access_token;
		
		if(count($fields) > 0) 
			$request_data['fields'] = $fields;
			
		return $this->doRequest(sprintf($this->api_format, $this->tld, "clan", "info"), $request_data);	
	}
	
	/* Encyclopedia Functions */
	
	/* Players rating functions */	
	
	/* Web request related functions */
	private function doRequest($url, $data, $force_https = false) {
		$this->use_https || $force_https ? $prefix = "https://" : $prefix = "http://";
		
		$data['application_id'] = $this->apikey; //Add our API key to the request
		$data['language'] = $this->language; //Add our language to the request
		
		if(isset($data['fields'])) {
			$data['fields'] = implode(",", $data['fields']); //Format our fields so that the API actually understands them
		}
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); //Follow redirects, if any
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, "WGAPI v1.0 for PHP5");	
		curl_setopt($curl, CURLOPT_HEADER, false); //We don't need the header
		curl_setopt($curl, CURLOPT_ENCODING, ""); // Accept any encoding
		
		$parameters = http_build_query($data);
		
		if($this->method == "GET") {		
			curl_setopt($curl, CURLOPT_URL, "{$prefix}{$url}?{$parameters}"); //Tack params onto the end of the URL, as per GET
		} elseif($this->method == "POST") {
			curl_setopt($curl, CURLOPT_URL, "{$prefix}{$url}");
			curl_setopt($curl, CURLOPT_POST, true); //We're POSTing the data
			curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters); //Set the data in the post fields
		}		
		
		if($this->use_https) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		}
		
		$response = curl_exec($curl); //Execute our request
		curl_close($curl);	
		
		if(!$response) 
			throw new Exception('Error querying API. Error: ' . curl_error($curl) . ' - Code: ' . curl_errno($curl));	
		
		return $response;
	}
}

?>