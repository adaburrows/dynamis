<?php
/* Layout:
 * This class is in charge of the layout of the site + rendering views.
 *
 * Every layout is composed of serveral slots that are filled with views.
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

class layout {

  private static $layout = NULL;
  private static $css = array();
  private static $js = array();
  private static $slots = array();
  private static $data = array();
  private static $ob = "";
  private static $temp_ob ="";

  private function __construct () {}

  /*
   * Accepts a key value array in the form of:
   *   array( 'slot_name' => 'view_name' )
   */
  public static function setSlots ($views = array()) {
    self::$slots = array_merge(self::$slots, $views);
  }

  /*
   * Overides a particular slot. 
   */
  public static function overrideSlot($name, $view) {
    $status = false;
    if(array_key_exists($name, self::$slots)) {
      self::$slots[$name] = $view;
      $status = true;
    }
    return $status;
  }

  /*
   * Returns the slot array
   */
  public static function getSlots () {
    return self::$slots;
  }

  /*
   * Sets layout to use for rendering.
   */
  public static function choose ($layout) {
    self::$layout = $layout;
  }

  /*
   * Gets layout chosen for rendering.
   */
  public static function which () {
    return self::$layout;
  }

  public static function addCss ($name, $file) {
    self::$css[$name] = array('file' => $file);
  }

  public static function addCssBlock ($name, $css) {
    self::$css[$name] = array('css' => $css);
  }

  public static function delCss ($name) {
    if (isset(self::$css[$name])) {
      unset(self::$css[$name]);
    }
  }

  public static function buildStyleTags () {
    $style_tags = "";
    foreach (self::$css as $style) {
      if (isset($style['file'])) {
        $style_tags .= '<link rel="stylesheet" type="text/css" href="'.site_url($style['file']).'" />'."\n";
      } else if (isset($style['css'])) {
        $style_tags .= '<style type="text/css">' . $style['css'] . '</script>'."\n";
      }
    } 
    return $style_tags;
  }

  public static function addScript($name, $file) {
    self::$js[$name] = array('file' => $file);
  }

  public static function addScriptBlock ($name, $script) {
    self::$js[$name] = array('script' => $script);
  }

  public static function delScript ($name) {
    if (isset(self::$js[$name])) {
      unset(self::$js[$name]);
    }
  }

  public static function buildScriptTags () {
    $script_tags = '';
    foreach(self::$js as $js) {
      $script_tags .= self::_build_script_tag(
        (isset($js['file']) ? $js['file'] : NULL),	// If the file is set, use it; otherwise NULL
        (isset($js['script']) ? $js['script'] : '')	// If the script is set, use it; otherwise ''
      )."\n";
    }
    return $script_tags;
  }

  private static function _build_script_tag ($file, $code) {
    return '<script type="text/javascript"' . ( !is_null($file) ? ' src="'.$file.'"' : '' ) . " >$code</script>";
  }

  private static function _read_file($filename) {
    if (is_file($filename)) {
      $data = file_get_contents($filename);
    } else {
      throw new Exception("Error: Cannot find file: $filename.");
    }
    return $data;
  }

  public static function setData ($data) {
    self::$data = array_merge(self::$data, $data);
  }

  /*
   * Methods for loading views.
   * ==========================================================================
   */

  /*
   * layout::view();
   * ------------
   * Loads the desired view and fills in the variables
   * Optionally, returns all data in a string.
   */
  public static function view($view_name, $data = array(), $buffer = false) {
    self::$temp_ob = "";
    // Move key value pairs from data into variables in the current scope.
    extract($data);
    // If the view exists load it into an output buffer.
    if (is_file(APPPATH."views/$view_name".EXT)) {
      ob_start();
      include(APPPATH."views/$view_name".EXT);
      // Store the output buffer.
      self::$temp_ob = ob_get_contents();
      ob_end_clean();
    } else {
      // Error! View missing!
      throw new Exception("Error: Could not find view: $view_name");
    }
    // If buffering, return the buffer.
    // If not, just append it to the full buffer.
    if ($buffer) {
      return self::$temp_ob;
    } else {
      $slots['content'] .= self::$temp_ob;
    }
  }

  public static function render() {
    global $config;

    // Get request type
    $request_type = router::getReqType();
    // Render output based on request type
    switch ($request_type) {
      // It's a json request
      case 'json':
        header('Content-Type: application/json');
        self::$ob = json_encode(self::$data);
        break;
      // It's a text request
      case 'text':
        header('Content-Type: plain/text');
        self::$ob = json_encode(self::$data);
        break;
      // The default is the full layout and html
      default:
        $layout_data = array();
        foreach (array('title', 'keywords', 'description') as $data_field) {
          if (isset(self::$data[$data_field]))
            $layout_data[$data_field] = self::$data[$data_field];
        }
        $layout_data['css'] = self::buildStyleTags();
        $layout_data['scripts'] = self::buildScriptTags();
        foreach (self::$slots as $slot => $view) {
          $layout_data[$slot] = self::view($view, self::$data, true);
        }
        extract($layout_data);
        $layout = self::$layout === NULL ? $config['default_layout'] : self::$layout;
        if (is_file(APPPATH."layouts/$layout".EXT)) {
          ob_start();
          include(APPPATH."layouts/$layout".EXT);
          self::$temp_ob = ob_get_contents();
          ob_end_clean();
        } else {
          throw new Exception("Error: Could not find layout $layout in: ".APPPATH."layouts/$layout".EXT);
        }
        self::$ob = self::$temp_ob;
    }
    return self::$ob;
  }

}
