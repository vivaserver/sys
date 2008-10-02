<?php
  /*
  +--------------------------------------------------------------------------
  |   Site Environment Class
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2004-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  class site {
    public $act;
    public $module;
    public $sysmsg;
    public $sysbtn;

    //
    // set proper defaults for sensitive class vars.
    //
    function __construct() {
      if ($GLOBALS['conf']['tb_conf']) {
        $row = $GLOBALS['db']->get_row("select * from {$GLOBALS['conf']['tb_conf']} where site_url like '%".$this->domain()."%'",ARRAY_A);
        if ($row) {
          // conf according to domain
        }
        else {
          // no conf matching domain, use default
          $row = $GLOBALS['db']->get_row("select * from {$GLOBALS['conf']['tb_conf']} order by id limit 1",ARRAY_A);
        }
        if ($row) {
          // site configuration to $conf global array
          foreach ($row as $key => $var) {
            if (isset($GLOBALS['conf'][$key])) {
              // do not overwrite existing configuration vars.
            }
            else {
              $GLOBALS['conf'][$key] = $var;
            }
          }
        }
        else {
          // no conf matching domain, use default
          die("Can't retrieve site configuration.");
        }
      }
      //
      // site module+action is needed to map user permissions
      //
      if ($_GET) {
        $this->module = $_GET['module'] ? $_GET['module'] : ($_GET['mod'] ? $_GET['mod'] : "default");
        $this->act    = $_GET['act']    ? $_GET['act']    : ($_GET['do']  ? $_GET['do']  : "default");
        // TODO: we need to clean 'params' var.
        $this->params = $_GET;
      }
      elseif ($_POST) {
        $this->module = $_POST['module'] ? $_POST['module'] : ($_POST['mod'] ? $_POST['mod'] : "default");
        $this->act    = $_POST['act']    ? $_POST['act']    : ($_POST['do']  ? $_POST['do']  : "default");
        // TODO: we need to clean 'params' var.
        $this->params = $_POST;
      }
      else {
        $this->module = "default";
        $this->act    = "default";
        $this->params = array();
      }
      $this->sysmsg = NULL;
      $this->sysbtn = array();
    }

    //
    // builds the name of the cache file according to domain, module/action & request params.
    //
    function cache_file() {
      if ($this->params) {
        $cache = '';
        foreach ($this->params as $key => $var) {
          $cache .= $key."_".$var."_";
        }
      }
      else {
        $cache = $this->module.($this->act ? "_{$this->act}_" : '');
      }
      $cache = substr($cache,0,strlen($cache)-1);
      $cache = $this->domain()."_$cache";
      return $cache;
    }

    function set_language($code) {
      global $db, $conf;

      if ($conf['tb_conf_lang'] && $code && $code!=$conf['default_language']) {
        $sql  = "select ";
        $sql .= " c.site_slogan, ";
        $sql .= " c.site_message, ";
        $sql .= " c.site_contact_message ";
        $sql .= "from ";
        $sql .= " {$conf['tb_lang']} l, ";
        $sql .= " {$conf['tb_conf_lang']} c ";
        $sql .= "where ";
        $sql .= " l.code='$code' and ";
        $sql .= " l.id=c.language_id ";
        $lang = $db->get_row($sql,ARRAY_A);
        if ($lang) {
          // lang. strings to $conf global array
          foreach ($lang as $key => $var) {
            $conf[$key] = $var;
          }
        }
      }
    }

    //
    // get site domain name, without prefix
    //
    function domain() {
      $domain = strtolower($_SERVER['HTTP_HOST']);
      if (substr($domain,0,4)=="www.") {
        $domain = substr($domain,-(strlen($domain)-4));
      }
      elseif (substr($domain,0,9)=="unstable.") {
        // local dev box has domains like : unstable.domain.com.ar
        $domain = substr($domain,-(strlen($domain)-9));
      }
      elseif (substr($domain,0,4)=="127.") {
        $domain = "127.0.0.1";
      }
      return $domain;
    }

    //
    // get current page load
    //
    function page_load() {
      global $db, $conf;
      // Use:
      //	INCLUDE the file in your script (or cut and paste in)
      //	assign to a variable $serverload = serverload();
      //	This will ONLY track page counts from pages that call the
      //	serverload() function. If you want a page counted, but do
      //	not need the results, simply call the function without
      //	assigning a variable.
      //
      // Requirements:
      //  MySQL database set up with a table (user selectable) in a
      //  database (user selectable). Table has one field, time, which
      //  is defined as INT(14) type.
      //
      $query = $db->query("delete from {$conf['tb_load']} where time<".(time()-$conf['session_timeout']));  // Delete old page counts
      $query = $db->query("insert into {$conf['tb_load']} (time) values (".time().")");             // Insert the current page count
      $count = $db->get_var("select count(*) from {$conf['tb_load']}");                             // Get page count (number of rows in the table)
      return $count;
    }

    //
    // get # users online, counting unique active session
    //
    function user_load() {
      global $db, $conf;
      $rows  = $db->get_results("select distinct sess_id from {$conf['tb_session']}");
      $count = $db->num_rows;
      return $count;
    }

    //
    // get server load on linux servers
    //
    function server_load() {
      $load = "n/a";
      if (file_exists("/proc/loadavg")) {
        if ($f = @fopen("/proc/loadavg","r")) {
          if (!feof($f)) {
            $buffer = fgets($f,1024);
          }
          fclose($f);
          $avg  = explode(" ",$buffer);
          $load = trim($avg[0]);
        }
        elseif ($serverstats = @exec("uptime")) {
          preg_match("/(?:averages)?\: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/",$serverstats,$stats);
          if (is_array($stats)) {
            $load = $stats[1];
          }
        }
      }
      return $load;
    }

    //
    // current URL for translation (http://ki4bbo.org/translation.phps)
    //
    function page_url($plain=0) {
      $page_url = $_SERVER["PHP_SELF"];
      if ($_SERVER['QUERY_STRING'] != '') {
        $page_url .= "?" . $_SERVER['QUERY_STRING'];
      }
      $page_url = 'http://' . $_SERVER["SERVER_NAME"] . $page_url;
      if ($plain) {
        // keep url as-is
        $page_url = str_replace("&amp;", "&", $page_url);
      }
      else {
        $page_url = str_replace(":", "%3A", $page_url);
        $page_url = str_replace("/", "%2F", $page_url);
        $page_url = str_replace("&", "%26", $page_url);
      }
      return $page_url;
    }

    //
    // log referer info, back-check referers logged, delete bad referers
    //
    //  CREATE TABLE referer (
    //    id int(11) NOT NULL auto_increment,
    //    ref_date datetime NOT NULL default '0000-00-00 00:00:00',
    //    ref_visit varchar(255) NOT NULL default '',
    //    ref_url varchar(255) NOT NULL default '',
    //    ref_domain varchar(255) default NULL,
    //    ref_count int(5) default '0',
    //    ref_error int(5) default '0',
    //    PRIMARY KEY  (id)
    //  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    //
    function log_referer($check=0,$limit=0,$ignore=array()) {
      global $db, $conf;

      $this_url = $_SERVER['REQUEST_URI'];
      $full_url = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
      $referer  = getenv('HTTP_REFERER');

      if ($referer && $referer == strip_tags($referer)) {
        $domain = preg_replace("/http:\/\//i", "", $referer);
        $domain = preg_replace("/^www\./i", "", $domain);
        $domain = preg_replace("/\/.*/i", "", $domain);
        if (!in_array(strtolower($domain),$ignore)) {
          // ignore given domains
          $row = $db->get_row("select id from {$conf['tb_referer']} where ref_visit='$this_url' and ref_url='$referer'");
          if ($row) {
            // another referer
            $query = $db->query("update {$conf['tb_referer']} set ref_date=now(), ref_count=ref_count+1 where ref_visit='$this_url' and ref_url='$referer'");
          }
          else {
            // first referer
            $query = $db->query("insert into {$conf['tb_referer']} (ref_date,ref_visit,ref_url,ref_domain,ref_count) values (now(),'$this_url','$referer','$domain','1')");
          }
        }
        if ($check) {
          // back check all referers for current url
          $rows = $db->get_results("select id, ref_url from {$conf['tb_referer']} where ref_visit like '%$this_url%'");
          foreach ($rows as $row) {
            $referer_page = @file_get_contents($row->ref_url);
            if ($referer_page && strstr($referer_page,$this_url)) {
              // good referer, keep it
            }
            else {
              // bad referer, increment it's error count
              $query = $db->query("update {$conf['tb_referer']} set ref_error=ref_error+1 where id='$row->id'");
            }
          }
          if ($limit) {
            // delete bad referers, according to error count limit
            $query = $db->query("delete from {$conf['tb_referer']} where ref_error>=$limit");
          }
        }
      }
      else {
        // then they have tried something funny, putting HTML or PHP into the HTTP_REFERER [or no referer at all]
      }
    }

    //
    // if banned client, redirect request
    //
    function ban() {
      global $db, $conf, $user;
      if (is_object($user) && $conf['site_banned'] && $conf['site_banned_redirect']) {
        $banned = explode("\n",$conf['site_banned']);
        foreach ($banned as $ban) {
          $ban = trim($ban);
          if (substr_count($user->user_host,$ban) || substr_count($user->user_ip,$ban)) {
            // log banned hit count and redirect
            $query = $db->query("update {$conf['tb_conf']} set site_banned_hits=site_banned_hits+1 where site_url like '%{$_SERVER['HTTP_HOST']}%'");
            // banned destination must be off-site
            header("location: {$conf['site_banned_redirect']}");
          }
        }
      }
    }
  }
?>
