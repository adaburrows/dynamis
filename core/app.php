<?php

/* ============================================================================
 * class app;
 * ----------
 * This is the factory class for Dynamos
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
 * ------------------------------------------------------------------------------
 * Original Author: Jillian Ada Burrows
 * Email:           jill@adaburrows.com
 * Website:         <http://www.adaburrows.com>
 * Github:          <http://github.com/jburrows>
 * Facebook:        <http://www.facebook.com/jillian.burrows>
 * Twitter:         @jburrows
 * ------------------------------------------------------------------------------
 * Use at your own peril! J/K
 *
 */

class app {
    public static $is_secure_url = false;

    protected static $request_type = "";
    protected static $controller = "";
    protected static $method = "";
    protected static $params = array();
    protected static $named_params = array();

    // Private statics vars for storing class references:
    private static $libraries = array();
    private static $controllers = array();
    private static $models = array();
    private static $core = array();
    private static $classes = array();
    private static $error_messages = array();
    private static $start_time;
    private static $controller_data = array();
    private static $controller_output = "";
    private static $ob = "";

    // Do not allow constructing an object out of this static class
    private function __constructor() {}

/*
 * Methods for loading classes from libraries, controllers, and models.
 * =============================================================================
 */

    /*
     * app::getCore();
     * --------------
     * Return a reference to a library, or create a new one if ! existing.
     */
    public static function &getCore($class_name) {
        $class = null;
        if (is_array($class_name)) {
          foreach ($class_name as $cname) {
            self::getCore($cname);
          }
        } else {
          try {
            $class = self::_load_class($class_name, APPPATH . 'core', self::$core);
          } catch (Exception $exc) {
            $class = self::_load_class($class_name, BASEPATH . 'core', self::$core);
          }
        }
        return $class;
    }

    /*
     * app::getLib();
     * --------------
     * Return a reference to a library, or create a new one if ! existing.
     */
    public static function &getLib($lib_name) {
        $lib = null;
        if (is_array($lib_name)) {
          foreach ($lib_name as $cname) {
            self::getLib($cname);
          }
        } else {
          try {
            $lib_path = BASEPATH . 'libs';
            if (is_file(APPPATH . 'libs/' . $lib_name . EXT)) {
                $model_path = APPPATH . 'libs';
            }
            $lib = self::_load_class($lib_name, $lib_path, self::$libraries);
          } catch (Exception $e) {
            self::exception_handler($e);
            try {
              $lib = self::_load_class('unknownLib', BASEPATH . 'libs', self::$libraries);
            } catch (Exception $e) {
              self::exception_handler($e);
            }
          }
        }
        return $lib;
    }

    /*
     * app::getController();
     * ---------------------
     * Return a reference to a controller or create a new one if ! existing.
     */
    public static function &getController($controller_name) {
        $controller = null;
        if (is_array($controller_name)) {
          foreach ($controller_name as $cname) {
            self::getController($cname);
          }
        } else {
          try {
            $controller_path = BASEPATH . 'controllers';
            if (is_file(APPPATH . 'controllers/' . $controller_name . EXT)) {
               $controller_path = APPPATH . 'controllers';
            }
            $controller = self::_load_class($controller_name, $controller_path, self::$controllers);
          } catch (Exception $e) {
            self::exception_handler($e);
            try {
              $controller = self::_load_class('unknownController', BASEPATH . 'controllers', self::$controllers);
            } catch (Exception $e) {
              self::exception_handler($e);
            }
          }
        }
        return $controller;
    }

    /*
     * app::getModel();
     * ----------------
     * Return a reference to a model or create a new one if ! existing.
     */
    public static function &getModel($model_name) {
        $model = null;
        if(is_array($model_name)) {
          foreach ($model_name as $modelname) {
            self::getModel($modelname);
          }
        } else {
          try {
            $model_path = BASEPATH . 'models';
            if (is_file(APPPATH . 'models/' . $model_name . EXT)) {
              $model_path = APPPATH . 'models';
            }
            $model = self::_load_class($model_name, $model_path, self::$models);
          } catch (Exception $e) {
            self::exception_handler($e);
            try {
              $model = self::_load_class('unknownModel', BASEPATH . 'models', self::$models);
            } catch (Exception $e) {
              self::exception_handler($e);
            }
          }
        }
        return $model;
    }

