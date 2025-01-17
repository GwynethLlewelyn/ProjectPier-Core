<?php

  // ---------------------------------------------------
  //  System callback functions, registered automatically
  //  or in application/application.php
  // ---------------------------------------------------

  /**
  * Gets called, when an undefined class is being instantiated
  *
  * @param_string $load_class_name
  */
  spl_autoload_register(function ($load_class_name) {  // how to do it in PHP 5.3+ (gwyneth 20210410)
    static $loader = null;
    $class_name = strtoupper($load_class_name);

    // Try to get this data from index...
    if (isset($GLOBALS[AutoLoader::GLOBAL_VAR]) &&
      isset($GLOBALS[AutoLoader::GLOBAL_VAR][$class_name])) {
      return include $GLOBALS[AutoLoader::GLOBAL_VAR][$class_name];
    } // if

    if (!$loader) {
      $loader = new AutoLoader();
      $loader->addDir(ROOT . '/application');
      $loader->addDir(ROOT . '/environment');
      $loader->addDir(ROOT . '/library');
      $loader->setIndexFilename(ROOT . '/cache/autoloader.php');
    } // if

    try {
      $loader->loadClass($class_name);
    } catch(Exception $e) {
      die('Exception in AutoLoader: ' . $e->__toString());
    } // try
  }
  ); // spl_autoload_register

  /**
  * ProjectPier shutdown function
  *
  * @param void
  * @return null
  */
  function __shutdown() {
    try {
      $logger_session = Logger::getSession();
    } catch(exception $e) {
      error_log(__FILE__ . "(" . __LINE__ . "): ProjectPier shutdown function error (not enough memory?); error was ". $e->getMessage());
    }
    if (($logger_session instanceof Logger_Session) && !$logger_session->isEmpty()) {
      Logger::saveSession();
    } // if
    $last_error = error_get_last();
    if($last_error['type'] === E_ERROR || $last_error['type'] === E_PARSE) {
      include 'error.php';
    }
    if (ob_get_length()) ob_end_flush();
  } // __shutdown

  /**
  * This function will be used as error handler for production
  *
  * @param integer $code
  * @param string $message
  * @param string $file
  * @param integer $line
  * @return null
  */
  function __production_error_handler($code, $message, $file, $line) {
    // Skip non-static method called statically type of error...
    // 2048 = E_SRICT, but we don't use the constant as it may not be defined in the
    // current version of PHP.
    // We also skip errors that have been suppressed using @ (which sets
    // error_reporting() to zero).
    if ($code == 2048 || error_reporting() == 0) {
      return;
    } // if

    Logger::log("Error: $message in '$file' on line $line (error code: $code)", Logger::ERROR);
  } // __production_error_handler

  /**
  * This function will be used as exception handler in production environment
  *
  * @param Exception $exception
  * @return null
  */
  function __production_exception_handler($exception) {
    Logger::log($exception, Logger::FATAL);
  } // __production_exception_handler

  // ---------------------------------------------------
  //  Get URL
  // ---------------------------------------------------

  /**
  * Return an application URL
  *
  * If $include_project_id variable is present active_project variable will be added to the list of params if we have a
  * project selected (active_project() function returns valid project instance)
  *
  * @param string $controller_name
  * @param string $action_name
  * @param array $params
  * @param string $anchor
  * @param boolean $include_project_id
  * @return string
  */
  function get_url($controller_name = null, $action_name = null, $params = null, $anchor = null, $include_project_id = true, $separator = '&amp;') {
    //trace(__FILE__,"get_url($controller_name, $action_name, params?, $anchor, $include_project_id, $separator)");
    $controller = trim($controller_name) ? $controller_name : DEFAULT_CONTROLLER;
    $action = trim($action_name) ? $action_name : DEFAULT_ACTION;
    if (!is_array($params) && !is_null($params)) {
      $params = array('id' => $params);
    } // if

    $url_params = array('c=' . $controller, 'a=' . $action);

    if ($include_project_id) {
      if (function_exists('active_project') && (active_project() instanceof Project)) {
        if (!(is_array($params) && isset($params['active_project']))) {
          $url_params[] = 'active_project=' . active_project()->getId();
        } // if
      } // if
    } // if

    // defeat caches
    $url_params[]=time();
    if (isset($_REQUEST['trace'])) {
      $url_params[]='trace';
    }

    if (is_array($params)) {
      foreach ($params as $param_name => $param_value) {
        if (is_bool($param_value)) {
          $url_params[] = $param_name . '=1';
        } else {
          $url_params[] = $param_name . '=' . urlencode($param_value);
        } // if
      } // foreach
    } // if

    if (trim($anchor) <> '') {
      $anchor = '#' . $anchor;
    } // if

    return with_slash(ROOT_URL) . 'index.php?' . implode($separator, $url_params) . $anchor;
  } // get_url

  // ---------------------------------------------------
  //  Product
  // ---------------------------------------------------

  /**
  * Return product name. This is a wrapper function that abstracts the product name
  *
  * @param void
  * @return string
  */
  function product_name() {
    return PRODUCT_NAME;
  } // product_name

  /**
  * Return product version, wrapper function.
  *
  * @param void
  * @return string
  */
  function product_version() {
    // 0.6 is the last version that comes without PRODUCT_VERSION constant that is set up
    return defined('PRODUCT_VERSION') ? PRODUCT_VERSION : '0.8.0';
  } // product_version

  /**
  * Returns product signature (name and version). If user is not logged in and
  * is not member of owner company he will see only product name
  *
  * @param void
  * @return string
  */
  function product_signature() {
    if (function_exists('logged_user') && (logged_user() instanceof User) && logged_user()->isMemberOfOwnerCompany()) {
      $result = lang('footer powered', 'http://www.projectpier.org/', clean(product_name()) . ' ' . product_version());
      if (Env::isDebugging()) {
        ob_start();
        benchmark_timer_display(false);
        $result .= '. ' . ob_get_clean();
        //if (function_exists('memory_get_usage')) {      // guaranteed to exist since PHP 5.2
          $result .= '. ' . format_filesize(memory_get_usage());
        //} // if
      } // if
      return $result;
    } else {
      return lang('footer powered', 'http://www.ProjectPier.org/', clean(product_name()));
    } // if
  } // product_signature

  // ---------------------------------------------------
  //  Request, routes replacement methods
  // ---------------------------------------------------

  /**
  * Return matched request controller
  *
  * @access public
  * @param void
  * @return string
  */
  function request_controller() {
    $controller = trim(array_var($_GET, 'c', DEFAULT_CONTROLLER));
    return $controller && is_valid_function_name($controller) ? $controller : DEFAULT_CONTROLLER;
  } // request_controller

  /**
  * Return matched request action
  *
  * @access public
  * @param void
  * @return string
  */
  function request_action() {
    $action = trim(array_var($_GET, 'a', DEFAULT_ACTION));
    return $action && is_valid_function_name($action) ? $action : DEFAULT_ACTION;
  } // request_action

  // ---------------------------------------------------
  //  Controllers and stuff
  // ---------------------------------------------------

  /**
  * Set internals of specific company website controller
  *
  * @access public
  * @param PageController $controller
  * @param string $layout Project or company website layout. Or any other...
  * @return null
  */
  function prepare_company_website_controller(PageController $controller, $layout = 'dashboard') {

    // If we don't have logged user prepare referer params and redirect user to login page
    if (!(logged_user() instanceof User)) {
      $ref_params = array();
      foreach ($_GET as $k => $v) {
        $ref_params['ref_' . $k] = $v;
      }
      trace(__FILE__, 'prepare_company_website_controller(): not logged in, redirect');
      $controller->redirectTo('access', 'login', $ref_params);
    } // if

    $controller->setLayout($layout);
    $controller->addHelper('breadcrumbs');
    $controller->addHelper('pageactions');
    $controller->addHelper('viewoptions');
    $controller->addHelper('tabbednavigation');
    $controller->addHelper('company_website');
    $controller->addHelper('project_website');
  } // prepare_company_website_controller

  // ---------------------------------------------------
  //  Company website interface
  // ---------------------------------------------------

  /**
  * Return owner company object if we are on company website and it is loaded
  *
  * @access public
  * @param void
  * @return Company
  */
  function owner_company() {
    return CompanyWebsite::instance()->getCompany();
  } // owner_company

  /**
  * Return logged user if we are on company website
  *
  * @access public
  * @param void
  * @return User
  */
  function logged_user() {
    return CompanyWebsite::instance()->getLoggedUser();
  } // logged_user

  /**
  * Return active project if we are on company website
  *
  * @access public
  * @param void
  * @return Project
  */
  function active_project() {
    return CompanyWebsite::instance()->getProject();
  } // active_project

  // ---------------------------------------------------
  //  Config interface
  // ---------------------------------------------------

  /**
  * Return config option value
  *
  * @access public
  * @param string $name Option name
  * @param mixed $default Default value that is returned in case of any error
  * @return mixed
  */
  function config_option($option, $default = null) {
    return ConfigOptions::getOptionValue($option, $default);
  } // config_option

  /**
  * Set value of specific configuration option
  *
  * @param string $option_name
  * @param mixed $value
  * @return boolean
  */
  function set_config_option($option_name, $value) {
    $config_option = ConfigOptions::getByName($option_name);
    if (!($config_option instanceof ConfigOption)) {
      return false;
    } // if

    $config_option->setValue($value);
    return $config_option->save();
  } // set_config_option

  /**
  * This function will return object by the manager class and object ID
  *
  * @param integer $object_id
  * @param string $manager_class
  * @return ApplicationDataObject
  */
  function get_object_by_manager_and_id($object_id, $manager_class) {
    trace(__FILE__, "get_object_by_manager_and_id($object_id, $manager_class)");
    $object_id = (integer) $object_id;
    $manager_class = trim($manager_class);

    if (!is_valid_function_name($manager_class) || !class_exists($manager_class, true)) {
      throw new Error("Class '$manager_class' does not exist");
    } // if

    $code = "return $manager_class::findById($object_id);";
    try {
      $object = eval($code);
    } catch (Exception $e) {
      $object = null;
    }

    return $object instanceof DataObject ? $object : null;
  } // get_object_by_manager_and_id

  /**
  * This function will return duration in secs in weeks, days, hours, minutes and seconds
  *
  * @param integer $secs
  * @return string
  */

  function duration($secs)
  {
    $vals = array(
      'weeks' => (int) ($secs / 86400 / 7),
      'days' => $secs / 86400 % 7,
      'hours' => $secs / 3600 % 24,
      'minutes' => $secs / 60 % 60,
      'seconds' => $secs % 60
    );

    $ret = array();
    $added = false;

    foreach ($vals as $k => $v) {
      if ($v > 0 || $added) {
        $added = false;  // true
        $ret[] = $v .' '. lang($k);
      }
    }
    return join(' ', $ret);
  }

  function make_json_for_ajax_return($result,$messageboard = null,$data = array(),$actionjs = null){
  	/*
  	 * result : result function PHP boolean permit to add icon src link icon ok or icon ko to message message board
  	 * messageboard : message to display in the IHM ajax board (option)
  	 * data : data to return to browser
  	 * actionjs : javascript code which can be executed on browser
  	 *
  	 * Return array => [['result'],['messageajaxboard'],['data'],['actionjs']]
  	 */
  	$myarr = array();
  	$myarr= array(); //init
  	$myarr['result'] = $result;
	//image
  	$img = "ok.gif";
  	if (!$result) $img = "cancel_gray.gif";
  	$myarr['messageboard'] = '<img src="' . get_image_url("icons/$img") . '">' . $messageboard;
  	$myarr['data'] = $data;
  	$myarr['actionjs'] = $actionjs;
	/*
	 * The first two headers prevent the browser
	 * from caching the response (a problem with IE and GET requests)
	 * and the third sets the correct MIME type for JSON.
	 */
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: text/html; charset: UTF-8');

  	return json_encode($myarr);
  }

  /**
  * Returns a string with backslashes before characters that need to be escaped.
  * As required by MySQL and suitable for multi-byte character sets
  * Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and ctrl-Z.
  * In addition, the special control characters % and _ are also escaped,
  * suitable for all statements, but especially suitable for `LIKE`.
  *
  * Put here because sometimes we need to sanitize MySQL strings but don't know if
  * we have an active connection to MySQL or not! (gwyneth 20210411)
  * Source: https://www.php.net/manual/en/mysqli.real-escape-string.php#121402
  *
  * @param string $string String to add slashes to
  * @return $string with `\` prepended to reserved characters
  *
  * @author Trevor Herselman
  */
  if (function_exists('mb_ereg_replace'))
  {
      function mb_escape(string $string)
      {
          return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]', '\\\0', $string);
      }
  } else {
      function mb_escape(string $string)
      {
          return preg_replace('~[\x00\x0A\x0D\x1A\x22\x25\x27\x5C\x5F]~u', '\\\$0', $string);
      }
  }

  /**
  * Moving from PHP 4 to 5 and now to 7 and 8 has introduced a very tricky issue with objects
  * instantiated from classes which, in turn, will call the object instantiation _again_
  * (possibly due to the different approaches to inheritance rules in PHP 7/8).
  * This invariably results in an 'instantiation loop' which quickly exhaust all memory.
  * This function is a humble hack to check if enough memory is available, and, if not,
  * log an error via the _standard_ PHP mechanism (because logging via the PP Logger
  * _also_ requires new objects to be instantiated...
  *
  * @param void
  * @return boolean
  */
  function check_memory_prevent_loop() {
    static $maxMemory;  // we use it as a cache value, because a ini_get() call might consume memory too, and it's unlikely that this value will change soon...

    if ($maxMemory == 0) {
      $maxMemory = memToBytes(ini_get('memory_limit'));
    }
    $currMem = memory_get_usage();
    $testMemExhausted = (boolean)($currMem < $maxMemory - MEGABYTE);

    file_put_contents(MEMORY_LOG, date("c") . "\tMemory in usage: " . $currMem . "(out of " . $maxMemory . ") Memory exhausted? " . $testMemExhausted . PHP_EOL, FILE_APPEND | LOCK_EX);

    return $testMemExhausted;
  }

  const MEGABYTE = 1024 * 1024;
  const MEMORY_LOG = ROOT . "/cache/memory.log";

  /**
  * Because ini_get('memory_limit') does _not_ return an integer, but an annotated string (e.g "12G"),
  * we need to convert it. (gwyneth 20210412)
  *
  * @see https://stackoverflow.com/a/4613049/1035977
  *
  * @param $string result of ini_get('memory_limit') (and others using the same notation)
  * @return integer
  *
  * @author Sergey Toropenko (akond)
  */
  function memToBytes($string) {
    sscanf ($string, '%u%c', $number, $suffix);
    if (isset ($suffix)) {
        $number = $number * pow (1024, strpos (' KMG', strtoupper($suffix)));
    }
    return $number;
  }

