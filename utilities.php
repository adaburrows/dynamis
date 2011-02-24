<?php

/*
 * Returns the proper site url relative to the base url for teh specified resource.
 * Use in all views to retain portability across domains.
 */
function site_url($url) {
global $config;
  if (is_array($url))
    $url = app::mapRoute($url);
  return($config['site_base'].$url);
}

function normalize_url($url) {
  $url_pattern = "@\b(https?://)?(([0-9a-zA-Z_!~*'().&=+$%-]+:)?[0-9a-zA-Z_!~*'().&=+$%-]+\@)?(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-zA-Z_!~*'()-]+\.)*([0-9a-zA-Z][0-9a-zA-Z-]{0,61})?[0-9a-zA-Z]\.[a-zA-Z]{2,6})(:[0-9]{1,4})?((/[0-9a-zA-Z_!~*'().;?:\@&=+$,%#-]+)*/?)@";
  if (preg_match($url_pattern, $url) > 0) {
    if (preg_match('@\b(https?://)@', $url) == 0) {
      $url = "http://$url";
    }
  } else {
    $url = false;
  }
  return $url;
}

/*
* checks to see if a number is in the right form to be phone number
*/
function isPhoneNumber($number) {
  return (preg_match('/^((\+)?[0-9]( |-)?)?(\(?[0-9]{3}\)?|[0-9]{3})( |-)?([0-9]{3}( |-)?[0-9]{4})$/', $number));
}

/*
* Formats a number for display
*/
function formatPhoneNumber($number) {
  return ('('.substr($number,0,3).') '.substr($number,3,3)."-".substr($number,6,4));
}

/*
* returns only digits
*/
function normalizePhoneNumber($number) {
  return (preg_replace('/(\+| |-|\(|\))?/', '', $number));
}

function _e($string) {
  echo isset($string) ? $string : "";
}

/*
 * Insert a visitor's info into our database
 */
function trackvisitor($company_id = 0){
  if(!isset($_SERVER['HTTP_REFERER'])){ $_SERVER['HTTP_REFERER']=''; }
  app::query_ins("INSERT INTO `prolegic`.`link_in` (`company_id` ,`Date` ,`Time` ,`HTTP_USER_AGENT` ,`HTTP_REFERER` ,`Ip_address`)VALUES ('{$company_id}', NOW(), NOW(), '{$_SERVER['HTTP_USER_AGENT']}', '{$_SERVER['HTTP_REFERER']}', '{$_SERVER['REMOTE_ADDR']}');");
}

/** use this function sparingly **/
/*
 * The session variable should be used to store the user_id when they login
 * The company_id should be posted to the pages in all cases and double checked against
 * the companies that belong to the owner id.
 *
 */
//create a bunch of cookies 
function generateCookies($CookieArray){
  foreach($CookieArray as $key => $value){
    setcookie($key, $value, time()+99600);
  }
}
/*
 ** !!!PLEASE DON'T DO THIS!!! **

  if(isset($_GET['company_id'])){  
    if($_GET['company_id']!=$_COOKIE['company_id']){
      header("location: {$_SERVER['REQUEST_URI']}");
      exit();
    }
  }
*/

/*
 * Removes double quotation marks (")
 */
function replaceillegalstuff($input){
  return str_ireplace("\"", "",$input );
}
