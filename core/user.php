<?php
  /*
  +--------------------------------------------------------------------------
  |   User Environment Class
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2004-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  class user extends session {
    public $id = 0;
    public $name;
    public $group_id;
    public $user_ip;
    public $user_host;

    //
    // set proper defaults for sensitive class vars.
    //
    function __construct($with_session=TRUE) {
      if ($with_session) {
        // create / retrieve user session, init (db) session handler
        parent::__construct();
        $this->id       = $_SESSION['user_id']       ? $_SESSION['user_id']       : $this->id;
        $this->group_id = $_SESSION['user_group_id'] ? $_SESSION['user_group_id'] : NULL;
        $this->name     = $_SESSION['user_name']     ? $_SESSION['user_name']     : NULL;
      }
      //
      $this->user_ip   = $_SERVER['REMOTE_ADDR'];
      $this->user_host = gethostbyaddr($this->user_ip);
      $this->set_language($_SESSION['user_lang'] ? $_SESSION['user_lang'] : $GLOBALS['conf']['default_language']);
    }

    //
    // public | set user language
    //
    function set_language($code) {
      $code .= substr($code,-4)=='.php' ? '' : '.php';
      // require system language files & then overwrite w/app's, in any
      @include($GLOBALS['conf']['sys_home']."lang/$code");
      @include($GLOBALS['conf']['app_home']."lang/$code");
      // set_language always sets some language, so return TRUE regardless
      return TRUE;
    }

    //
    // public | log user out
    //
    function logout() {
      if ($this->id) {
        $sql   = "update ";
        $sql  .= " {$GLOBALS['conf']['tb_usuarios']} ";
        $sql  .= "set ";
        $sql  .= " sess_id=NULL ";
        $sql  .= "where ";
        $sql  .= " id='{$this->id}' ";
        $query = $GLOBALS['db']->query($sql);
        if ($query) {
          // remember to keep site language even after user logs out
          $code = $_SESSION['user_lang'];
          // NOTE: we don't destroy the session alltogheter anymore because it'll also destroy the session_id and we wouldn't be able to set_language again
          $_SESSION = array();
          setcookie("user_name",    '', time());
          setcookie("user_password",'', time());
          $this->set_language($code);
          return TRUE;
        }
      }
      return FALSE;
    }

    //
    // public | is user logged or not?
    //
    function is_logged() {
      return ($this->id ? TRUE : FALSE);
    }

    //
    // public | set user geo info, if available
    //
    function geo() {
      if ($GLOBALS['conf']['tb_geo']) {
        // get user geo info
        $row = $GLOBALS['db']->get_row("select * from {$GLOBALS['conf']['tb_geo']} where ip_from<=inet_aton('".$this->user_ip."') and ip_to>=inet_aton('".$this->user_ip."') limit 1");
        $this->geo_ip_from = $row->ip_from;
        $this->geo_ip_to   = $row->ip_to;
        $this->geo_code    = $row->ip_country_code2;
        $this->geo_country = $row->ip_country_name;
      }
    }

    //
    // public | log user visit
    //
    //  CREATE TABLE _visits_log (
    //    visit_id int(11) NOT NULL auto_increment,
    //    visit_time datetime NOT NULL default '0000-00-00 00:00:00',
    //    visit_ip varchar(20) NOT NULL default '',
    //    visit_host varchar(100) NOT NULL default '',
    //    PRIMARY KEY  (visit_id)
    //  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    //
    //  CREATE TABLE _visits_stat (
    //    visit_date date NOT NULL default '0000-00-00',
    //    visit_hits int(10) NOT NULL default '0',
    //    PRIMARY KEY  (visit_date)
    //  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
    //
    //
    function log_visit() {
      if ($GLOBALS['conf']['tb_visit_log'] && $GLOBALS['conf']['tb_visit_stat']) {
        // log (unique) user visit
        if ($this->user_ip != $conf['admin_ip']) {
          $row  = $GLOBALS['db']->get_row("select visit_ip, visit_time from {$GLOBALS['conf']['tb_visit_log']} where visit_ip='".$this->user_ip."' order by visit_time desc limit 1");
          $date = date("Y-m-d",time());
          $time = date("Y-m-d H:i:s",time());
          $past = date("Y-m-d H:i:s",time()-$GLOBALS['conf']['user_timeout']);
          if (!$row->visit_ip || ($row->visit_ip && $row->visit_time<$past)) {
            $query = $GLOBALS['db']->query("insert into {$GLOBALS['conf']['tb_visit_log']} (visit_time,visit_ip,visit_host) values ('$time','".$this->user_ip."','".$this->user_host."')");
            $row   = $GLOBALS['db']->get_row("select visit_hits from {$GLOBALS['conf']['tb_visit_stat']} where visit_date='$date'");
            if ($row) {
              $query = $GLOBALS['db']->query("update {$GLOBALS['conf']['tb_visit_stat']} set visit_hits=visit_hits+1 where visit_date='$date'");
            }
            else {
              $query = $GLOBALS['db']->query("insert into {$GLOBALS['conf']['tb_visit_stat']} (visit_date,visit_hits) values ('$date','1')");
            }
          }
        }
      }
    }
  }
?>
