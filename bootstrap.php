<?php
date_default_timezone_set('America/Los_Angeles');
$start_time = microtime(true);

// Require the application configuration file
require_once APPPATH.'config'.EXT;

// Require libraries
require_once BASEPATH.'database'.EXT;
require_once BASEPATH.'utilities'.EXT;
require_once BASEPATH.'libs/layout'.EXT;
require_once BASEPATH.'libs/app'.EXT;
app::setStartTime($start_time);

// Setup defaults for application 
require_once APPPATH.'init'.EXT;
require_once APPPATH.'routes'.EXT;

// Start the session
session_start();

// Core libraries to load
$core = array('controller', 'db');
// Load core classes that all classes extend
foreach($core as $class) {
  if (file_exists(APPPATH."core/$class".EXT)) {
    require_once APPPATH."core/$class".EXT;
  } else {
    require_once BASEPATH."core/$class".EXT;
  }
}

// Start the app by dispatching the route
require_once BASEPATH.'libs/dispatcher'.EXT;
