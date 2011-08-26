<?php
class util {
  /**
   * Takes a recognizable url and makes sure it has a http(s?):// prefix to ensure all urls are never relative
   */
  public static function normalize_url($url) {
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
  public static function isPhoneNumber($number) {
    $number = trim($number);
    return (preg_match('/^((\+)?[0-9]( |-)?)?(\(?[0-9]{3}\)?|[0-9]{3})( |-)?([0-9]{3}( |-)?[0-9]{4})$/', $number));
  }

  /**
   * Formats a number for display
   * TODO: check number of digits and format accordingly
   */
  public static function formatPhoneNumber($number) {
    $number = self::normalizePhoneNumber($number);
    return ('('.substr($number,0,3).') '.substr($number,3,3)."-".substr($number,6,4));
  }

  /**
   * returns only digits of a phone number
   */
  public static function normalizePhoneNumber($number) {
    $number = trim($number);
    $number = preg_replace('/(\+| |-|\(|\))?/', '', $number);
    if(strlen($number) == 11) {
      $number = substr($number, 1);
    }
    return $number;
  }
}

/**
 * For outputting values that might not be defined.
 */
function _e($string) {
  echo isset($string) ? $string : "";
}
