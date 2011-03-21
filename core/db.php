<?php

class db {
 
  protected $aspects = array();
  protected $aliases;
  protected $join_on = array();
  protected $default_fields = array();
  protected $insert_defaults = array();
  protected $update_defaults = array();
  protected $stat = array();
  protected $primary_aspect;
  protected $primary_key;

  public function __construct() {
    $this->default_fields = array ( 'created', 'modified' );

    $this->insert_defaults = array (
      'created' => 'NOW()'
    );

    $this->update_defaults = array (
      'modified' => 'NOW()'
    );
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

      // Get the primary aspect -- first item in ordered hash
      $aspect_list = array_keys($this->aspects);
      $primary_aspect = array_shift($aspect_list);

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

    print_r($query);
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

    print_r($query);
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
    $query .= " WHERE `id` = '{$data['id']}';";

    print_r($query);
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
    // Get the primary aspect -- first item in ordered hash
    $aspect_list = array_keys($this->aspects);
    $this->primary_aspect = array_shift($aspect_list);
    $this->primary_key = $this->aspects[$this->primary_aspect][0];

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
    $query_parts[] = "\nORDER BY `{$this->primary_aspect}`.`{$this->primary_key}`;";
    $query = implode(' ', $query_parts);
    return $query;
  }

  /*
   * Builds + executes stat queries, parses results into the following format:
   *  $data[$aspect] = array($data_elements);
   */
  public function get_stats($total_days) {
    $query = $this->build_stat_query($total_days);
    $result = app::query_array($query);
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
