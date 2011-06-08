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
    // Private statics vars for storing class references:
    private static $libraries = array();
    private static $controllers = array();
    private static $models = array();
    private static $core = array();
    private static $classes = array();
    private static $error_messages = array();
    private static $start_time;

    // private constructor, this is a purely static class
    private function __construct() {

    }

    public static function setConfig($config) {
        self::$config = $config;
    }

    /*
     * Methods for loading classes from libraries, controllers, and models.
     * ==========================================================================
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
     * Methods for dealing with application requests.
     * ==========================================================================
     */

    /*
     * app::go();
     * ----------
     * Kicks the app in the rear!
     * Called from bootstrap.php
     */
    public static function go() {
        $route = ""; // Used to store the route
        $parts = array(); // Used to store the parts of the route
        // Check if a route is set and get its parts
        if (isset($_GET['route'])) {
            $route = $_GET['route'];
        }
        $parts = router::map($route);
        // Get the controller off the array
        $controller = array_shift($parts);
        $method = array_shift($parts);
        // If there is no specified $controller or $method use defaults
        $controller = ($controller !== NULL && $controller !== "") ? $controller : self::$config['default_controller'];
        $method = ($method !== NULL && $method !== "") ? $method : 'index';

        // Check if there's an extension on the end of the route. Used for XML & AJAX requests.
        $type = isset($_GET['ext']) ? $_GET['ext'] : self::$config['default_request_type'];
        router::setReqType($type);

        // Load the $controller's $method, passing in $parts as the parameters.
        router::dispatch($controller, $method, $parts);
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
     * ------------------
     * Return the start time.
     */
    public static function getStartTime() {
        return self::$start_time;
    }

    /*
     * app::getElapsedTime();
     * ------------------
     * Return the time elapsed since starting.
     */
    public static function getElapsedTime() {
        return microtime(true) - self::$start_time;
    }

    /*
     * Methods for dealing with errors and exceptions.
     * ==========================================================================
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
        layout::render();
        // Output stored view buffer
        echo layout::getOutputBuffer();
        // Output the random shit from the controller
        // **Should probably just write this to a log **
//    echo "\n<!--\n".self::$controller_output."\n-->";
//    echo "\n<!-- Generated in ".self::getElapsedTime()." seconds. -->";
    }

}