<?php

class app {

  public static $named_params = array();

  public static $config = array();
  
  private static $conversions = array(
    // Group of one or more digits
    '/\/:num/'  => '((\/?)[0-9]+(\/?))',
    // Group of one or more characters
    '/\/:char/' => '((\/?)[a-zA-Z]+(\/?))',
    // Group of one or more of the following: -_a-zA-Z0-9
    '/\/:id/'   => '((\/?)[-_a-zA-Z0-9]+(\/?))',
    // Zero or more groups of one or more of the following: -_a-zA-Z0-9=
    '/\/:opt/'  => '(((\/?)[-_a-zA-Z0-9=:]+(\/?))*)'
  );
  
  private static $status_codes = array(
    100     => 'Continue',
    101     => 'Switching Protocols',
    
    200     => 'OK',
    201     => 'Created',
    202     => 'Accepted',
    203     => 'Non-Authoritative Information',
    204     => 'No Content',
    205     => 'Reset Content',
    206     => 'Partial Content',
    
    300     => 'Multiple Choices',
    301     => 'Moved Permanently',
    302     => 'Found',
    303     => 'See Other',
    304     => 'Not Modified',
    305     => 'Use Proxy',
    307     => 'Temporary Redirect',
    
    400     => 'Bad Request',
    401     => 'Unauthorized',
    402     => 'Payment Required',
    403     => 'Forbidden',
    404     => 'Not Found',
    405     => 'Method Not Allowed',
    406     => 'Not Acceptable',
    407     => 'Proxy Authentication Required',
    408     => 'Request Timeout',
    409     => 'Conflict',
    410     => 'Gone',
    411     => 'Length Required',
    412     => 'Precondition Failed',
    413     => 'Request Entity Too Large',
    414     => 'Request-URI Too Long',
    415     => 'Unsupported Media Type',
    416     => 'Requested Range Not Satisfiable',
    417     => 'Expectation Failed',
    
    500     => 'Internal Server Error',
    501     => 'Not Implemented',
    502     => 'Bad Gateway',
    503     => 'Service Unavailable',
    504     => 'Gateway Timeout',
    505     => 'HTTP Version Not Supported'
    );

  // Private statics vars for storing class references:
  private static $libs = array();
  private static $controllers = array();
  private static $models = array();

  private static $routes = array();
  private static $request_controller = "";
  private static $request_method = "";
  private static $request_type = "";
  private static $error_messages = array();

  private static $controller_data = array();
  private static $controller_output = "";

  private static $start_time;

  private static $db_num_results = 0;
  private static $db_query_results = array();

  // private constructor, this is a purely static class
  private function __construct() {}
  
  public static function setConfig($config) {
      self::$config = $config;
  }

/*
 * Methods for loading classes from libraries, controllers, and models.
 * ==========================================================================
 */

  /*
   * app::getLib();
   * --------------
   * Return a reference to a library, or create a new one if ! existing.
   */
  public static function &getLib($lib_name) {
    return self::_load_class($lib_name, BASEPATH.'libs', self::$libs);
  }

  /*
   * app::getController();
   * ---------------------
   * Return a reference to a controller or create a new one if ! existing.
   */
  public static function &getController($controller_name) {
    return self::_load_class($controller_name, APPPATH.'controllers', self::$controllers);
  }

  /*
   * app::getModel();
   * ----------------
   * Return a reference to a model or create a new one if ! existing.
   */
  public static function &getModel($model_name) {
    return self::_load_class($model_name, APPPATH.'models', self::$models);
  }

