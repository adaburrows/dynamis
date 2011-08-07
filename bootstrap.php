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

// Include config class - dependancy of eveything
require_once BASEPATH.'core/config'.EXT;
$start_time = config::load();

// Include router - dependancy of app.
require_once BASEPATH.'core/router'.EXT;
// Setup routes for application
require_once APPPATH.'routes'.EXT;

// Include layout - dependancy of app.
require_once BASEPATH.'core/layout'.EXT;

// Include app
require_once BASEPATH.'core/app'.EXT;
app::setConfig(config::getAll());
app::setStartTime($start_time);

// Set the global error handler
set_error_handler( array('app', 'error_handler') );
// Set the global exception handler
set_exception_handler( array('app', 'exception_handler') );
// Set the global shutdown handler
register_shutdown_function( array('app', 'shutdown_handler') );

// Load core classes that all classes extend
$core = config::get('core');
if($core){
    foreach($core as $class) {
        if (file_exists(APPPATH."core/$class".EXT)) {
            require_once APPPATH."core/$class".EXT;
        } else if (file_exists(BASEPATH."core/$class".EXT)) {
            require_once BASEPATH."core/$class".EXT;
        }
    }
}

// Connect to database
if(config::get('use_database')) {
    if(is_file(APPPATH.'models/schema/aspects'.EXT)) {
        require_once APPPATH.'models/schema/aspects'.EXT;
    }
    db::connect();
}

app::getCore('controller');
app::go();