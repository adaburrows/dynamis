<?php

class db {
 
  protected $aspects;
  protected $join_on;
  protected $insert_defaults;
  protected $update_defaults;

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

      // prepare fields by wrapping them in back ticks
      foreach ($this->aspects[$aspect] as $field) {
        $fields[] = "`$field`";
      }
      // Set table to select from
      $from = "$aspect";

    } else {
      // We have joins for multiple tables (all aspects)

      foreach ($this->aspects as $aspect => $aspect_fields) {
        // Add aspect name to list of tables being used
        foreach ($aspect_fields as $field) {
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
        $values[] = "'$value'";
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
        $statements[] = "`$k`='$value'";
      }
    }
    $query .= implode(',', $statements);
    $query .= " WHERE `id` = '{$data['id']}';";

    print_r($query);
    return $query;
  }

}
