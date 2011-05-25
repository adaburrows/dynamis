<?php
/* ============================================================================
 * class session;
 * -----------------
 * This class provides session implementation
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

class session extends db {
  protected $sessions;
  protected $hashes;
  protected $default_expiration;

  public function  __construct() {
    parent::__construct();

    // Setup aspects for sessions
    $this->aspects = array (
      'ulynk_sessions' => array (
        'id',
        'name',
        'expires',
        'data',
        'hash'
      )
    );

    // Set the default expiration of the cookie to a half hour session
    $this->default_expiration = 30 * 60 * 60;

    // Fetch all of the session info from the database
    $this->sessions = array();
    foreach ($_COOKIE['dynamis_sessions'] as $name => $hash) {
      $hashes[] = $hash;
    }
    $query = $this->build_select()." WHERE `hash` IN (".implode(',',$hashes).");";
    $data = self::query_array($query);
    if($data) {
      foreach ($data as $datum) {
        $this->sessions[$datum['name']] = $datum;
      }
    }
  }

  public function list_all() {
    $query = $this->build_select();
    return self::query_array($query.';');
  }

  public function is_current($name) {
    return key_exists($name, $$this->sessions);
  }

  public function make($name) {
    $status = false;
    if (!$this->is_current($name)) {
      $this->sessions[$name] = array(
        'name' => $name,
        'expires' => "DATE_ADD(NOW(), {$this->default_expiration} SECONDS)",
      );
      $insert = $this->build_insert($this->sessions[$name], 'ulynk_sessions');
      $status = self::query_ins($insert);
      if($status) {
        $id = mysql_insert_id();
        $hash = sha1($id.$name.microtime());
        $this->sessions[$name]['hash'] = $hash;
        $status = $this->set($this->sessions[$name]);
        if($status) {
          setcookie("dynamis_sessions[{$name}]", $hash, time()+$this->default_expiration);
        }
      }
    }
    return $status;
  }

  public function get($name) {
    $return = false;
    if(!empty($this->sessions[$name])) {
      $return = $this->sessions[$name];
    }
    return $return;
  }

  public function set($data) {
    $query = $this->build_update($data, 'ulynk_sessions');
    return self::query_ins($query);
  }

  public function end($name) {
    setcookie("dynamis_sessions[{$name}]", '', (time() - 3600));
    $hash = $this->sessions['hash'];
    if (self::query_ins("DELETE FROM `ulynk_sessions` WHERE `hash` = '{$hash}';")) {
      unset($this->sessions[$name]);
    }
  }
}