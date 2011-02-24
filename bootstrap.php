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

// Start the app by dispatching the route
require_once BASEPATH.'libs/dispatcher'.EXT;
