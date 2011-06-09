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
 
  protected $aspects = array();
  protected $aliases = array();
  protected $join_on = array();
  protected $default_fields = array();
  protected $insert_defaults = array();
  protected $update_defaults = array();
  protected $stat = array();
  protected $primary_aspect ='';
  protected $primary_key = '';

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


/*
 * Non-static methods for classes derived from this class.
 * Provide the magic sauce for building parts of queries.
 * ==========================================================================
 */

  public function __construct() {
    $this->default_fields = array ( 'created', 'modified' );

    $this->insert_defaults = array (
      'created' => 'NOW()'
    );

    $this->update_defaults = array (
      'modified' => 'NOW()'
    );

    // Get the primary aspect -- first item in ordered hash
    $aspect_list = array_keys($this->aspects);
    $this->primary_aspect = array_shift($aspect_list);
    $this->primary_key = $this->aspects[$this->primary_aspect][0];
  }

  /*
   * get_all()
   * ---------
   * Select everything from the model
   */
  public function get_all() {
    $select = $this->build_select().';';
    return self::query_array($select);
  }

  /*
   * set()
   * ---------
   * Set the data for the model, create it if it doesn't exist
   */
  public function set($data) {
    if (isset($data['id']) && $this->get_by_id($data['id'])) {
      $query = $this->build_update($data, $this->primary_aspect);
    } else {
      $query = $this->build_insert($data, $this->primary_aspect);
    }
    $result = db::query_ins($query);
    return $result;
  }

  /*
   * delete()
   * --------
   * deletes data from the primary table in the model
   */
  public function delete($id) {
      return db::query_ins("DELETE FROM `{$this->primary_aspect}` WHERE `id` = {$id};");
  }

  protected function build_select($aspect = NULL) {
    $verb = 'SELECT';
    $fields = array();
    $from = '';
    $joins = NULL;

    // if we're selecting from only one aspect, there will be no joins
    if ($aspect != NULL) {
      $fields = array_merge($this->aspects[$aspect], $this->default_fields);
      // prepare fields by wrapping them in back ticks
      foreach ($fields as $field) {
        $fields[] = "`$field`";
      }
      // Set table to select from
      $from = "$aspect";

    } else {
      // We have joins for multiple tables (all aspects)

      $primary_aspect = $this->primary_aspect;

      foreach ($this->aspects as $aspect => $aspect_fields) {
        $iter_fields = $aspect_fields;
        // Add default fields to primary aspect
        if ($aspect == $primary_aspect)
          $iter_fields = array_merge($iter_fields, $this->default_fields);
        // Add aspect name to list of tables being used
        foreach ($iter_fields as $field) {
          // Prepare fields for selection in a join.
          $fields[] = "`$aspect`.`$field`";
        }
      }

      $tables = array_keys($this->aspects);
      // Grab table to select from, and prevent it from being in the joins table
      $from = array_shift($tables);

      $joins = array();
      // loop through tables to prepare join statements
      foreach($tables as $table) {
        // begin join statement
        $join = "JOIN `$table`";
        // if there are join on conditions, use them here.
        if (isset($this->join_on[$from])) {
          if (isset($this->join_on[$from][$table])) {
            $join .= " ON ({$this->join_on[$from][$table]})";
          }
        }
        // Add join statement to the array of joins
        $joins[] = $join;
      }
    }

    // finish building select statement
    $query_parts[] = $verb;
    $query_parts[] = implode(', ', $fields);
    $query_parts[] = "FROM `$from`";
    if ($joins != NULL) {
      $query_parts[] = implode(' ', $joins);
    }
    $query = implode(" ", $query_parts);
    return $query;
  }

  protected function build_insert($data, $aspect) {
    $fields = array();
    $values = array();
    $field_list = array_merge($this->aspects[$aspect], $this->default_fields);
    $data = array_merge($data, $this->insert_defaults);
    $query  = "INSERT INTO `$aspect` (";
    foreach ($data as $k => $v) {
      if (in_array($k, $field_list)){
        $fields[] = "`$k`";
      }
    }
    $query .= implode(',', $fields);
    $query .= ") VALUES (";
    foreach ($data as $k => $v) {
      if (in_array($k, $field_list)){
        $value = mysql_real_escape_string($v);
        if(is_integer($v) || in_array($k, $this->default_fields)) {
          $values[] = "$value";
        } else {
          $values[] = "'$value'";
        }
      }
    }
    $query .= implode(',', $values);
    $query .= ");";
    return $query;
  }

  protected function build_update($data, $aspect) {
    $statements = array();
    $field_list = array_merge($this->aspects[$aspect], $this->default_fields);
    $data = array_merge($data, $this->update_defaults);
    $query  = "UPDATE `$aspect` SET ";
    foreach ($data as $k => $v) {
      if (in_array($k, $field_list)){
        $value = mysql_real_escape_string($v);
        if(is_integer($v) || in_array($k, $this->default_fields)) {
          $statements[] = "`$k`=$value";
        } else {
          $statements[] = "`$k`='$value'";
        }
      }
    }
    $query .= implode(',', $statements);
    $key = $this->primary_key;
    $value = $data[$key];
    $query .= " WHERE `{$key}` = '{$value}';";
    return $query;
  }

  /*
   * Prepares individual statements
   * Expects classes that derive from this implement the comparison functions
   *   that return a SQL comparison "verb predicate" (eg. ' > NOW()').
   */
  protected function prepare_stat_subqueries($total_days) {
    $statements = array();
    foreach ($this->stat as $aspect => $params) {
      foreach ($params as $function => $comparison_key) {
        for ($i = 0; $i <= $total_days; $i++) {
          $statements[$aspect][] = "SELECT `{$this->primary_key}`, COUNT(`{$this->primary_key}`) AS `{$i}` FROM `{$aspect}` WHERE `$comparison_key`" . $this->$function($i)." GROUP BY `{$this->primary_key}`";
        }
      }
    }
    return $statements;
  }

  /*
   * Builds entire stat query and returns it.
   */
  public function build_stat_query($total_days) {
    $verb             = 'SELECT';
    $stat_selections  = array("\n  `{$this->primary_aspect}`.`{$this->primary_key}` AS `{$this->primary_key}`");
    $stat_joins       = array("`{$this->primary_aspect}`");
    $query_parts      = array();
    $query            = '';

    $stat_statements  = $this->prepare_stat_subqueries($total_days);
    foreach ($stat_statements as $aspect => $stat_subqueries) {
      foreach ($stat_subqueries as $i => $stat_subquery) {
        $stat_selections[]  = "\n  COALESCE(`{$aspect}_table_{$i}`.`{$i}`, 0) AS `{$aspect}_{$i}`";
        $stat_join          = "\n  ( $stat_subquery ) AS `{$aspect}_table_{$i}`".
          "\n  ON `{$aspect}_table_{$i}`.`{$this->primary_key}` = `{$this->primary_aspect}`.`{$this->primary_key}`";
        $stat_joins[] = $stat_join;
      }
    }
    $query_parts[] = $verb;
    $query_parts[] = implode(', ', $stat_selections);
    $query_parts[] = "\nFROM";
    $query_parts[] = implode("\nLEFT OUTER JOIN", $stat_joins);
    $query_parts[] = "\nORDER BY `{$this->primary_aspect}`.`{$this->primary_key}`";
    $query = implode(' ', $query_parts);
    return $query;
  }

  /*
   * Builds + executes stat queries, parses results into the following format:
   *  $data[$aspect] = array($data_elements);
   */
  public function get_stats($total_days, $additional_clause = '') {
    if($additional_clause != '') {
      $additional_clause = ' '.$additional_clause;
    }
    $query = $this->build_stat_query($total_days).$additional_clause.';';
    $result = self::query_array($query);
    $data = array();
    foreach ($result as $i => $stats) {
      $temp = array($this->primary_key => $stats[$this->primary_key]);
      foreach ($this->stat as $aspect => $params) {
        for ($i = 0; $i <= $total_days; $i++) {
          $temp[$aspect][] = $stats["{$aspect}_{$i}"];
        }
      }
      $data[] = $temp;
    }
    return $data;
  }

}
