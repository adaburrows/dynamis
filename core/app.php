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
    public static $config = array();

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


    // private constructor, this is a purely static class
    private function __construct() {

    }

    public static function setConfig($config) {
        self::$config = $config;
    }

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
        try {
            $class = self::_load_class($class_name, APPPATH . 'core', self::$core);
        } catch (Exception $exc) {
            $class = self::_load_class($class_name, BASEPATH . 'core', self::$core);
        }
        return $class;
    }

    /*
     * app::getLib();
     * --------------
     * Return a reference to a library, or create a new one if ! existing.
     */
    public static function &getLib($lib_name) {
        return self::_load_class($lib_name, BASEPATH . 'libs', self::$libraries);
    }

    /*
     * app::getController();
     * ---------------------
     * Return a reference to a controller or create a new one if ! existing.
     */
    public static function &getController($controller_name) {
        return self::_load_class($controller_name, APPPATH . 'controllers', self::$controllers);
    }

    /*
     * app::getModel();
     * ----------------
     * Return a reference to a model or create a new one if ! existing.
     */
    public static function &getModel($model_name) {
        return self::_load_class($model_name, APPPATH . 'models', self::$models);
    }

    /*
     * app::_load_class();
     * -------------------
     * Returns a reference to a specific class.
     * If a class instance doesn't already exist, it creates a new one.
     * Loads class from a file if necessary.
     */
    private static function &_load_class($class_name, $class_dir, &$class_array) {
        if (!array_key_exists($class_name, $class_array)) {
            if (is_file("$class_dir/$class_name" . EXT)) {
                include_once "$class_dir/$class_name" . EXT;
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
                $clases = self::getCores();
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
        $route = ""; // Used to store the route

        // Check if a route is set and get its parts
        if (isset($_GET['route'])) {
            $route = $_GET['route'];
        }
        self::$params = router::map($route);
        // Get the controller off the array
        $controller = array_shift(self::$params);
        // Get the method off the array
        $method = array_shift(self::$params);
        // If there is no specified $controller or $method use defaults
        self::$controller = ($controller !== NULL && $controller !== "") ? $controller : self::$config['default_controller'];
        self::$method = ($method !== NULL && $method !== "") ? $method : 'index';

        // Check if there's an extension on the end of the route. Used for XML & AJAX requests.
        $type = isset($_GET['ext']) ? $_GET['ext'] : self::$config['default_request_type'];
        self::setReqType($type);

        // Load the $controller's $method, passing in $parts as the parameters.
        self::dispatch();
    }

    /*
     * app::dispatch();
     * ----------------
     * This function handles finding the controller class, calling its methods, and passing arguments.
     */
    public static function dispatch() {
        // Start the session
        session_start();
        // try loading the specified controller
        // if this throws an exception, our app::exception_handler() will catch it.
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
                // Parse all named parameters passed as arguments
                foreach (self::$params as $arg) {
                    $split = explode(':', $arg);
                    if (count($split) == 2) {
                        self::$named_params[$split[0]] = $split[1];
                    }
                }
                ob_start(); // Buffer the controller output
                // We have more than enough parameters for the method, dispatch.
                self::$controller_data = call_user_func_array(array($app_controller, self::$method), self::$params);
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
                foreach (layout::getSlots() as $slot => $view) {
                    self::setData(array($slot => layout::view($view, self::$controller_data, true)));
                }
                // If any error messages have accumulated, show them.
                if (self::hasErrorMessages()) {
                    // Set the data for the error messages
                    self::setData(array('content' => layout::error(self::getErrorMessages())));
                }
                self::$controller_data['css'] = layout::buildStyleTags();
                self::$controller_data['scripts'] = layout::buildScriptTags();
                $layout = layout::which() === NULL ? self::$config['default_layout'] : layout::which();
                try {
                    $temp_ob = layout::layout($layout, self::$controller_data);
                } catch(Exception $e) {
                    $temp_ob = layout::distribution_layout($layout, self::$controller_data);
                }
                self::$ob = $temp_ob;
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
            return;
        }

        switch ($errno) {
            case E_USER_ERROR:
                $error = "
<b>ERROR</b> [$errno] $errstr<br />\n
Fatal error on line $errline in file $errfile\n:
\n  PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                $error .= serialize($errcontext);
                break;

            case E_USER_WARNING:
                $error = "
<b>WARNING</b> [$errno] $errstr<br />\n
Fatal error on line $errline in file $errfile\n:
\n  PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                $error .= serialize($errcontext);
                break;

            case E_USER_NOTICE:
                $error = "
<b>NOTICE</b> [$errno] $errstr<br />\n
Fatal error on line $errline in file $errfile\n:
\n  PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                $error .= serialize($errcontext);
                break;

            default:
                $error = "
<b>ERROR</b> [$errno] $errstr<br />\n
Fatal error on line $errline in file $errfile\n:
\n  PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
                $error .= serialize($errcontext);
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
        // Render output
        self::render();
        // Output stored view buffer
        echo self::getOutputBuffer();
        // Output the random shit from the controller
        // **Should probably just write this to a log **
//    echo "\n<!--\n".self::$controller_output."\n-->";
//    echo "\n<!-- Generated in ".self::getElapsedTime()." seconds. -->";
    }

}