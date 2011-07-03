<?php
/* ============================================================================
 * class db;
 * -----------------
 * This class provides the magic for accessing the database
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

class db {
  protected static $connection;
  protected static $db_num_results = 0;
  protected static $db_query_results = array();

/*
 * Methods for dealing with the database.
 * ==========================================================================
 */

  /*
   * db::connect();
   * ------------------
   * Connects to the database.
   */
  public static function connect() {
  global $config;
    //connect to mysql database server
    self::$connection = mysql_connect($config['db_host'],$config['db_user'],$config['db_pass']) or die("Error connecting to database.");
    //select our database
    mysql_select_db($config['db_name']) or die("Error selecting database...");
  }

  /*
   * db::query_array();
   * -------------------
   * This function retreives a number indexed array of rows (stored in associative array form) from a database query
   */
  public static function query_array($sql_request){
    $a=array();
    $result= mysql_query($sql_request);
    if($result) {
      self::$db_num_results = mysql_num_rows($result);
      while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
        $a[]=$row;
      }
    }
    return $a;
  }

  /*
   * db::query_item();
   * ------------------
   * This function retreives one row from a database query as an associative array
   * Assumption of name: your query will only return one record.
   * If the query returns more than one record, only the first is returned by the function
   */
  public static function query_item($sql_request){
    $row = array();
    $result = mysql_query($sql_request);
    if ($result) {
      self::$db_num_results = mysql_num_rows($result);
      $row = mysql_fetch_array($result, MYSQL_ASSOC);
    }
    return $row;
  }

  /*
   * db::query_ins();
   * -----------------
   * Runs an insert query, returning the result.
   */
  public static function query_ins($sql_insert){
    $result = mysql_query($sql_insert);
    if ($result) {
      self::$db_num_results = mysql_affected_rows();
    }
    return $result;
  }

  /*
   * db::num_results();
   * ------------------
   * Returns the number of results from the last query.
   */
  public static function num_results(){
    return self::$db_num_results;
  }

  /*
   * db::paginate();
   * ------------------
   * Prints paging in a paged view.
   */
  public static function paginate ($page = 0, $show = 10, $params = '') {
    $data = array(
      'request_controller' => app::getReqController(),
      'request_method'     => app::getReqMethod(),
      'page'               => $page,
      'show'               => $show,
      'prev'               => $page-1,
      'next'               => $page+1,
      'num_pages'          => intval(self::$db_num_results/$show),
      'params'             => $params
    );

    $html = layout::view('pagination', $data, true);
    return $html;
  }

}
