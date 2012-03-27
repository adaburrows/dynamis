<?php
/* Router:
 * This class does the hard work of mapping routes to controller/method/params.
 *==============================================================================
 * -- Version alpha 0.1 --
 * This code is being released under an MIT style license:
 *
 * Copyright (c) 2010 Jillian Ada Burrows
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *------------------------------------------------------------------------------
 * Original Author: Jillian Ada Burrows
 * Email:           jill@adaburrows.com
 * Website:         <http://www.adaburrows.com>
 * Github:          <http://github.com/jburrows>
 * Facebook:        <http://www.facebook.com/jillian.burrows>
 * Twitter:         @jburrows
 *------------------------------------------------------------------------------
 * Use at your own peril! J/K
 * 
 */

class router {
  private static $routes = array();
  private static $secure_routes = array();
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
   * TODO: Implement this!
   */
  public static function isRoute() {
    return true;
  }

  /*
   * router::setSecureRoutes();
   * --------------------------
   * Set secure routes, merges
   */
  public static function setSecureRoutes($secure_routes) {
      self::$secure_routes = $secure_routes;
  }

  /*
   * router::getSecureRoutes();
   * --------------------------
   * Return all secure routes.
   */
  public static function getSecureRoutes() {
    return self::$secure_routes;
  }

  /*
   * router::clearSecureRoutes();
   * ----------------------------
   * Clear all secure routes. Probably not a good idea, but perhaps for testing?
   */
  public static function clearSecureRoutes() {
    self::$secure_routes = array();
  }

  /*
   * router::isSecureRoute();
   * ------------------------
   * Is the array passed in a secure route?.
   */
  public static function isSecureRoute($url) {
    $secure_route = false;
    // Make sure it's an array -- not full proof, but I'm not throwing exceptions yet.
    if(is_string($url)) {
        $url = self::map($url);
    }
    // Add null values to array -- in case there are too few array elements --
    //  and assign the array elements to the desired variables
    list($controller, $method) = ($url+Array(null, null));
    // If null, assign default values
    if($controller == null) $controller = config::get('default_controller');
    if($method == null) $method = config::get('default_method');
    // Check for controller in secure routes array
    if(array_key_exists($controller, self::$secure_routes)){
        // Found it, is it a string or array?
        if(is_string(self::$secure_routes[$controller])) {
            // Strings create secure routes, I'm sure this will to some fun...
            $secure_route = true;
        } else if(is_array(self::$secure_routes[$controller])) {
            // It's array, check if method is in array.
            if(in_array($method, self::$secure_routes[$controller])) {
                // Yep, it's a secure route.
                $secure_route = true;
            }
        }
    }
    return $secure_route;
  }


  /*
   * router::parse();
   * ----------------
   * Return the parsed route and extension.
   */
  public static function parse($full_route) {
    $route = '';
    $type = config::get('default_request_type');

	// Parse named anchors
	$full_route_anchor = explode('#',$full_route);
	if(count($full_route_anchor) > 1) {
		// Currently, we're going to ignore this.
	}
	$full_route = array_shift($full_route_anchor);

	// Parse the get variables
	$route_and_params = explode('?', $full_route);
	if(count($route_and_params) > 1) {
		$get_vars = array();
		parse_str(array_pop($route_and_params), $get_vars);
		$_GET = array_merge($_GET,$get_vars);
	}
	$full_route = array_shift($route_and_params);

    $route_parts = explode('.', $full_route);
    // Get the extension;
    //   this could be problematic if the data just contains a dot; <_<
    //   this should be run through a list of available types. later...
    if(count($route_parts) > 1 ) {
      $type = array_pop($route_parts);
      $route = substr($full_route, 0, -1 * (strlen($type) + 1));
    } else {
      $route = $full_route;
    }
    // Remove leading slash from route,
    // removed by rewrite rules in some cases
    $lslash = strpos($route, '/');
    if (($lslash !== false) && ($lslash == 0)) {
      $route = substr($route, 1);
      // If this is the default route false is not a valid key!
      if(!$route) {$route = '';}
    }
    $pbase = config::get('site_base').'/';
    $base_pos = strpos($route, $pbase);
    if(($base_pos !== false) && ($base_pos == 0)) {
      $route = substr($route, strlen($pbase));
      // If this is the default route false is not a valid key!
      if(!$route) {$route = '';}
    }
    $args = self::map($route);
    // Get the controller off the array
    $controller = array_shift($args);
    // Get the method off the array
    $method = array_shift($args);

    $params = array();
    $named_params = array();
    // Parse all named parameters passed as arguments
    foreach ($args as $arg) {
      $split = explode(':', $arg);
      if (count($split) == 2) {
        $named_params[$split[0]] = $split[1];
      } else {
        $params[] = $arg;
      }
    }

    $secure = self::isSecureRoute( array($controller, $method) );

    return array(
      'route'         => $route,
      'type'          => $type,
      'controller'    => $controller,
      'method'        => $method,
      'secure'        => $secure,
      'params'        => $params,
      'named_params'  => $named_params
    );
  }

  /*
   * router::map();
   * ------------------
   * Return the mapping from route to controller, method & args.
   */
  public static function map($route) {
    // Extract segments into an array to be returned if all route searches fail
    $parts = explode('/', $route);
    // Search for route with no parameters
    if (array_key_exists($route, self::$routes)) {
      // If matched set the parts to the mapped values
      $parts = explode('#', self::$routes[$route]);
    // It wasn't that simple, expand regexes
    } else {
      // Search through each entry, expanding regexes
      foreach(self::$routes as $r => $map) {
        // Match the route against the URL
        // Are there any matches?
        if (preg_match_all(self::regexRoute($r), $route, $matches) > 0) {
          // Yes! Grab the array of parts that matched
          $matches = $matches[1];
          // If it's an array, grab the first match and expand it
          if (is_array($matches))
            $matches = explode('/', $matches[0]);
          // Create the argument string
          $args = implode('#', $matches);
          // Create the string representation of the true route
          $true_route = $map.'#'.$args;
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
   * 
   * TODO: actually replace the placeholders in the middle of a route with the
   *  right parameters, instead of an empty space. Need to find a mapping
   *  between the placeholders and parameters. Perhaps I should use named
   *  parameters so that each placeholder is unique. Would introduce an
   *  interesting caveat in to routing: all parameters inside of a route would
   *  need to be named, but paramters on the end of a route could use the
   *  generic placeholders.
   */
  public static function unmap($url) {
    if(is_string($url))
        $url = explode('/',$url);
    // Get the parts of the URL we need.
    $path = array_slice($url,0,2);
    $params = array_slice($url,2);
    // Assemble the route -- sans parameters
    $route = implode('#', $path);
    // Search for the route
    $map = array_search($route, self::$routes);
    // If there's a match
    if ($map) {
      // Get the route variable place holders
      $placeholders = array_keys(self::$conversions);
      // replace placeholders with empty space
      $temp = preg_replace($placeholders, '', $map);
      $path = explode('/',$temp);
    }
    $mapped_route = implode('/', array_merge_recursive($path,$params));
    // Return the mapped route.
    return $mapped_route;
  }

}
