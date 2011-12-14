<?php
/**
 *
 * Class by JD for Paymo API - http://api.paymo.biz
 * 
 * API key : f232efcc9a6e3183af89ab8e50fd1c9f
 * http://api.paymo.biz/service/METHOD_NAME?api_key=API_KEY&format=RESPONSE_FORMAT&arg1=value1
 * 
 * @package paymo-api
 * @author James Duncombe
 * @copyright none
 * @version 0.0.1
 * @todo Make this thing work!!
 */
 
/**
 * The main class
 *
 * Main class for PHP Paymo API Wrapper. Read on for more fun and games!
 * @package paymo-api
 */
class Paymo extends Cache {

	public $api_key;
	public $format;
	public $auth_token;
	public $rest_url = 'http://api.paymo.biz/service/';
	public $response;
	public $error_msg;
	//public $cache_array = array();
	public $cache_file_data;
	public $method_args;
	public $use_auth_cache;
	public $use_data_cache;
	
	/**
	 * Setup a new instance of the class
	 *
	 * @param string 	$api_key			Paymo API key
	 * @param string 	$username			Paymo username
	 * @param string 	$password			Paymo password
	 * @param bool 		$use_auth_cache		Whether or not to use the cache for authenticating with Paymo
	 * @param bool		$use_data_cache		Whether or not to use the cache for data from Paymo
	 * @param string	$format				What format to request, JSON or XML?
	 */
	public function __construct($api_key, $username, $password, $use_auth_cache, $use_data_cache, $format) {
		/* Set main vars */
		$this->cache_file = './cache/test.cache';
		$this->cache_time = 8400;
		$this->cache_time_data = 120;
		$this->api_key = $api_key;
		$this->format = $format;
		$this->username = $username;
		$this->password = $password;
		$this->use_auth_cache = $use_auth_cache;
		$this->use_data_cache = $use_data_cache;
		
		/* If we are caching, check it, if not, don't */
		if ($this->use_auth_cache === true) {
			/* Check cache */
			if (!$this->checkCache()) {
				$result = $this->auth_login($this->username, $this->password);
				$xml = new SimpleXMLElement($result);
				parent::setCache($xml->token);
			} else {
				$this->auth_token = parent::getCache();
			}
		} else {
			$result = $this->auth_login($this->username, $this->password);
			$xml = new SimpleXMLElement($result);
			$this->auth_token = $xml->token;
		}
	}

	/**
	 * Function to call Paymo API methods
	 * @param string $method API method name that will be called
	 * @param array  $args Array of options to pass to method 
	 * @todo Must be able to use POST as well as GET for these methods
	 */
	public function callMethod($method, $args = array()) {

		$method_args = null;
		$arguements = $args;
		foreach ($arguements as $a => $b) {
			$method_args .= $a.'='.$b.'&';
		}
		$this->method_args = $method.'?'.rtrim($method_args, '&');
		
		$this->cache_file_data = './cache/'.$method.'.'.$this->format;
		
		/* If we're using caching, read the cache, otherwise poll the server for new results everytime */
		if ($this->use_data_cache === true) {
			/* Check format - XML or JSON */
			if ($this->format === 'xml') {
				if (!$this->checkDataCache()) {
					$raw_xml = file_get_contents($this->rest_url.$this->method_args);
					$xml = new SimpleXMLElement($raw_xml);
					file_put_contents($this->cache_file_data.'.'.$this->format, $xml->asXML());
					$this->response = simplexml_load_file($this->cache_file_data.'.'.$this->format);
				} else {
					$this->response = simplexml_load_file($this->cache_file_data.'.'.$this->format);
				}
			} elseif ($this->format === 'json') {
				if (!$this->checkDataCache()) {
					$raw_json = file_get_contents($this->rest_url.$this->method_args);
					file_put_contents($this->cache_file_data.'.'.$this->format, $raw_json);
					$this->response = $raw_json;
				} else {
					$this->response = file_get_contents($this->cache_file_data.'.'.$this->format);
				}
			}
		} else {
			/* Check format - XML or JSON */
			if ($this->format === 'xml') { 
				$raw_xml = file_get_contents($this->rest_url.$this->method_args);
				$this->response = simplexml_load_string($raw_xml);
			} elseif ($this->format === 'json') {
				$raw_json = file_get_contents($this->rest_url.$this->method_args);
				$this->response = $raw_json;
			}
		}
				
		if ($this->response->error) {
			$this->error_msg = 'Sorry, there was a problem, the error from Paymo was: '.strval($this->response->error->attributes()->message);
			$this->response = false;
		}
		return $this->response;
	}

	
	/**
	 * Check for cached data files time
	 * @return bool
	 */
    public function checkDataCache() {
    	if (file_exists($this->cache_file_data.'.'.$this->format)) {
    		$cache_time = (1 * 1 * 1 * 60);
    		$mod_time = filemtime($this->cache_file_data.'.'.$this->format) + $this->cache_time_data;
    		if ($mod_time > time()) {
    			return true;
    		} else {
    			return false;
    		}
    	} else {
    		return false;
    	}
    }



