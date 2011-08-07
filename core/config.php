<?php

/* ============================================================================
 * class config;
 * ------------
 * This class is in charge of the configuration information.
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

class config {
    protected static $config = array();

    public static function load() {
        // Require the application configuration file
        require_once APPPATH.'config'.EXT;
        if (empty($config['timezone'])) {
            $config['timezone'] = 'America/Los_Angeles';
        }
        // Set up time
        date_default_timezone_set($config['timezone']);
        $start_time = microtime(true);
        // Set up defaults
        if(empty($config['default_request_type'])){
            $config['default_request_type'] = 'html';
        }
        if(empty($config['default_controller'])){
            $config['default_controller'] = 'main';
        }
        if(empty($config['default_method'])){
            $config['default_method'] = 'index';
        }
        // Core libraries to load
        if(empty ($config['core'])) {
            $config['core'] = array('db', 'model');
        }
        self::$config = $config;

        return $start_time;
    }

    public static function set($config_var, $value) {
        self::$config[$config_var] = value;
    }

    public static function get($config_var) {
        $value = false;
        if(isset(self::$config[$config_var])) {
            $value = self::$config[$config_var];
        }
        return $value;
    }
}