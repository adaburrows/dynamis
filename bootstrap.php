<?php
/* ============================================================================
 * Bootstrap file
 * --------------
 * Loads all the required files for the app to work and gives it a slight kick! 
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

// Require the application configuration file
require_once APPPATH.'config'.EXT;
if (empty($config['timezone'])) {
  $config['timezone'] = 'America/Los_Angeles';
}

// Set up time
date_default_timezone_set($config['timezone']);
$start_time = microtime(true);

if(empty($config['default_request_type'])){
  $config['default_request_type'] = 'html';
}

// Include global utility functions
require_once BASEPATH.'utilities'.EXT;

// Setup extra init tasks if file exists
if(file_exists(APPPATH.'init'.EXT)) {
  require_once APPPATH.'init'.EXT;
}

//Include the core app file, used to power the rest of the process.
if (file_exists(BASEPATH.'core/app'.EXT)) {
  require_once BASEPATH.'core/app'.EXT;
}
// Set the global error handler
set_error_handler( array('app', 'error_handler') );
// Set the global exception handler
set_exception_handler( array('app', 'exception_handler') );
// Set the global shutdown handler
register_shutdown_function( array('app', 'shutdown_handler') );

// Core libraries to load
if(empty ($config['core'])) {
    $config['core'] = array('controller', 'db', 'layout', 'router');
}
// Load core classes that all classes extend
foreach($config['core'] as $class) {
  app::getCore($class);
}

// Setup routes for application 
require_once APPPATH.'routes'.EXT;

// Connect to database
if(isset($config['use_database']) && $config['use_database'] == true) {
  db::connect();
}

app::setStartTime($start_time);
app::setConfig($config);
app::go();