<?php

class router {
  private static $routes = array();
  private static $request_type = "";
  private static $request_controller = "";
  private static $request_method = "";

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

  /*
   * router::dispatchRequest();
   * ------------------------
   * This function handles finding the controller class, calling its methods, and passing arguments.
   */
  public static function dispatch($controller, $method='index', $args=array()) {
    // Start the session
    session_start();
    // try loading the specified controller
    // if this throws an exception, our app::exception_handler() will catch it.
    $app_controller = &app::getController($controller);
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

        /**
         * TODO: any hooks for additional processing before calling the controller's method
         */

        // Parse all named parameters passed as arguments
        foreach ($args as $arg) {
          $split = explode(':', $arg);
          if (count($split) == 2) {
            self::$named_params[$split[0]] = $split[1];
          }
        }

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
   * router::setReqType();
   * ------------------
   * Sets the request type. Normally set by the dispatcher based on the extension.
   */
  public static function setReqType($type) {
    self::$request_type = $type;
  }

  /*
   * router::getReqType();
   * ------------------
   * Return the request type.
   */
  public static function getReqType() {
    return self::$request_type;
  }

  /*
   * router::setRoutes();
   * ------------------
   * Set routes, merging with existing routes
   */
  public static function setRoutes($new_routes) {
    self::$routes = array_merge(self::$routes, $new_routes);
  }

  /*
   * router::getRoutes();
   * ------------------
   * Return all routes.
   */
  public static function getRoutes() {
    return self::$routes;
  }

  /*
   * router::clearRoutes();
   * ------------------
   * Clear all routes.
   */
  public static function clearRoutes() {
    self::$routes = array();
  }

  /*
   * router::isRoute();
   * ------------------
   * Is the array passed in a route?.
   */
  public static function isRoute() {
    return true;
  }


  /*
   * router::map();
   * ------------------
   * Return the mapping from route to controller, method & args.
   */
  public static function map($route) {
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
   * router::regexRoute();
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
   * router::unmap();
   * ------------------
   * Turns a route into a cannonical route
   */
  public static function unmap($url)
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
      $mapped_route = "$temp/$params";
    } else {
    // If not, just return the full path
      $mapped_route = "$controller/$method/$params";
    }

    // Return the mapped route.
    return $mapped_route;
  }


}
