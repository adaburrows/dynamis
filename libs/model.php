<?php

class model extends db {
  // settings used in models
  protected $aspects = array();
  protected $subqueries = array();
  protected $aliases = array();
  protected $fields = array();
  protected $coalesce = array();
  protected $joins = array();
  protected $data = array();
  protected $table = NULL;
}