	/* Start of the Paymo API methods */
	
	/**
	 * paymo.auth.login
	 * 
	 * @link http://api.paymo.biz/docs/paymo.auth.login.html
	 * @param string $username Your username for Paymo
	 * @param string $password Your password for Paymo
	 * @return mixed
	 */
	function auth_login($username, $password) {
		$this->callMethod('paymo.auth.login', array("api_key" => $this->api_key, "username" => $username, "password" => $password));
		return $this->response ? $this->response : $this->error_msg;
	}

	/*
	* 
	* paymo.clients
	*
	* All submethods.
	*
	*/
	
	/**
	 * paymo.clients.getInfo
	 * @link http://api.paymo.biz/docs/paymo.clients.getList.html
	 * @param string $client_id Your client id
	 * @return mixed
	 */
	function clients_getInfo($client_id) {
		$this->callMethod('paymo.clients.getInfo', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token, "client_id" => $client_id));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * paymo.clients.getList
	 * @link http://api.paymo.biz/docs/paymo.clients.getList.html
	 * @return mixed
	 */
	function clients_getList() {
		$this->callMethod('paymo.clients.getList', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * paymo.clients.findByName
	 * @link http://api.paymo.biz/docs/paymo.clients.findByName.html
	 * @param string @client_name Client's name
	 * @return mixed
	 */
	function clients_findByName($client_name) {
		$this->callMethod('paymo.clients.findByName', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token, "name" => $client_name));
		return $this->response ? $this->response : $this->error_msg;
	}

	
	/* 
	* 
	* paymo.companies
	*
	* All submethods.
	*
	*/

	/**
	 * paymo.companies.getInfo
	 * @link http://api.paymo.biz/docs/paymo.companies.getInfo.html
	 * @return mixed
	 */
	function companies_getInfo() {
		$this->callMethod('paymo.companies.getInfo', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	
	/* 
	* 
	* paymo.projects
	*
	* All submethods.
	*
	*/
	
	/**
	 * paymo.projects.getList
	 * @link http://api.paymo.biz/docs/paymo.projects.getList.html
	 * @return mixed
	 */
	function projects_getList() {
		$this->callMethod('paymo.projects.getList', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/*
	*
	* payment.entries
	*
	* All submethods.
	*
	*/
	
	
	/**
	 * paymo.entries.findByProject
	 *
	 * @link http://api.paymo.biz/docs/paymo.entries.findByProject.html
	 * @param string $project_id Project ID that you are searching for
	 * @param string $start	Start time in MySQL datetime format
	 * @param string $end End time in MySQL datetime format
	 * @return mixed
	 */
	function entries_findByProject($project_id, $start, $end) {
		$this->callMethod('paymo.entries.findByProject', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token, "project_id" => $project_id, "start" => $start, "end" => $end));
		return $this->response ? $this->response : $this->error_msg;
	}

	/**
	 * paymo.entries.getTrackedTimeByProject
	 *
	 * @link http://api.paymo.biz/docs/paymo.entries.getTrackedTimeByProject.html
	 * @param string $project_id Project ID that you are searching for
	 * @param string $start	Start time in MySQL datetime format
	 * @param string $end End time in MySQL datetime format
	 * @return mixed 
	 */
	function entries_getTrackedTimeByProject($project_id, $start, $end) {
		$this->callMethod('paymo.entries.getTrackedTimeByProject', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token, "project_id" => $project_id, "start" => $start, "end" => $end));
		return $this->response ? $this->response : $this->error_msg;
	}

	/**
	 * paymo.entries.getTrackedTimeByUser
	 *
	 * @link http://api.paymo.biz/docs/paymo.entries.getTrackedTimeByUser.html
	 * @param string $user_id The user id that you wish to get stats for
	 * @return mixed 
	 */
	function entries_getTrackedTimeByUser($user_id) {
		$this->callMethod('paymo.entries.getTrackedTimeByUser', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token, "user_id" => $user_id));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/*
	*
	* paymo.reports
	*
	* All submethods.
	*
	*/

	/** 
	 * paymo.reports.create
	 * @link http://api.paymo.biz/docs/paymo.reports.create.html
	 * @param string $start	Start time in MySQL datetime format
	 * @param string $end End time in MySQL datetime format
	 * @param string $clients Array of client ID's
	 */
	function reports_create($start, $end, $clients) {
		$this->callMethod('paymo.reports.create', array("api_key" => $this->api_key, "format" => $this->format, "auth_token" => $this->auth_token, "clients" => $clients, "start" => $start, "end" => $end));
		return $this->response ? $this->response : $this->error_msg;
	}

}

?>