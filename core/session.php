<?php
  /*
  +----------------------------------------------------------------------------
  |   Session Handling Class (DB support show work on both MySQL and PostgreSQL)
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2004-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +----------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +----------------------------------------------------------------------------
  */

  class session {
    //
    // set proper defaults for sensitive class vars.
    //
    function __construct() {
      ini_set('session.gc_maxlifetime', $GLOBALS['conf']['session_timeout']);
      switch ($GLOBALS['conf']['session_handler']) {
        case "db" :
          // db session handling
          ini_set('session.save_handler', 'user');
          ini_set('session.gc_probability', 99);
          session_set_save_handler(array(&$this,'_open'), array(&$this,'_close'), array(&$this,'_read'), array(&$this,'_write'), array (&$this,'_destroy'), array(&$this,'_gc')) or die("Failed to register user session handler.");
        break;

        default :
          // defaults to php built-in session handling
        break;
      }
      session_start();
    }

    // sessions db table schema is to be something like this:
    //
    // CREATE TABLE sessions (
    //   sess_id character varying NOT NULL,
    //   sess_last_active integer,
    //   sess_data text,
    //   PRIMARY KEY  (sess_id)
    // );
    //
    // NOTE: sess_last_active changed to store unix timestamp for pgsql compatibility
    //
    function _open($save_path, $session_name) {
      is_object($GLOBALS['db']) or die("No database connection available.");
      return true;
    }

    function _close() {
      return true;
    }

    function _read($id) {
      $row = $GLOBALS['db']->get_row("select sess_data from {$GLOBALS['conf']['tb_sessions']} where sess_id='$id'");
      if ($row) {
        $data = $row->sess_data;
      }
      else {
        // no session with given id, create for future updates
        $query = $GLOBALS['db']->query("insert into {$GLOBALS['conf']['tb_sessions']} (sess_id,sess_last_active) values ('$id','".time()."')");
        if ($query) {
          $data  = null;
        }
        else {
          // create failed, race condition? (session already created)
          $row = $GLOBALS['db']->get_row("select sess_data from {$GLOBALS['conf']['tb_sessions']} where sess_id='$id'");
          if ($row) {
            $data = $row->sess_data;
          }
          else {
            // fatal: create failed, no race condition, no session available
            $data = null;
          }
        }
      }
      return $data;
    }

    function _write($id, $sess_data) {
      $query = $GLOBALS['db']->query("update {$GLOBALS['conf']['tb_sessions']} set sess_last_active='".time()."', sess_data='$sess_data' where sess_id='$id'");
      if (!$query) {
        // no session yet, create
        $query = $GLOBALS['db']->query("insert into {$GLOBALS['conf']['tb_sessions']} (sess_id,sess_last_active,sess_data) values ('$id','".time()."','$sess_data')");
      }
      return $query;
    }

    function _destroy($id) {
      return $GLOBALS['db']->query("delete from {$GLOBALS['conf']['tb_sessions']} where sess_id='$id'");
    }

    function _gc($maxlifetime) {
      return $GLOBALS['db']->query("delete from {$GLOBALS['conf']['tb_sessions']} where sess_last_active < '".(time()-$maxlifetime)."' ");
    }
  }
?>