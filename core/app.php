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

class app {

  public static $config = array();
  
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

  // Private statics vars for storing class references:
  private static $libs = array();
  private static $controllers = array();
  private static $models = array();
  
  private static $classes = array();

  private static $error_messages = array();

  private static $start_time;

  // private constructor, this is a purely static class
  private function __construct() {}
  
  public static function setConfig($config) {
      self::$config = $config;
  }


/*
 * Methods for loading classes from libraries, controllers, and models.
 * ==========================================================================
 */

  /*
   * app::getLib();
   * --------------
   * Return a reference to a library, or create a new one if ! existing.
   */
  public static function &getLib($lib_name) {
    return self::_load_class($lib_name, BASEPATH.'libs', self::$libs);
  }

  /*
   * app::getController();
   * ---------------------
   * Return a reference to a controller or create a new one if ! existing.
   */
  public static function &getController($controller_name) {
    return self::_load_class($controller_name, APPPATH.'controllers', self::$controllers);
  }

  /*
   * app::getModel();
   * ----------------
   * Return a reference to a model or create a new one if ! existing.
   */
  public static function &getModel($model_name) {
    return self::_load_class($model_name, APPPATH.'models', self::$models);
  }

  /*
   * app::_load_class();
   * -------------------
   * Returns a reference to a specific class.
   * If a class instance doesn't already exist, it creates a new one.
   * Loads class from a file if necessary.
   */
  private static function &_load_class($class_name, $class_dir, &$class_array) {
    if( ! array_key_exists($class_name, $class_array)) {
      if (is_file("$class_dir/$class_name".EXT)) {
        include_once "$class_dir/$class_name".EXT;
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
   * app::getContollerMethods();
   * ---------------------------
   * Returns a list
   */
   public static function getControllerMethods() {
     $controller_names = self::getControllers();
     $controllers = array();
     foreach ($controller_names as $controller) {
       $controllers[$controller] = self::getClassMethods($controller);
     }
     return $controllers;
   }

  /*
   * app::getClassMethods();
   * -----------------------
   * Returns a list of methods in the public, protected and private scopes
   */
   public static function getClassMethods($class_name) {
     $reflector = new ReflectionClass($class_name);
     $methods = array(
       'public'    => $reflector->getMethods(ReflectionMethod::IS_PUBLIC),
       'protected' => $reflector->getMethods(ReflectionMethod::IS_PROTECTED),
       'private'   => $reflector->getMethods(ReflectionMethod::IS_PRIVATE)
     );
     return $methods;
   }

  /*
   * app::getModels();
   * -----------------
   * Returns a list of models
   */
   public static function getModels() {
     self::$models = self::getClassesInDir(APPPATH.'/models');
     return array_keys(self::$models);
   }

  /*
   * app::getControllers();
   * ----------------------
   * Returns a list of controllers
   */
   public static function getControllers() {
     self::$controllers = self::getClassesInDir(APPPATH.'/controllers');
     return array_keys(self::$controllers);
   }

  /*
   * app::getClassesInDir();
   * -----------------------
   * Returns an array of classes, where the keys are the names of the instances 
   */
   public static function getClassesInDir($directory) {
     self::$classes = array();
     $dir = opendir($directory);
     print_r($dir);
     if($dir) {
       while (false !== ($file = readdir($dir))) {
         print_r($file);
         if (is_file($file) == 'file') {
           $file_parts = explode('.', $file);
           print_r($file_parts);
           $extension = array_pop($file_parts);
           print_r($extension);
           if($extension == EXT) {
             $classname = array_shift($file_parts);
             print_r($classname);
             try {
               $this->_load_class($classname, $directory, self::$classes);
             } catch (Exception $e) {
               // Do nothing, classes were not added to the array passed into the above function.
             }
           }
         }
       }
       closedir($dir);
     }
     return self::$classes;
   }


/*
 * Methods for dealing with application requests.
 * ==========================================================================
 */

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
    if (count(self::$error_messages) > 0) {
      layout::setSlots(array('content' => 'errors/error'));
      layout::overrideSlot('content', 'errors/error');
      // Set the data for the view.
      layout::setData(array('error_messages' => self::$error_messages));
    }
    // Output stored view buffer
    echo layout::render();
    // Output the random shit from the controller
    // **Should probably just write this to a log **
//    echo "\n<!--\n".self::$controller_output."\n-->";
//    echo "\n<!-- Generated in ".self::getElapsedTime()." seconds. -->";
  }  

}
