<?php
/**
 *
 * PHP wrapper for Paymo API - http://api.paymo.biz
 *
 * http://api.paymo.biz/service/METHOD_NAME?api_key=API_KEY&format=RESPONSE_FORMAT&arg1=value1
 * 
 * @package paymo-api
 * @author James Duncombe
 * @copyright none
 * @version 0.0.1
 */
 
/**
 * The main paymo class
 * @package paymo-api
 */
class Paymo extends Cache {

	public $api_key;
	public $format;
	public $auth_token;
	public $rest_url = 'https://api.paymo.biz/service/';
	public $response;
	public $error_msg;
	public $cache_file_data;
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
		$this->cache_file = './cache/';
		$this->cache_time = 8400;
		$this->cache_time_data = 120;
		$this->api_key = $api_key;
		$this->format = $format;
		$this->username = $username;
		$this->password = $password;
		$this->use_auth_cache = $use_auth_cache;
		$this->use_data_cache = $use_data_cache;
		
		// must always authenticate
		$result = $this->auth_login($this->username, $this->password);
		
		// check if we're using XML or JSON format
		if ($this->format === 'xml') {
			$this->auth_token = $result->token;
		} elseif ($this->format === 'json') {
			$this->auth_token = $result->token->_content;
		}

	}

	/**
	 * Function to call Paymo API methods
	 *
	 * @param string $request_type	HTTP request type for API method (POST or GET)
	 * @param string $method		API method name that will be called
	 * @param array  $arguments		Array of options to pass to method 
	 */
	public function callMethod($request_type, $method, $params = array()) {

		// iterate over the method arguments
		foreach ($params as $key => $value) {
			$request_params .= urlencode($key).'='.urlencode($value).'&';
		}
		
		// setup the string which is encoded and cleaned
		$request_params = rtrim($request_params, '&');
		
		// set the location of the cache file if there is one (we'll check in a minute)
		$this->cache_file = './cache/'.$method.'.'.$this->format;
		
		// if we're using caching, read the cache, otherwise poll the server for new results everytime
		if ($this->use_data_cache === true) {
			
			// check format - XML or JSON
			if ($this->format === 'xml') {
				if (!$this->checkCache()) {
					// are we using GET or POST?
					$api_response = $this->makeRESTRequest($request_type, $method, $request_params);
					$xml = new SimpleXMLElement($api_response);
					file_put_contents($this->cache_file, $xml->asXML());
				}
				$this->response = simplexml_load_file($this->cache_file);
			// must be json, continue
			} elseif ($this->format === 'json') {
				if (!$this->checkCache()) {
					$api_response = $this->makeRESTRequest($request_type, $method, $request_params);
					file_put_contents($this->cache_file, $api_response);
					$this->response = json_decode($api_response);
				} else {
					$this->response = json_decode(file_get_contents($this->cache_file));
				}
			}

		// if we're not using a cache
		} else {

			$api_response = $this->makeRESTRequest($request_type, $method, $request_params);

			// check format - XML or JSON
			if ($this->format === 'xml') { 
				$this->response = simplexml_load_string($api_response);
			} elseif ($this->format === 'json') {
				$this->response = json_decode($api_response);
			}
		}
				
		if ($this->response->error) {
			if ($this->format === 'xml') {
			$this->error_msg = 'Sorry, there was a problem, the error from Paymo was: '.strval($this->response->error->attributes()->message);
			} elseif ($this->format === 'json') {
				$this->error_msg = 'Sorry, there was a problem, the error from Paymo was: '.strval($this->response->error->message);	
			}
			$this->response = false;
		}
		return $this->response;
	}
	
	/**
	 * To make the actual API call
	 *
	 * @param string $request_type		HTTP request type for API method (POST or GET)
	 * @param string $request_params	Encoded string of parameters to send to Paymo API
	 * @return string					Raw API response
	 */
	public function makeRESTRequest($request_type, $method, $request_params) {

		// @see http://www.lornajane.net/posts/2010/three-ways-to-make-a-post-request-from-php
		if ($request_type === 'POST') {
			$context = stream_context_create(array(
				'http' => array(
					'method' 	=> $request_type,
					'header' 	=> 'Content-Type: application/x-www-form-urlencoded',
					'content' 	=> $request_params
				)
			));
			$api_response = file_get_contents($this->rest_url.$method, false, $context);
		} else {
			$api_response = file_get_contents($this->rest_url.$method.'?'.$request_params);
		}
		
		// return our raw API response
		return $api_response;

	}

	/**
	 * Start of the Paymo API methods
	 */
	 
	/**
	 * paymo.auth
	 * @defgroup Auth 
	 */

	/**
	 * paymo.auth.login
	 * @ingroup Auth
	 * @see http://api.paymo.biz/docs/paymo.auth.login.html
	 * @param string $username Your username for Paymo
	 * @param string $password Your password for Paymo
	 * @return mixed
	 */
	function auth_login($username, $password) {
		$this->callMethod('GET', 'paymo.auth.login', array('api_key' => $this->api_key, 'username' => $username, 'password' => $password, 'format' => $this->format));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * paymo.auth.checkToken
	 * @ingroup Auth
	 * @see http://api.paymo.biz/docs/paymo.auth.checkToken.html
	 * @return mixed
	 */
	function auth_checkToken() {
		$this->callMethod('GET', 'paymo.auth.checkToken', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}

	/**
	  * paymo.clients
	  * @defgroup Clients
	  */
	
	/**
	 * paymo.clients.getInfo
	 * @ingroup Clients
	 * @see http://api.paymo.biz/docs/paymo.clients.getList.html
	 * @param string $client_id Your client id
	 * @return mixed
	 */
	function clients_getInfo($client_id) {
		$this->callMethod('GET', 'paymo.clients.getInfo', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'client_id' => $client_id));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * paymo.clients.getList
	 * @ingroup Clients
	 * @see http://api.paymo.biz/docs/paymo.clients.getList.html
	 * @return mixed
	 */
	function clients_getList() {
		$this->callMethod('GET', 'paymo.clients.getList', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * paymo.clients.findByName
	 * @ingroup Clients
	 * @see http://api.paymo.biz/docs/paymo.clients.findByName.html
	 * @param string @client_name Client's name
	 * @return mixed
	 */
	function clients_findByName($client_name) {
		$this->callMethod('GET', 'paymo.clients.findByName', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'name' => $client_name));
		return $this->response ? $this->response : $this->error_msg;
	}

	
	/** 
	 * paymo.companies
	 * @defgroup Companies
	 */

	/**
	 * paymo.companies.getInfo
	 * @ingroup Companies
	 * @see http://api.paymo.biz/docs/paymo.companies.getInfo.html
	 * @return mixed
	 */
	function companies_getInfo() {
		$this->callMethod('GET', 'paymo.companies.getInfo', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	
	/** 
	 * paymo.projects
	 * @defgroup Projects
	 */
	
	/**
	 * paymo.projects.getList
	 * @ingroup Projects
	 * @see http://api.paymo.biz/docs/paymo.projects.getList.html
	 * @return mixed
	 */
	function projects_getList() {
		$this->callMethod('GET', 'paymo.projects.getList', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * payment.entries
	 * @defgroup Entries
	 */
	
	
	/**
	 * paymo.entries.findByProject
	 * @ingroup Entries
	 * @see http://api.paymo.biz/docs/paymo.entries.findByProject.html
	 * @param string $project_id Project ID that you are searching for
	 * @param string $start	Start time in MySQL datetime format
	 * @param string $end End time in MySQL datetime format
	 * @return mixed
	 */
	function entries_findByProject($project_id, $start, $end) {
		$this->callMethod('GET', 'paymo.entries.findByProject', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'project_id' => $project_id, 'start' => $start, 'end' => $end));
		return $this->response ? $this->response : $this->error_msg;
	}

	/**
	 * paymo.entries.getTrackedTimeByProject
	 * @ingroup Entries
	 * @see http://api.paymo.biz/docs/paymo.entries.getTrackedTimeByProject.html
	 * @param string $project_id Project ID that you are searching for
	 * @param string $start	Start time in MySQL datetime format
	 * @param string $end End time in MySQL datetime format
	 * @return mixed 
	 */
	function entries_getTrackedTimeByProject($project_id, $start, $end) {
		$this->callMethod('GET', 'paymo.entries.getTrackedTimeByProject', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'project_id' => $project_id, 'start' => $start, 'end' => $end));
		return $this->response ? $this->response : $this->error_msg;
	}

	/**
	 * paymo.entries.getTrackedTimeByUser
	 * @ingroup Entries
	 * @see http://api.paymo.biz/docs/paymo.entries.getTrackedTimeByUser.html
	 * @param string $user_id The user id that you wish to get stats for
	 * @return mixed 
	 */
	function entries_getTrackedTimeByUser($user_id) {
		$this->callMethod('GET', 'paymo.entries.getTrackedTimeByUser', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'user_id' => $user_id));
		return $this->response ? $this->response : $this->error_msg;
	}
	
	/**
	 * paymo.reports
	 * @defgroup Reports
	 */

	/** 
	 * paymo.reports.create
	 * @ingroup Reports
	 * @see http://api.paymo.biz/docs/paymo.reports.create.html
	 * @param string $start	Start time in MySQL datetime format
	 * @param string $end	End time in MySQL datetime format (<a href="http://api.paymo.biz/docs/misc.dates.html">more info on the date format</a>)
	 * @param string $clients Comma seperated list of client ID's
	 */
	function reports_create($start, $end, $clients, $optional_arguments = array()) {
		
		// set the default arguments for this API method
		$arguments = array(
			'api_key' => $this->api_key,
			'format' => $this->format,
			'auth_token' => $this->auth_token,
			'clients' => $clients,
			'start' => $start,
			'end' => $end
		);
		
		// loop through the optional arguments and add them to the query string
		foreach ( $optional_arguments as $argument ) {
			array_push($arguments, $argument);
		}
		
		// finally, call the API method, passing the arguments
		$this->callMethod('POST', 'paymo.reports.create', $arguments);
		
		return $this->response ? $this->response : $this->error_msg;
	}

}

?>