    /*
     * app::_load_class();
     * -------------------
     * Returns a reference to a specific class.
     * If a class instance doesn't already exist, it creates a new one.
     * Loads class from a file if necessary.
     */
    private static function &_load_class($class_name_file, $class_dir, &$class_array) {
        $class_name_parts = explode('/', $class_name_file);
        if(count($class_name_parts) > 1) {
            $class_name = array_pop($class_name_parts);
        } else {
            $class_name = $class_name_file;
        }
        if (!array_key_exists($class_name, $class_array)) {
            if(config::get('debug')) {
                self::log("Loading class $class_name from $class_dir/$class_name_file:");
            }
            if (is_file("$class_dir/$class_name_file" . EXT)) {
                include_once "$class_dir/$class_name_file" . EXT;
                $class_array[$class_name] = new $class_name;
                // Call initialize() method if it's defined.
                if (method_exists($class_array[$class_name], 'initialize')) {
                    $class_array[$class_name]->initialize();
                }
                if(config::get('debug')) {
                    self::log("Done.\n");
                }
            } else {
                if(config::get('debug')) {
                    self::log("Class $class_name not found.\n");
                }
                throw new Exception("Class $class_name not found in $class_dir/$class_name_file.");
            }
        }
        return $class_array[$class_name];
    }

    /*
     * app::getClassMethods();
     * ----------------------------
     * Returns a list of controller and their methods.
     */
    public static function getClassMethods($type = 'controllers') {
        $classes = array();
        switch ($type) {
            case 'controllers':
                $classes = self::getControllers();
                break;
            case 'models':
                $classes = self::getModels();
                break;
            case 'libraries':
                $classes = self::getLibraries();
                break;
            case 'core':
                $classes = self::getCores();
        }
        $classes_methods = array();
        foreach ($classes as $class) {
            $classes_methods[$class] = self::getMethods($class);
        }
        return $classes_methods;
    }

    /*
     * app::getMethods();
     * ------------------
     * Returns a list of methods in the public, protected and private scopes
     */
    public static function getMethods($class_name) {
        $reflector = new ReflectionClass($class_name);
        $methods = array(
            'public' => self::filterMethods($reflector->getMethods(ReflectionMethod::IS_PUBLIC)),
            'protected' => self::filterMethods($reflector->getMethods(ReflectionMethod::IS_PROTECTED)),
            'private' => self::filterMethods($reflector->getMethods(ReflectionMethod::IS_PRIVATE))
        );
        return $methods;
    }

    /*
     * app::filterMethods();
     * ---------------------
     * Filters the list of methods so there is only an array of function names
     */
    protected static function filterMethods($unfiltered) {
        $filtered = array();
        foreach ($unfiltered as $method) {
            $filtered[] = $method->name;
        }
        return $filtered;
    }

    /*
     * app::getModels();
     * -----------------
     * Returns a list of models
     */
    public static function getModels() {
        self::$models = self::getClassesInDir(APPPATH . 'models');
        return array_keys(self::$models);
    }

    /*
     * app::getControllers();
     * ----------------------
     * Returns a list of controllers
     */
    public static function getControllers() {
        self::$controllers = self::getClassesInDir(APPPATH . 'controllers');
        return array_keys(self::$controllers);
    }

    /*
     * app::getLibraries();
     * --------------------
     * Returns a list of libraries
     */
    public static function getLibraries() {
        self::$libraries = self::getClassesInDir(BASEPATH . 'libs');
        return array_keys(self::$libraries);
    }

    /*
     * app::getCores();
     * --------------------
     * Returns a list of core classes
     */
    public static function getCores() {
        self::$core = self::getCodeFilesInDir(BASEPATH . 'core');
        return array_keys(self::$core);
    }

    /*
     * app::getClassesInDir();
     * -----------------------
     * Returns an array of classes, where the keys are the names of the instances
     */
    public static function getClassesInDir($directory) {
        self::$classes = array();
        $classes = self::getCodeFilesInDir($directory);
        foreach ($classes as $classname) {
            try {
                self::_load_class($classname, $directory, self::$classes);
            } catch (Exception $e) {
                // Do nothing, classes were not added to the array passed into the above function.
            }
        }
        return self::$classes;
    }

    /*
     * app::_read_file();
     * ---------------------
     * Reads a file off disk
     */
    private static function _read_file($filename) {
        if (is_file($filename)) {
            $data = file_get_contents($filename);
        } else {
            throw new Exception("Error: Cannot find file: $filename.");
        }
        return $data;
    }

