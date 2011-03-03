<?php
/* ============================================================================
 * class dispatcher;
 * -----------------
 * This class kicks everything off by dispatching the url route to the right
 * controllers. 
 * ============================================================================
 */

class dispatcher {

  // Keep track of the controller class
  private $app_controller;

  // Construct the class and get the app in motion
  public function __construct() {
    // Set the global error handler
    set_error_handler( array('app', 'error_handler') );
    // Set the global exception handler
    set_exception_handler( array('app', 'exception_handler') );
    // Set the global shutdown handler
    register_shutdown_function( array('app', 'shutdown_handler') );

    global $config;	// We need access to the config array
    app::setConfig($config);

    $route = "";	// Used to store the route
    $parts = array();	// Used to store the parts of the route

    // Check if a route is set and get its parts
    if (isset($_GET['route'])) {
      $route = $_GET['route'];
    }

    // Check if there's an extension on the end of the route. Used for XML & AJAX requests.
    $type = isset($_GET['ext']) ? $_GET['ext'] : "html";
    app::setReqType($type);

    $parts = app::routeMapper($route);
    
    // Get the controller off the array
    $controller = array_shift($parts);
    $method = array_shift($parts);

    $controller = ($controller !== NULL && $controller !== "") ? $controller : $config['default_controller'];
    $method = ($method !== NULL && $method !== "") ? $method : 'index';

    // Load the $controller's $method, passing in $parts as the parameters.
    app::dispatchRequest($controller, $method, $parts);
  }

}

$dispatch = new dispatcher();
