<?php

//connect to mysql database server
$db_connection = mysql_connect($config['db_host'],$config['db_user'],$config['db_pass']) or die('mysql_connect');
//select our database
mysql_select_db($config['db_name']) or die('mysql_select_db');