    /*
     * app::getCodeFilesInDir();
     * -----------------------
     * Returns an array of classes, where the keys are the names of the instances
     */
    public static function getCodeFilesInDir($directory) {
        $files = array();
        $dir = opendir($directory);
        if ($dir) {
            while (false !== ($file = readdir($dir))) {
                $path = "$directory/$file";
                if (is_file($path)) {
                    $file_parts = explode('.', $file);
                    $extension = array_pop($file_parts);
                    $filename = array_shift($file_parts);
                    // This is the right type of file try it
                    if (".$extension" == EXT) {
                        $files[] = $filename;
                    }
                }
            }
            closedir($dir);
        }
        return $files;
    }

    /*
     * app::setData();
     * ---------------
     * Sets the data to be rendered as HTML, JSON, or XML
     */
    public static function setData($data) {
        self::$controller_data = array_merge(self::$controller_data, $data);
    }

    /*
     * app::setText();
     * ---------------
     * Sets the text to return on text requests
     */
    public static function setText($text) {
        self::$controller_output = $text;
    }

/*
 * Methods for dealing with application requests.
 * =============================================================================
 */

    /*
     * app::go();
     * ----------
     * Kicks the app in the rear!
     * Called from bootstrap.php
     */
    public static function go() {
        $parsed = array();
        $full_route = '';

        /*
         * Get the full route somehow
         */
        // IF the server has the request uri set, use it
        if(isset($_SERVER['REQUEST_URI'])) {
          $full_route = $_SERVER['REQUEST_URI'];
        // IF there is no REQUEST_URI key,
        //    get it from the GET vars from the rewrite rules.
        } else {
          if(isset($_GET['ext'])) {
            $extension = $_GET['ext'];
          }
          // Check if a route is set and get its parts
          if (isset($_GET['route'])) {
            $route = $_GET['route'];
          }
          $full_route = $route.'.'.$extension;
        }

        if(isset($_SERVER['REQUEST_METHOD'])) {
          $http_verb = $_SERVER['REQUEST_METHOD'];
        }

        // Parse full route
        $parsed = router::parse($full_route);
        self::setReqType($parsed['type']);

        // If url should be secure and it's not, redirect to secure url.
        self::$is_secure_url = $parsed['secure']
                                || ($_SERVER['SERVER_PORT'] == '443');

        if(self::$is_secure_url && ($_SERVER['SERVER_PORT'] != '443')) {
            $url = self::site_url($parsed['route']);
            if(self::$request_type != 'html')
                $url .= ".".self::$request_type;
            header("Location: {$url}");
            exit();
        }

        // Load the $controller's $method, passing in $parts as the parameters.
        self::dispatch($parsed['controller'], $parsed['method'], $parsed['params'], $parsed['named_params']);
    }

    /*
     * app::dispatch();
     * ----------------
     * This function handles finding the controller class, calling its methods, and passing arguments.
     */
    public static function dispatch($controller, $method, $params, $named_params) {
        // If there is no specified $controller or $method use defaults
        self::$controller = ($controller !== NULL && $controller !== "") ? $controller : config::get('default_controller');
        self::$method = ($method !== NULL && $method !== "") ? $method : config::get('default_method');
        self::$params = $params;
        self::$named_params = $named_params;

        // Start the session
        session_start();
        // try loading the specified controller
        // if this throws an exception, catch it and create a new blank class.
        $app_controller = &self::getController(self::$controller);
        // Find out if the controller has the requested method
        if (method_exists($app_controller, self::$method)) {
            // Get a reflection class
            $reflector = new ReflectionMethod($app_controller, self::$method);
            // Get the number of required arguments for the method parameter
            $num_req_args = $reflector->getNumberOfRequiredParameters();
            if (count(self::$params) >= $num_req_args) {
                // Set the default view, can be changed by the controller
                layout::setSlots(array(
                    'content' => self::$controller . "/" . self::$method
                ));
                /**
                 * TODO: any hooks for additional processing before calling the controller's method
                 */
                ob_start(); // Buffer the controller output
                // We have more than enough parameters for the method, dispatch.
                self::setData(
                        call_user_func_array(
                                array($app_controller, self::$method),
                                self::$params
                        )
                );
                self::$controller_output = ob_get_contents(); // Get the output
                ob_end_clean(); // End and clean the buffer
                // Write and close the session
                session_write_close();
            } else {
                // Error, need more params
                throw new Exception('Error: the method '.self::$controller.'::'.self::$method."() requires $num_req_args parameters.");
            }
        } else {
            // Error! can't find the method
            throw new Exception('Error: the controller '.self::$controller.' does not have method '.self::$controller.'::'.self::$method.'().');
        }
    }


