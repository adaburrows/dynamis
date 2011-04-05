<?php

/**
 * Returns the proper site url relative to the base url for the specified resource.
 * Use in all views to retain portability across domains.
 */
function site_url($url) {
global $config;
  if (is_array($url))
    $url = router::unmap($url);
  return($config['site_base'].$url);
}

/**
 * Takes a recognizable url and makes sure it has a http(s?):// prefix to ensure all urls are never relative
 */
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

/**
 * checks to see if a number is in the right form to be phone number
 */
function isPhoneNumber($number) {
  return (preg_match('/^((\+)?[0-9]( |-)?)?(\(?[0-9]{3}\)?|[0-9]{3})( |-)?([0-9]{3}( |-)?[0-9]{4})$/', $number));
}

/**
 * Formats a number for display
 * TODO: check number of digits and format accordingly
 */
function formatPhoneNumber($number) {
  return ('('.substr($number,0,3).') '.substr($number,3,3)."-".substr($number,6,4));
}

/**
 * returns only digits of a phone number
 */
function normalizePhoneNumber($number) {
  return (preg_replace('/(\+| |-|\(|\))?/', '', $number));
}

/**
 * For outputting values that might not be defined.
 */
function _e($string) {
  echo isset($string) ? $string : "";
}
