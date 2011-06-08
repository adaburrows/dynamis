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