    /**
     * Returns the proper site url relative to the base url for the specified resource.
     * Use in all views to retain portability across domains.
     */
    public static function site_url($url) {
      // Is this supposed to be a secure URL?
      $secure_url = self::$is_secure_url;
      if (is_array($url)) {
        $secure_url = router::isSecureRoute($url);
        $url = router::unmap($url);
      }
      $proto = $secure_url ? 'https://' : 'http://';
      $host = config::get('site_host') != '' ? config::get('site_host') : $_SERVER['SERVER_NAME'];
      $base = config::get('site_base') != '' ? '/'.config::get('site_base') : '';
      return("{$proto}{$host}{$base}/{$url}");
    }

    /**
     * Return the proper http:// or https:// protocol
     */
    public static function http_s($url) {
      // Is this supposed to be a secure URL?
      $secure_url = self::$is_secure_url;
      $proto = $secure_url ? 'https://' : 'http://';
      return("{$proto}{$url}");
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
     * app::getController();
     * ---------------------
     * Returns the current controller of the request
     */
    public static function getReqController() {
        return self::$controller;
    }

    /*
     * app::getMethod();
     * -----------------
     * Return the current method of the request
     */
    public static function getReqMethod() {
        return self::$method;
    }

    /*
     * app::getNamedParams();
     * ----------------------
     * Return the current method of the request
     */
    public static function getNamedParams() {
        return self::$named_params;
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
     * --------------------
     * Return the start time.
     */
    public static function getStartTime() {
        return self::$start_time;
    }

    /*
     * app::getElapsedTime();
     * ----------------------
     * Return the time elapsed since starting.
     */
    public static function getElapsedTime() {
        return microtime(true) - self::$start_time;
    }

    /*
     * app::render();
     * --------------
     * Renders the pages if they need to be rendered.
     * Fetches error views/layouts if needed.
     */
    public static function render() {
        // Render output based on request type
        switch (self::getReqType()) {
            // It's a json request
            case 'json':
                header('Content-Type: application/json');
                self::$ob = json_encode(self::$controller_data);
                break;
            // It's a text request
            case 'text':
                $content = self::$controller_output;
                header('Content-Type: text/plain');
                header('Content-Length: ' . strlen($content));
                self::$ob = $content;
                break;
            // The default is the full layout and html
            default:
                // If we haven't set a layout use the default
                $layout = layout::which() === NULL ? config::get('default_layout') : layout::which();
                layout::choose($layout);
                // If any error messages have accumulated, show them.
                if (self::hasErrorMessages()) {
                    // Set the data for the error messages
                    self::$controller_data['content'] = layout::error(self::getErrorMessages());
                }
                // Set the data
                layout::setData(self::$controller_data);
                // Render it all!
                self::$ob = layout::render();
        }
    }

    /*
     * app::getOutputBuffer();
     * -----------------------
     * Returns the output buffer for display
     */
    public static function getOutputBuffer() {
        return self::$ob;
    }

/*
 * Methods for dealing with errors, exceptions, and shutdown.
 * =============================================================================
 */

   /*
     * app::log();
     * -----------
     *  Logs error messages
     */
    public static function log($message) {
        error_log($message,3,APPPATH.'error.log');
    }

    /*
     * app::hasErrorMessages();
     * -------------------------
     * Returns true if there are any error messages
     */
    public static function hasErrorMessages() {
        return (count(self::$error_messages) > 0);
    }

    /*
     * app::getErrorMessages();
     * -------------------------
     * Returns the error messages in an application
     */
    public static function getErrorMessages() {
        return self::$error_messages;
    }

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
            return true;
        }

        switch ($errno) {
            case E_USER_ERROR:
                $error = "<b>ERROR</b> [{$errno}] {$errstr}<br />: On line {$errline} in file {$errfile}e";
                break;

            case E_USER_WARNING:
                $error = "<b>WARNING</b> [{$errno}] {$errstr}<br />: On line {$errline} in file {$errfile}";
                break;

            case E_USER_NOTICE:
                $error = "<b>NOTICE</b> [{$errno}] {$errstr}<br />: On line {$errline} in file {$errfile}";
                break;

            default:
                $error = "<b>ERROR</b> [{$errno}] {$errstr}<br />: On line {$errline} in file {$errfile}";
                break;
        }

        $error .= htmlentities(serialize($errcontext));
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
        // Render output
        self::render();
        // Output stored view buffer
        echo self::getOutputBuffer();
        app::log("\n".self::$controller_output."\n");
        app::log("Generated in ".self::getElapsedTime()." seconds.\n");
    }

}