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
  protected static $db_num_rows_affected = 0;
  protected static $db_insert_id = false;
  protected static $db_error = array();
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
    $type = isset(app::$config['db_type']) ?
      app::$config['db_type'] : 'unknown';
    $host = isset(app::$config['db_host']) ?
      app::$config['db_host'] : '127.0.0.1';
    $port = isset(app::$config['db_port']) ?
      'port='.app::$config['db_port'].';' : '';
    $name = isset(app::$config['db_name']) ?
      'dbname='.app::$config['db_name'].';' : '';
    $user = isset(app::$config['db_user']) ?
      app::$config['db_user'] : 'user';
    $pass = isset(app::$config['db_pass']) ?
      app::$config['db_pass'] : 'pass';
    
    $dsn = "{$type}:host={$host};{$port}{$name}";

    // Lets try the settings and hope they work!
    try {
        //connect to mysql database server
        self::$connection = new PDO($dsn, $user, $pass);
    } catch (PDOException $e) {
        self::$connection = null;
        app::exception_handler(new Exception("Could not connect to database!"));
    }
  }

  /*
   * db::query_array();
   * -------------------
   * This function retreives a number indexed array of rows (in associative array form) from a database query
   */
  public static function query_array($query, $bind_values = NULL) {
    $statement = self::$connection->prepare($query);
    if($bind_values!=NULL && is_array($bind_values)) {
      foreach ($bind_values as $name => $value) {
        $statement->bindValue(":{$name}", $value);
      }
    }
    try {
      $statement->execute();
      self::$db_query_results = $statement->fetchAll(PDO::FETCH_ASSOC);
      self::$db_num_results = count(self::$db_query_results);
    } catch (PDOException $e) {
      self::$db_error = $statement->errorInfo();
      app::exception_handler(new Exception(implode(' ,', self::$db_error)."\n{$query}"));
      self::$db_query_results = array();
    }
    return self::$db_query_results;
  }

  /*
   * db::query_item();
   * ------------------
   * This function retreives one row from a database query as an associative array
   * Assumption of name: your query will only return one record.
   * If the query returns more than one record, only the first is returned by the function
   */
  public static function query_item($query, $bind_values = NULL) {
    $statement = self::$connection->prepare($query);
    if($bind_values!=NULL && is_array($bind_values)) {
      foreach ($bind_values as $name => $value) {
        $statement->bindValue(":{$name}", $value);
      }
    }
    try {
      $statement->execute();
      self::$db_query_results = $statement->fetch(PDO::FETCH_ASSOC);
      self::$db_num_results = self::$db_query_results ? 1 : 0;
    } catch (PDOException $e) {
      self::$db_error = $statement->errorInfo();
      app::exception_handler(new Exception(implode(' ,', self::$db_error)."\n{$query}"));
      self::$db_query_results = array();
    }
    return self::$db_query_results;
  }

  /*
   * db::query_ins();
   * -----------------
   * Runs an insert query, returning the result.
   */
  public static function query_ins($query, $bind_values = NULL) {
    $statement = self::$connection->prepare($query);
    if($bind_values!=NULL && is_array($bind_values)) {
      foreach ($bind_values as $name => $value) {
        $statement->bindValue(":{$name}", $value);
      }
    }
    try {
      $statement->execute();
      self::$db_num_rows_affected = $statement->rowCount();
      self::$db_insert_id = self::$connection->lastInsertId();
    } catch (PDOException $e) {
      self::$db_error = $statement->errorInfo();
      app::exception_handler(new Exception(implode(' ,', self::$db_error)."\n{$query}"));
      self::$db_num_rows_affected = 0;
    }
    if (self::$db_num_rows_affected > 0) {
        return true;
    } else {
        return false;
    }
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
   * db::results();
   * --------------
   * Returns the results from the last query.
   */
  public static function results(){
    return self::$db_query_results;
  }

  /*
   * db::num_rows_affected();
   * ------------------------
   * Returns the number of affected rows from the last query.
   */
  public static function num_rows_affected(){
    return self::$db_num_rows_affected;
  }

  /*
   * db::insert_id();
   * ----------------
   * Returns the insert id from the last query.
   */
  public static function insertId(){
    return self::$db_insert_id;
  }

  /*
   * db::error();
   * ------------
   * Returns the error from the last query.
   */
  public static function error(){
    return self::$db_error;
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