  /*
   * app::_load_class();
   * -------------------
   * Returns a reference to a specific class.
   * If a class instance doesn't already exist, it creates a new one.
   * Loads class from a file if necessary.
   */
  private static function &_load_class($class_name, $class_dir, &$class_array) {
    if( ! array_key_exists($class_name, $class_array)) {
      if (is_file("$class_dir/$class_name".EXT)) {
        include_once "$class_dir/$class_name".EXT;
        $class_array[$class_name] = new $class_name;
        // Call initialize() method if it's defined.
        if (method_exists($class_array[$class_name], 'initialize')) {
          $class_array[$class_name]->initialize();
        }
      } else {
        throw new Exception("Class $class_name not found in $class_dir.");
      }
    }
    return $class_array[$class_name];
  }

/*
 * Methods for dealing with application requests.
 * ==========================================================================
 */

  /*
   * app::setRoutes();
   * ------------------
   * Set routes, merging with existing routes
   */
  public static function setRoutes($new_routes) {
    self::$routes = array_merge(self::$routes, $new_routes);
  }

  /*
   * app::getRoutes();
   * ------------------
   * Return all routes.
   */
  public static function getRoutes() {
    return self::$routes;
  }

  /*
   * app::clearRoutes();
   * ------------------
   * Clear all routes.
   */
  public static function clearRoutes() {
    self::$routes = array();
  }

  /*
   * app::isRoute();
   * ------------------
   * Is the array passed in a route?.
   */
  public static function isRoute() {
    return true;
  }


  /*
   * app::routeMapper();
   * ------------------
   * Return the mapping from route to controller, method & args.
   */
  public static function routeMapper($route) {
    // Initial single segment route match
    $key = FALSE;
    // Extract segments into an array
    $parts = explode('/', $route);
    // Flip values and keys for easy searching
    $route_search = array_flip(self::$routes);
    // Search for route
    if (array_key_exists($route, self::$routes)) {
      // If matched set the parts to the mapped values
      $parts = explode('#', self::$routes[$route]);
    } else {
      // Search through each entry, expanding regexes
      foreach($route_search as $r) {
        // Get the regex for the route
        $route_regex = self::regexRoute($r);
        // Match the route against the URL
        $num_matches = preg_match_all($route_regex, $route, $matches);
        // Are there any matches?
        if ($num_matches > 0) {
          // Yes! Grab the array of parts that matched
          $matches = $matches[1];
          // If it's an array, grab the first match and expand it
          if (is_array($matches))
            $matches = explode('/', $matches[0]);
          // Create the argument string
          $args = implode('#', $matches);
          // Create the string representation of the true route
          $true_route = self::$routes[$r].'#'.$args;
          // Set the parts array to the mapped values
          $parts = explode('#', $true_route);
        }
      }
    }
    // Return the parts, processed or not
    return $parts;
  }

  /*
   * app::regexRoute();
   * ------------------
   * Turns a route specification into a regex string
   */
  public static function regexRoute($route) {
    $expressions = array();
    $replacements = array();
    foreach(self::$conversions as $expression => $regex_value) {
      $expressions[] = $expression;
      $replacements[] = "/$regex_value";
    }
    $regex = preg_replace($expressions, $replacements, $route);
    $regex = "~^$regex$~";
    return $regex;
  }
  
  /*
   * app::mapRoute();
   * ------------------
   * Turns a route into a cannonical route
   */
  public static function mapRoute($url)
  {
    // Get the controller
    $controller = array_shift($url);
    // Get the method
    $method = array_shift($url);
    // Get the params
    $params = array_shift($url);

    // Process the segments, replacing missing segments with default values
    $controller = $controller !== NULL ? $controller : self::$config['default_controller'];
    $method = $method !== NULL ? $method : 'index';
    $params = $params !== NULL ? $params : '';
    $params = is_array($params) ? implode('/', $params) : $params;
    $params = $params === '' ? '' : $params;

    foreach ($params as $arg) {
      $split = explode(':', $arg);
      if (count($split) == 2) {
        self::$named_params[$split[0]] = $split[1];
      }
    }

    // Set the default mapped route
    $mapped_route = '/';
    // Get the route variable place holders
    $placeholders = array_keys(self::$conversions);
    // Assemble the route
    $route = "$controller#$method";
    
    // Search for the route
    $map = array_search($route, self::$routes);
    if ($map) {
    // If there's a match
    /**
     * TODO: actually replace the placeholders in the middle of a route with the right parameters.
     */
      // replace placeholders with empty space
      $temp = preg_replace($placeholders, '', $map);
      // append params to the end
      $mapped_route = $temp.$params;
    } else {
    // If not, just return the full path
      $mapped_route = "$controller/$method/$params";
    }

    // Return the mapped route.
    return $mapped_route;
  }

