<?php

class db {
  protected $fields = array();
  protected $data = array();
  
  protected $templates = array(
    'select' => 'SELECT #fields# FROM #table# #predicate#;',
    'insert' => 'INSERT INTO #table# (#fields#) VALUES (#values#);',
    'update' => 'UPDATE #table# SET #fieldvalues# #predicate#;',
    'delete' => 'DELETE FROM #table# #predicate#;'
    );
  
  // SQL construction variables

  public function &select($fields) {
    
    return &this;
  }

  public function &delete() {
    return &$this;
  }

  public function &from($table) {
    return &$this;
  }

  public function &insert($data) {
    return &this;
  }

  public function &into($table) {
    return &this;
  }
  
  public function &update($table) {
    return &$this;
  }

  public function &set($data) {
    return &this;
  }
}
