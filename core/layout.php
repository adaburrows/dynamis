<?php

/* Layout:
 * This class is in charge of the layout of the site + rendering views.
 *
 * Every layout is composed of serveral slots that are filled with views.
 * ==============================================================================
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

class layout {

    private static $layout = NULL;
    private static $css = array();
    private static $js = array();
    private static $meta = array();
    private static $slots = array();
    private static $data = array();
    private static $text = "";
    private static $temp_ob = "";

    private function __construct() {
        
    }

    /*
     * layout::setSlots();
     * -------------------
     * Accepts a key value array in the form of:
     *   array( 'slot_name' => 'view_name' )
     */
    public static function setSlots($views = array()) {
        self::$slots = array_merge(self::$slots, $views);
    }

    /*
     * layout::overideSlot();
     * ----------------------
     * Overides a particular slot.
     */
    public static function overrideSlot($name, $view) {
        $status = false;
        if (array_key_exists($name, self::$slots)) {
            self::$slots[$name] = $view;
            $status = true;
        }
        return $status;
    }

    /*
     * layout::getSlots();
     * -------------------
     * Returns the slot array
     */
    public static function getSlots() {
        return self::$slots;
    }

    /*
     * layout::choose();
     * -----------------
     * Sets layout to use for rendering.
     */
    public static function choose($layout) {
        self::$layout = $layout;
    }

    /*
     * layout::which();
     * ----------------
     * Return the current layout chosen for rendering.
     */
    public static function which() {
        return self::$layout;
    }

    /*
     * layout::setData();
     * -----------------
     * Sets the data array.
     */
    public static function setData($data) {
        self::$data = $data;
    }

    /*
     * layout::getData();
     * -----------------
     * Gets the data array.
     */
    public static function getData() {
        return self::$data;
    }

    /*
     * layout::addMeta();
     * ------------------
     * Adds a meta tag
     */
    public static function addMeta($meta) {
        self::$meta[] = $meta;
    }


    /*
     * layout::buildMetaTags();
     * ------------------------
     * Builds the meta tags and reuturns a string representation
     */
    public static function buildMetaTags() {
        $meta_tags = '';
        foreach (self::$meta as $meta_datum) {
            $meta_tags .= '<meta ';
            foreach ($meta_datum as $attribute => $value) {
                $meta_tags .= $attribute . '="' . $value .'" ';
            }
            $meta_tags .= '/>';
        }
    }

    /*
     * layout::addCSS();
     * -----------------
     * Adds a named CSS file.
     */
    public static function addCss($name, $file) {
        self::$css[$name] = array('file' => $file);
    }

    /*
     * layout::addCssBlock();
     * ----------------------
     * Adds a named block of css.
     */
    public static function addCssBlock($name, $css) {
        self::$css[$name] = array('css' => $css);
    }

    /*
     * layout::delCss();
     * -----------------
     * Removes CSS from the queue by name.
     */
    public static function delCss($name) {
        if (isset(self::$css[$name])) {
            unset(self::$css[$name]);
        }
    }

    /*
     * layout::getCss();
     * -------------------
     * Gets CSS
     */
    public static function getCss() {
        return self::$css;
    }

    /*
     * layout::buildStyleTags();
     * -------------------------
     * Creates an HTML partial of all CSS
     */
    public static function buildStyleTags() {
        $style_tags = "";
        foreach (self::$css as $style) {
            if (isset($style['file'])) {
                $style_tags .= '<link rel="stylesheet" type="text/css" href="' . $style['file'] . '" />' . "\n";
            } else if (isset($style['css'])) {
                $style_tags .= '<style type="text/css">' . $style['css'] . '</style>' . "\n";
            }
        }
        return $style_tags;
    }

    /*
     * layout::addSCript();
     * --------------------
     * Add a named script to the queue.
     * $file must be a full path.
     */
    public static function addScript($name, $file) {
        self::$js[$name] = array('file' => $file);
    }

    /*
     * layout::addScriptBlock();
     * -------------------------
     * Add a named block of javascript to the queue
     */
    public static function addScriptBlock($name, $script) {
        self::$js[$name] = array('script' => $script);
    }

    /*
     * layout::delScript();
     * -------------------
     * Removes a script from the queue by name
     */
    public static function delScript($name) {
        if (isset(self::$js[$name])) {
            unset(self::$js[$name]);
        }
    }

    /*
     * layout::getScripts();
     * -------------------
     * Get Scripts
     */
    public static function getScripts() {
        return self::$js;
    }

    /*
     * layout::buildScriptTags();
     * --------------------------
     * Builds an HTML fragment containing all script tags
     */
    public static function buildScriptTags() {
        $script_tags = '';
        foreach (self::$js as $js) {
            $script_tags .= self::_build_script_tag(
                            (isset($js['file']) ? $js['file'] : NULL), // If the file is set, use it; otherwise NULL
                            (isset($js['script']) ? $js['script'] : '') // If the script is set, use it; otherwise ''
                    ) . "\n";
        }
        return $script_tags;
    }

    /*
     * layout::_build_script_tag();
     * ----------------------------
     * Builds an html fragment to add a script
     */
    private static function _build_script_tag($file, $code) {
        return '<script type="text/javascript"' . (!is_null($file) ? ' src="' . $file . '"' : '' ) . " >$code</script>";
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
        // If the view exists load it into an output buffer.
        try {
            self::$temp_ob = self::load_template(APPPATH . "views/$view_name" . EXT, $data);
        } catch(Throwable $e) {
            // Error! View missing!
            app::exception_handler(new Exception("Error: Could not find view: $view_name"));
        }
        // If buffering, return the buffer.
        // If not, just append it to the full buffer.
        if ($buffer) {
            return self::$temp_ob;
        } else {
            self::$slots['content'] .= self::$temp_ob;
        }
    }

    /*
     * layout::layout();
     * -----------------
     * Loads the desired layout and fills in the variables
     * Optionally, returns all data in a string.
     */
    public static function layout($layout, $data = array()) {
        try {
            self::$temp_ob = self::load_template(APPPATH . "layouts/$layout" . EXT, $data);
        } catch (Throwable $e) {
            throw new Exception("Error: Could not find layout $layout in: " . APPPATH . "layouts/$layout" . EXT);
        }
        return self::$temp_ob;
    }

    /*
     * layout::error();
     * ----------------
     * Loads the error view, fails gracefully.
     */
    public function error($errors) {
        try {
            self::$temp_ob = self::load_template(
                    APPPATH . "views/errors/error" . EXT,
                    array('error_messages' => $errors)
            );
        } catch (Throwable $e) {
            try {
                self::$temp_ob = self::load_template(
                        BASEPATH . "default/views/errors/error" . EXT,
                        array('error_messages' => $errors)
                );
            } catch (Throwable $f) {
                self::$temp_ob = "System Error! Could not find built-in error view. Please re-install Dynamis.";
            }
        }
        return self::$temp_ob;
    }

    /*
     * layout::distribution_layout();
     * ------------------------------
     * Loads the default layout that comes with the Dyanmis core.
     */
    public static function distribution_layout($data = array()) {
        try { 
            self::$temp_ob = self::load_template(BASEPATH . "default/layouts/default" . EXT, $data);
        } catch (Throwable $e) {
            self::$temp_ob = "System Error! Could not find built-in default layout. Please re-install Dynamis.";
        }
        return self::$temp_ob;
    }

    /*
     * layout::load_template();
     * ------------------------
     * Loads a template file.
     */
    public static function load_template($file, $data = array()) {
        if (is_file($file)) {
            extract($data);
            ob_start();
            include($file);
            self::$temp_ob = ob_get_contents();
            ob_end_clean();
        } else {
            throw new Exception("Could not load file: $file");
        }
        return self::$temp_ob;
    }

    /*
     * layout::render();
     * -----------------
     * Renders the template and each of the views in it's slots.
     */
    public static function render() {
        self::$data['meta'] = self::buildMetaTags();
        self::$data['css'] = self::buildStyleTags();
        self::$data['scripts'] = self::buildScriptTags();
        foreach (self::$slots as $slot => $view) {
            self::$data[$slot] = self::view($view, self::$data, true);
        }
        try {
            $temp_ob = self::layout(self::$layout, self::$data);
        } catch(Throwable $e) {
            $temp_ob = self::distribution_layout(self::$layout, self::$data);
        }
        return $temp_ob;
    }

}