  /*
   * app::setReqType();
   * ------------------
   * Sets the request type. Normally set by the dispatcher based on the extension.
   */
  public static function setReqType($type) {
    self::$request_type = $type;
  }

  /*
   * app::getReqType();
   * ------------------
   * Return the request type.
   */
  public static function getReqType() {
    return self::$request_type;
  }

  /*
   * app::setStartTime();
   * --------------------
   * Set the start time of the application.
   * Called from bootstrap.php
   */
  public static function setStartTime($time) {
    self::$start_time = $time;
  }

  /*
   * app::getStartTime();
   * ------------------
   * Return the start time.
   */
  public static function getStartTime() {
    return self::$start_time;
  }

  /*
   * app::getElapsedTime();
   * ------------------
   * Return the time elapsed since starting.
   */
  public static function getElapsedTime() {
    return microtime(true) - self::$start_time;
  }

  /*
   * app::dispatchRequest();
   * ------------------------
   * This function handles finding the controller class, calling its methods, and passing arguments.
   */
  public static function dispatchRequest($controller, $method='index', $args=array()) {
    // try loading the specified controller
    // if this throws an exception, our app:exception_handler() will catch it.
    $app_controller = &self::getController($controller);
    // Find out if the controller has the requested method
    if (method_exists($app_controller, $method)) {
      self::$request_controller = $controller;
      self::$request_method = $method;
      // Get a reflection class
      $reflector = new ReflectionMethod($app_controller, $method);
      // Get the number of required arguments for the method parameter
      $num_req_args = $reflector->getNumberOfRequiredParameters();
      if (count($args) >= $num_req_args) {

        // Set the default view, can be changed by the controller
        layout::setSlots( array(
          'content' => self::$request_controller."/".self::$request_method
        ));

        // Start the session
        session_start();

        /*
         * TODO: any hooks for additional processing before calling the controller's method
         */

        ob_start(); // Buffer the controller output
        // We have more than enough parameters for the method, dispatch.
        self::$controller_data = call_user_func_array(array($app_controller,$method), $args);
        self::$controller_output = ob_get_contents(); // Get the output
        ob_end_clean(); // End and clean the buffer

        // Write and close the session
        session_write_close();

        // Set the data for the view.
        layout::setData(self::$controller_data);

      } else {
        // Error, need more params
        throw new Exception ("Error: the method $controller::$method() requires $num_req_args parameters.");
      }
    } else {
      // Error! can't find the method
      throw new Exception( "Error: the controller $controller does not have method $controller::$method().");
    }
  }


/*
 * Methods for dealing with errors and exceptions.
 * ==========================================================================
 */
   
  /*
   * app::error_handler();
   * -------------------------
   * Application wide error handler
   * TODO: load error view
   */
  public static function error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    $error = "";
    
    if (!(error_reporting() & $errno)) {
      // This error code is not included in error_reporting
      return;
    }

    switch ($errno) {
    case E_USER_ERROR:
      $error = <<<ERROR
<b>ERROR</b> [$errno] $errstr<br />\n
Fatal error on line $errline in file $errfile\n:
\n  PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n
ERROR;
      $error .= serialize($errcontext);
      break;

    case E_USER_WARNING:
      $error = "<b>WARNING</b> [$errno] $errstr<br />\n";
      break;

    case E_USER_NOTICE:
      $error = "<b>NOTICE</b> [$errno] $errstr<br />\n";
      break;

    default:
      $error = "Unknown error type: [$errno] $errstr<br />\n";
      break;
    }
    
