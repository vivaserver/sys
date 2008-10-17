<?php
  /*
  +--------------------------------------------------------------------------
  |   Authentication Class
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2004-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */
  load_file("lib/endecrypt");

  class auth {
    public $encoder;
    private $validate_with;

    //
    // (not just) set proper defaults for sensitive class vars.
    //
    function __construct($validate_with='user_name') {
      global $db, $conf, $lang, $user, $site;

      $this->encoder = new EnDecryptText();
      $this->validate_with = $validate_with;
      $auth = 0;

      if ($conf['only_local_logins'] && !in_array(substr($_SERVER['REMOTE_ADDR'],0,7),array('192.168','127.0.0'))) {
        // disallow login from remote ip's
      }
      elseif ($conf['tb_permissions']) {
        // permissions are user-group based, so get user-grup first
        if ($user->group_id) {
          $group_id = $user->group_id;
        }
        elseif ($this->_checktimeout()) {
          $group_id = $user->group_id;
        }
        else {
          $group_id = array_key_exists('not_logged_group_id',$conf) ? $conf['not_logged_group_id'] : 0;
        }
        // check for specific user_group + module + action permission
        $sql  = "select ";
        $sql .= " * ";
        $sql .= "from ";
        $sql .= " {$conf['tb_permissions']} ";
        $sql .= "where ";
        $sql .= " group_id='$group_id' ";
        $sql .= "and ";
        $sql .= " module='{$site->module}' ";
        $sql .= "and ";
        $sql .= " act='{$site->act}' ";
        $row  = $db->get_row($sql,ARRAY_A);
        if ($row) {
          $auth = $row['grant_access'];
        }
        else {
          // no specific user_group + module + action permission, check for module defaults
          $sql  = "select ";
          $sql .= " * ";
          $sql .= "from ";
          $sql .= " {$conf['tb_permissions']} ";
          $sql .= "where ";
          $sql .= " group_id='$group_id' ";
          $sql .= "and ";
          $sql .= " module='{$site->module}' ";
          $sql .= "and ";
          $sql .= " act='*' ";
          $row  = $db->get_row($sql,ARRAY_A);
          if ($row) {
            $auth = $row['grant_access'];
          }
          else {
            // no specific user_group + module permission, check for group defaults
            $sql  = "select ";
            $sql .= " * ";
            $sql .= "from ";
            $sql .= " {$conf['tb_permissions']} ";
            $sql .= "where ";
            $sql .= " group_id='$group_id' ";
            $sql .= "and ";
            $sql .= " module='*' ";
            $sql .= "and ";
            $sql .= " act='*' ";
            $row  = $db->get_row($sql,ARRAY_A);
            if ($row) {
              $auth = $row['grant_access'];
            }
            else {
              // no module access for group user
              $auth = 0;
            }
          }
        }
      }
      else {
        // no permissions table, access granted to all modules !!
        $auth = 1;
      }

      //
      // validate request usign auth
      //
      switch ($auth) {
        //
        // no access, rewrite url request to error page
        //
        case 0 :
          if ($user->id) {
            // user logged, but not enough access permissions
            $site->module = NULL;
            $site->act    = NULL;
            $site->sysmsg = "Acceso Denegado. No tiene suficientes permisos para ingresar aqu&iacute;.";
          }
          else {
            // not logged. log-in and recheck permissions
            $site->module = "log";
            $site->act    = "form";
            $site->sysmsg = "Acceso Denegado. Ingrese antes de continuar.";
          }
        break;

        //
        // access granted, process url request
        //
        case 1 :
          // validate user session before proceeding
          if ($user->id) {
            if ($this->_checktimeout()) {
              // not timeout yet, proceed
            }
            else {
              $site->module = "log";
              $site->act    = "form";
              $site->sysmsg = "Session timeout. Please re-log.";
            }
          }
          else {
            // try log in from post
            if ($_POST['user_name'] && $_POST['user_password']) {
              setcookie("user_name",    '', time());
              setcookie("user_password",'', time());
              if ($this->hascookies()) {
                // only if cookies enabled
                $sql  = "select ";
                $sql .= " * ";
                $sql .= "from ";
                $sql .= " {$conf['tb_usuarios']} ";
                $sql .= "where ";
                $sql .= " {$this->validate_with}='{$_POST['user_name']}' and ";
                $sql .= " is_disabled!=1 ";
                $row  = $db->get_row($sql,ARRAY_A);
                if ($row) {
                  // got user w/given email, get password and compare it w/posted
                  $decd = $this->encoder->Decrypt_Text($row['user_password']);
                  if ($decd == $_POST['user_password']) {
                    $_SESSION['user_id']        = $row['id'];
                    $_SESSION['user_group_id']  = $row['group_id'];
                    $_SESSION['user_name']      = $row['user_name'];
                    $_SESSION['user_full_name'] = strtoupper("{$row['last_name']}, {$row['first_name']}");
                    $user->id                   = $_SESSION['user_id'];
                    $user->group_id             = $_SESSION['user_group_id'];
                    $user->name                 = $_SESSION['user_name'];
                    // remember log data?
                    if ($_POST['user_timeout']) {
                      setcookie("user_name",     $_POST['user_name'],     time()+$_POST['user_timeout'], $conf['path_home']);
                      setcookie("user_password", $_POST['user_password'], time()+$_POST['user_timeout'], $conf['path_home']);
                    }
                    // update last log in
                    $sql   = "update ";
                    $sql  .= " {$conf['tb_usuarios']} ";
                    $sql  .= "set ";
                    $sql  .= " sess_id='".session_id()."', ";
                    $sql  .= " last_login_at=now(), ";
                    $sql  .= " last_ip='{$user->user_ip}' ";
                    $sql  .= "where ";
                    $sql  .= " {$this->validate_with}='{$_POST['user_name']}' ";
                    $query = $db->query($sql);
                  }
                  else {
                    $site->module = "log";
                    $site->act    = "form";
                    $site->sysmsg = "Invalid user e-mail / password.";
                  }
                }
                else {
                  // wrong log in try
                  $site->module = "log";
                  $site->act    = "form";
                  $site->sysmsg = "Invalid user name / password.";
                }
              }
              else {
                // no login w/o cookies
                $site->module = "log";
                $site->act    = "form";
                $site->sysmsg = "Activate cookies before login.";
              }
            }
            else {
              // not logged user and not log from post. process only if not admin requested
              if ($_GET['mode']=="admin" || $_POST['mode']=="admin") {
                $site->module = "log";
                $site->act    = "form";
                $site->sysmsg = $lang['error_user_not_logged'];
              }
            }
          }
        break;
      }
    }

    //
    // public | check user cookies activated
    //
    function hascookies() {
      if (isset($_COOKIE[session_name()])) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }

    //
    // private | check user session timeout
    //
    function _checktimeout() {
      if ($_SESSION['user_id']) {
        // session still alive
        return TRUE;
      }
      else {
        // session terminated, remember?
        if ($this->hascookies() && isset($_COOKIE['user_name']) && isset($_COOKIE['user_password'])) {
          $sql  = "select ";
          $sql .= " * ";
          $sql .= "from ";
          $sql .= " {$GLOBALS['conf']['tb_usuarios']} ";
          $sql .= "where ";
          $sql .= " {$this->validate_with}='{$_COOKIE['user_name']}' and ";
          $sql .= " is_disabled!=1 ";
          $row  = $GLOBALS['db']->get_row($sql,ARRAY_A);
          if ($row) {
            $decd = $this->encoder->Decrypt_Text($row['user_password']);
            if ($decd == $_COOKIE['user_password']) {
              $_SESSION['user_id']        = $row['id'];
              $_SESSION['user_group_id']  = $row['group_id'];
              $_SESSION['user_name']      = $row['user_name'];
              $_SESSION['user_full_name'] = strtoupper("{$row['last_name']}, {$row['first_name']}");
              //
              $GLOBALS['user']->id        = $_SESSION['user_id'];
              $GLOBALS['user']->group_id  = $_SESSION['user_group_id'];
              $GLOBALS['user']->name      = $_SESSION['user_name'];
              // update last log in
              $sql   = "update ";
              $sql  .= " {$GLOBALS['conf']['tb_usuarios']} ";
              $sql  .= "set ";
              $sql  .= " sess_id='".session_id()."', ";
              $sql  .= " last_login_at=now(), ";
              $sql  .= " last_ip='{$GLOBALS['user']->user_ip}' ";
              $sql  .= "where ";
              $sql  .= " {$this->validate_with}='{$_COOKIE['user_name']}' ";
              $query = $GLOBALS['db']->query($sql);
              return TRUE;
            }
          }
        }
      }
      return FALSE;
    }
  }
?>
