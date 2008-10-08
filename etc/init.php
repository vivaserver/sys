<?php
  /*
  +--------------------------------------------------------------------------
  |   Initial System Configuration: Do not modify unless you know what you are doing
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  // app's root is relative, app's home is absolute
  $conf['app_home'] = substr($_SERVER["SCRIPT_FILENAME"],0,-strlen("index.php"));
  $conf['app_root'] = str_replace('/var/www','',$conf['app_home']);

  require($conf['app_home']."etc/conf.php");

  load_file("lib/ez_sql.{$conf['db_server']}");
  load_file("lib/functions");
  load_file("lib/markdown");
  load_file("lib/tbs");
  load_file("lib/tbs_html");
  load_file("lib/tbs_markdown");
  load_file("lib/tbs_navbar");
  //
  load_file("core/site");
  load_file("core/session");
  load_file("core/user");
  load_file("core/auth");

  @include($conf['app_home']."etc/models.php");

  $db   = new db($conf['db_user'],$conf['db_pass'],$conf['db_name'],$conf['db_host']);
  $tpl  = new clsTinyButStrong;
  $site = new site();
  $user = new user();
  $auth = new auth();

  //
  // require app. or system file, in that order
  //
  function load_file($name) {
    $name .= substr($name,-4)=='.php' ? '' : '.php';
    if (file_exists($GLOBALS['conf']['app_home'].$name)) {
      require($GLOBALS['conf']['app_home'].$name);
    }
    elseif (file_exists($GLOBALS['conf']['sys_home'].$name)) {
      require($GLOBALS['conf']['sys_home'].$name);
    }
  }

  define("INITIALIZED",true);
?>