    self::$error_messages[] = $error;

    /* Don't execute PHP internal error handler */
    return true;
  }

  /*
   * app::exception_handler();
   * -------------------------
   * Application wide exception handler
   */
  public static function exception_handler(Exception $e) {
    $error = <<<ERROR
<pre>
<b>Uncaught Exception Code {$e->getCode()}:</b> {$e->getMessage()}\n
On line {$e->getLine()} in {$e->getFile()}:\n
{$e->getTraceAsString()}
</pre>
ERROR;
    self::$error_messages[] = $error; 
  }

  /*
   * app::shutdown_handler();
   * -------------------------
   * Application wide shutdown handler
   */
  public static function shutdown_handler() {
    if (count(self::$error_messages) > 0) {
      layout::setSlots(array('content' => 'errors/error'));
      layout::overrideSlot('content', 'errors/error');
      // Set the data for the view.
      layout::setData(array('error_messages' => self::$error_messages));
    }
    // Output stored view buffer
    echo layout::render();
    // Output the random shit from the controller
    // **Should probably just write this to a log **
    echo "\n<!--\n".self::$controller_output."\n-->";
    echo "\n<!-- Generated in ".self::getElapsedTime()." seconds. -->";
  }  
/*
 * Methods for dealing with the database.
 * ==========================================================================
 */

  /*
   * app::query_array();
   * -------------------
   * This function retreives a number indexed array of rows (stored in associative array form) from a database query
   */
  public static function query_array($sql_request){
    $a=array();
    $result= mysql_query($sql_request);
    if($result) {
      self::$db_num_results = mysql_num_rows($result);
      while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
        $a[]=$row;
      }
    }
    return $a;
  }

  /*
   * app::query_item();
   * ------------------
   * This function retreives one row from a database query as an associative array
   * Assumption of name: your query will only return one record.
   * If the query returns more than one record, only the first is returned by the function
   */
  public static function query_item($sql_request){
    $row = array();
    $result = mysql_query($sql_request);
    if ($result) {
      self::$db_num_results = mysql_num_rows($result);
      $row = mysql_fetch_array($result, MYSQL_ASSOC);
    }
    return $row;
  }

  /*
   * app::query_ins();
   * -----------------
   * Runs an insert query, returning the result.
   */
  public static function query_ins($sql_insert){
    $result = mysql_query($sql_insert);
    if ($result) {
      self::$db_num_results = mysql_affected_rows();
    }
    return $result;
  }

  /*
   * app::num_results();
   * ------------------
   * Returns the number of results from the last query.
   */
  public static function num_results(){
    return self::$db_num_results;
  }

  /*
   * app::paginate();
   * ------------------
   * Prints paging in a paged view.
   */
  public static function paginate ($page = 0, $show = 10, $params = '') {
    $prev = $page-1;
    $next = $page+1;
    $num_pages = intval(self::$db_num_results/$show);

    if ($num_pages > 0) {
      if ($page > 0): ?>
        <a href="<? echo site_url(array(self::$request_controller, self::$request_method, '/'.$prev.$params)); ?>" style="clear:both;">Previous&nbsp;&lt;&lt;&nbsp;</a>
      <? endif;
      for ($i = 0; $i <= $num_pages; $i++) {
        $page_num = $i + 1;
        if ($i == $page) {
          echo "<span>$page_num</span>";
        } else {
          echo '<span><a href="'.site_url(array(self::$request_controller, self::$request_method, '/'.$i.$params)).'">'.$page_num.'</a>&nbsp</span>';
        }
      }
      if ($page < $num_pages): ?>
        <a href="<? echo site_url(array(self::$request_controller, self::$request_method, '/'.$next.$params)); ?>" style="clear:both;">&nbsp&gt;&gt;&nbsp;Next</a>
      <? endif;
    }
  }

}
