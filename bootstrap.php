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

function load_files($files) {
    // Load core classes that all classes extend
    foreach($files as $file) {
        if (file_exists(APPPATH."$file".EXT)) {
            require_once APPPATH."$file".EXT;
        } else if (file_exists(BASEPATH."$file".EXT)) {
            require_once BASEPATH."$file".EXT;
        }
    }
}

// Require the application configuration file
require_once BASEPATH.'core/config'.EXT;
$start_time = config::load();

// Dependancies required to run
if(!config::get('deps')) {
    config::set('deps', array('core/util', 'core/router','core/layout','core/app'));
}
// Core classes to load
if(!config::get('core')) {
    config::set('core', array('aspects'));
}

// Libraries to load
if(!config::get('libs')) {
  config::set('libs', array('db/db'));
}

// Models to load
if(!config::get('models')) {
  config::set('models', array('model', 'db_model'));
}

// Controllers to load
if(!config::get('controllers')) {
  config::set('controllers', array('controller'));
}

// Load all dependancies
load_files(config::get('deps'));

// Set up configuration and default runtime handlers
app::setStartTime($start_time);
// Set the global error handler
set_error_handler( array('app', 'error_handler') );
// Set the global exception handler
set_exception_handler( array('app', 'exception_handler') );
// Only set the shutdown function if we are not running embedded
if (!config::get('embedded')) {
  // Set the global shutdown handler
  register_shutdown_function( array('app', 'shutdown_handler') );
}

// Load the core library files
app::getCore(config::get('core'));

// Load the libraries
app::getLib(config::get('libs'));

// Load the model files
app::getModel(config::get('models'));

// Load the controllers
app::getController(config::get('controllers'));

// Setup routes for application 
require_once APPPATH.'routes'.EXT;

// Connect to database
if(config::get('use_database')) {
    aspects::load();
    db::connect();
}

// Start the app
app::go();
