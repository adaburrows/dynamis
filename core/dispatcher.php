<?php
/* ============================================================================
 * Dispatching code;
 * -----------------
 * This kicks everything off by dispatching the url route to the right
 * controllers. 
 * ============================================================================
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

// Set the global error handler
set_error_handler( array('app', 'error_handler') );
// Set the global exception handler
set_exception_handler( array('app', 'exception_handler') );
// Set the global shutdown handler
register_shutdown_function( array('app', 'shutdown_handler') );

app::setConfig($config);

$route = "";	// Used to store the route
$parts = array();	// Used to store the parts of the route

// Check if a route is set and get its parts
if (isset($_GET['route'])) {
  $route = $_GET['route'];
}
$parts = router::map($route);
// Get the controller off the array
$controller = array_shift($parts);
$method = array_shift($parts);
// If there is no specified $controller or $method use defaults
$controller = ($controller !== NULL && $controller !== "") ? $controller : $config['default_controller'];
$method = ($method !== NULL && $method !== "") ? $method : 'index';

// Check if there's an extension on the end of the route. Used for XML & AJAX requests.
$type = isset($_GET['ext']) ? $_GET['ext'] : $config['default_request_type'];
router::setReqType($type);

// Load the $controller's $method, passing in $parts as the parameters.
router::dispatch($controller, $method, $parts);