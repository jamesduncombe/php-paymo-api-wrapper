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
 * @version 0.0.3
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
  public $cache_directory;
  public $cache_time;
  public $force_cache;

  /**
   * Setup a new instance of the class
   *
   * @param string   $api_key      Paymo API key
   * @param string   $username      Paymo username
   * @param string   $password      Paymo password
   * @param bool     $use_auth_cache    Whether or not to use the cache for authenticating with Paymo
   * @param bool    $use_data_cache    Whether or not to use the cache for data from Paymo
   * @param string  $format        What format to request, JSON or XML?
   */
   public function __construct($api_key, $username, $password, $use_auth_cache, $use_data_cache, $format, $cache_directory = './cache1/', $cache_time = 8400, $force_cache = false) {

    /* Set main vars */
    $this->cache_directory = $cache_directory;
    $this->cache_time = $cache_time;
    $this->api_key = $api_key;
    $this->format = $format;
    $this->username = $username;
    $this->password = $password;
    $this->use_auth_cache = $use_auth_cache;
    $this->use_data_cache = $use_data_cache;
    $this->force_cache = $force_cache;

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
   * @param string $request_type  HTTP request type for API method (POST or GET)
   * @param string $method    API method name that will be called
   * @param array  $arguments    Array of options to pass to method
   */
  public function callMethod($request_type, $method, $params = array()) {

    $request_params = '';
    $cache_request_params = '';

    // iterate over the method arguments
    foreach ($params as $key => $value) {
      $request_params .= urlencode($key).'='.urlencode($value).'&';
      // not add auth_token to cache_request_params
      if($key !== 'auth_token'){
        $cache_request_params .= urlencode($key).'='.urlencode($value).'&';
      }
    }

    // setup the string which is encoded and cleaned
    $request_params = rtrim($request_params, '&');
    $cache_request_params = rtrim($cache_request_params, '&');

    // create hash for API cache file name
    $hash = md5($this->rest_url.$method.'?'.$cache_request_params);

    // set the location of the cache file if there is one (we'll check in a minute)
    $this->cache_file = $this->cache_directory.$hash.'.'.$this->format;

    // if we're using caching, read the cache, otherwise poll the server for new results everytime
    if ($this->use_data_cache === true) {

      // check format - XML or JSON
      if ($this->format === 'xml') {
        if (!$this->force_cache || ($this->force_cache && !file_exists($this->cache_file))) {
          if (!$this->checkCache()) {
            // are we using GET or POST?
            $api_response = $this->makeRESTRequest($request_type, $method, $request_params);
            $xml = new SimpleXMLElement($api_response);
            file_put_contents($this->cache_file, $xml->asXML());
          }
        }
        $this->response = simplexml_load_file($this->cache_file);
      // must be json, continue
      } elseif ($this->format === 'json') {
        if (!$this->force_cache || ($this->force_cache && !file_exists($this->cache_file))) {
          if (!$this->checkCache()) {
            $api_response = $this->makeRESTRequest($request_type, $method, $request_params);
            file_put_contents($this->cache_file, $api_response);
            $this->response = json_decode($api_response);
          } else {
            $this->response = json_decode(file_get_contents($this->cache_file));
          }
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

    if (isset($this->response->error)) {
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
   * @param string $request_type    HTTP request type for API method (POST or GET)
   * @param string $request_params  Encoded string of parameters to send to Paymo API
   * @return string          Raw API response
   */
  public function makeRESTRequest($request_type, $method, $request_params) {

    // @see http://www.lornajane.net/posts/2010/three-ways-to-make-a-post-request-from-php
    if ($request_type === 'POST') {
      $context = stream_context_create(array(
        'http' => array(
          'method'   => $request_type,
          'header'   => 'Content-Type: application/x-www-form-urlencoded',
          'content'   => $request_params
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
   * @param int $client_id Your client id
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
  function projects_getList($include_task_lists = 0, $include_tasks = 0) {
    $this->callMethod('GET', 'paymo.projects.getList', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token,'include_task_lists' => $include_task_lists, 'include_tasks' => $include_tasks));
    return $this->response ? $this->response : $this->error_msg;
  }

  /**
   * paymo.projects.getInfo
   * @ingroup Projects
   * @see http://api.paymo.biz/docs/paymo.projects.getInfo.html
   * @return mixed
  */
  function projects_getInfo($project_id, $include_task_lists = 0, $include_tasks = 0) {
      $this->callMethod('GET', 'paymo.projects.getInfo', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'project_id' => $project_id, 'include_task_lists' => $include_task_lists, 'include_tasks' => $include_tasks));
      return $this->response ? $this->response : $this->error_msg;
  }

  
  /**
   * paymo.projects.findByUser
   * @ingroup Projects
   * @see http://api.paymo.biz/docs/paymo.projects.findByUser.html
   * @param string @user_id Users's id
   * @return mixed
   */
  function projects_findByUser($user_id, $include_task_lists = 0,$include_tasks = 0, $include_retired_projects = 0 ) {
    $this->callMethod('GET', 'paymo.projects.findByUser', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'user_id' => $user_id, 'include_task_lists' => $include_task_lists, 'include_task_lists' => $include_task_lists, 'include_retired_projects' => $include_retired_projects));
    return $this->response ? $this->response : $this->error_msg;
  }
  
  /**
   * paymo.tasks
   * @defgroup Tasks
   */

  /**
   * paymo.tasks.findByProject
   * @ingroup Tasks
   * @see http://api.paymo.biz/docs/paymo.tasks.findByProject.html
   * @param int $project_id  The id of the project you want to search for tasks in
   * @return mixed
   */
  function tasks_findByProject($project_id) {
    $this->callMethod('GET', 'paymo.tasks.findByProject', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'project_id' => $project_id));
    return $this->response ? $this->response : $this->error_msg;
  }

  /**
   * paymo.tasks.add
   * @ingroup Tasks
   * @see http://api.paymo.biz/docs/paymo.tasks.findByProject.html
   * @param int $project_id  The id of the project you want to search for tasks in
   * @return mixed
   */
  function tasks_add(
    $name,
    $project_id,
    $tasklist_id=null,
    $description=null,
    $billable=null,
    $price_per_hour=null,
    $budget_hours=null,
    $due_date=null,
    $user_id=null
    ) {
    $this->callMethod('POST', 'paymo.tasks.add',
      array(
        'api_key' => $this->api_key, 
        'format' => $this->format, 
        'auth_token' => $this->auth_token, 
        'name' => $name,
        'project_id' => $project_id,
        'tasklist_id' => $tasklist_id,
        'description' => $description,
        'billable' => $billable,
        'price_per_hour' => $price_per_hour,
        'budget_hours' => $budget_hours,
        'due_date' => $due_date,
        'user_id' => $user_id,

        )
      );
    return $this->response ? $this->response : $this->error_msg;
  }


  /**
   * paymo.tasks.getInfo
   * @ingroup Tasks
   * @see http://api.paymo.biz/docs/paymo.tasks.getInfo.html
   * @return mixed
  */
  function tasks_getInfo($task_id) {
      $this->callMethod('GET', 'paymo.tasks.getInfo', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'task_id' => $task_id));
      return $this->response ? $this->response : $this->error_msg;
  }

  /**
   * paymo.invoices
   * @defgroup Invoices
   */

  /**
   * paymo.invoices.find
   * @ingroup Invoices
   * @see http://api.paymo.biz/docs/paymo.invoices.find.html
   * @param int $client_id Client ID that you wish to search for - optional
   * @param string $start Start date in MySQL datetime format - optional
   * @param string $end End date in MySQL datetime format - optional
   * @param string $status Valid invoice statuses are: draft, sent, viewed, paid, void. - optional
   */
  function invoices_find($client_id, $start_date, $end_date, $status) {
    $this->callMethod('GET', 'paymo.invoices.find', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'client_id' => $client_id, 'start_date' => $start_date, 'end_date' => $end_date, 'status' => $status));
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
   * @param int $project_id Project ID that you are searching for
   * @param string $start  Start time in MySQL datetime format
   * @param string $end End time in MySQL datetime format
   * @return mixed
   */
  function entries_findByProject($project_id, $start, $end) {
    $this->callMethod('GET', 'paymo.entries.findByProject', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'project_id' => $project_id, 'start' => $start, 'end' => $end));
    return $this->response ? $this->response : $this->error_msg;
  }

  /**
   * paymo.entries.findByUser
   * @ingroup Entries
   * @see http://api.paymo.biz/docs/paymo.entries.findByUser.html
   * @param int $user_id User ID that you are searching for
   * @param string $start  Start time in MySQL datetime format
   * @param string $end End time in MySQL datetime format
   * @return mixed
   */
  function entries_findByUser($user_id, $start, $end) {
    $this->callMethod('GET', 'paymo.entries.findByUser', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'user_id' => $user_id, 'start' => $start, 'end' => $end));
    return $this->response ? $this->response : $this->error_msg;
  }

  /**
   * paymo.entries.getTrackedTimeByProject
   * @ingroup Entries
   * @see http://api.paymo.biz/docs/paymo.entries.getTrackedTimeByProject.html
   * @param int $project_id Project ID that you are searching for
   * @param string $start  Start time in MySQL datetime format
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
   * @param int $user_id The user id that you wish to get stats for
   * @return mixed
   */
  function entries_getTrackedTimeByUser($user_id) {
    $this->callMethod('GET', 'paymo.entries.getTrackedTimeByUser', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token, 'user_id' => $user_id));
    return $this->response ? $this->response : $this->error_msg;
  }

  /**
   * paymo.entries.addBulk
   * @ingroup Entries
   * @see http://api.paymo.biz/docs/paymo.entries.addBulk.html
   * @param string $date The date for the entry - format: 2009-03-19
   * @param int $duration The duration of time to track (in seconds)
   * @param int $task_id The task id
   * @param boolean $billed If the time has been billed or not (1 if billed, 0 if not) (optional)
   * @param string $description The description of the task (optional)
   * @return mixed
   */
  function entries_addBulk($date, $duration, $task_id, $billed, $description) {
    $this->callMethod('POST', 'paymo.entries.addBulk', array(
      'api_key'       => $this->api_key,
      'format'        => $this->format,
      'auth_token'    => $this->auth_token,
      'date'          => $date,
      'duration'      => $duration,
      'billed'        => $billed,
      'task_id'       => $task_id,
      'description'   => $description
    ));
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
   * @param string $start  Start time in MySQL datetime format
   * @param string $end  End time in MySQL datetime format (<a href="http://api.paymo.biz/docs/misc.dates.html">more info on the date format</a>)
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

  /**
   * paymo.users
   * @defgroup Users
   */

  /**
   * paymo.users.getList
   * @ingroup Users
   * @see http://api.paymo.biz/docs/paymo.users.getList.html
   * @return mixed
   */
  function users_getList() {
    $this->callMethod('GET', 'paymo.users.getList', array('api_key' => $this->api_key, 'format' => $this->format, 'auth_token' => $this->auth_token));
    return $this->response ? $this->response : $this->error_msg;
  }


}

?>