<?php
  /*
  +--------------------------------------------------------------------------
  |   Overall functions : Do not modify unless you know what you are doing
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2004-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  //
  // TBS functions for ezSQL
  //
  function tbsdb_db_open(&$db,&$query) {
    $db->get_results($query) ;
    return $db ;
  }
  function tbsdb_db_fetch(&$db,$num) {
    if ($num<=$db->num_rows) {
      return $db->get_row(null,ARRAY_A,$num-1) ;
    } else {
      return False ;
    }
  }
  function tbsdb_db_close(&$db) {
    // not needed
  }

  //
  // flat multi-dimensional array to one dimension
  // thanks to http://www.php.net/manual/en/ref.array.php#60518
  // but see warning at http://www.php.net/manual/en/ref.array.php#69235
  //
  function array_flatten($array,$prefix='') {
    $tmp = array();
    foreach($array as $key => $value) {
      if (is_array($value)) {
        $tmp = array_merge($tmp,array_flatten($value,$key));
      }
      else {
        $tmp[$prefix.$key] = $value;
      }
    }
    return $tmp;
  }

  //
  // simple function to remove element from array
  // thanks to http://www.php.net/manual/en/ref.array.php#73434
  // changed to use key instead of array index
  //
  function array_remove($val, &$array) {
    $k = array_search($val,$array);
    if ($k === FALSE) {
      return FALSE;
    }
    else {
      $i = array_kpos($k,$array);
      $array = array_merge(array_slice($array,0,$i),array_slice($array,$i+1));
    }
    return TRUE;
  }

  //
  // get a key position in array
  // thanks to http://www.php.net/manual/en/ref.array.php#73434
  //
  function array_kpos($key, $array) {
    $x = 0;
    foreach ($array as $i => $v) {
      if($key === $i) {
        return $x;
      }
      $x++;
    }
    return FALSE;
  }

  //
  // format valid date to yyyy-mm-dd w/leading 0
  //
  function format_date($dd, $mm, $yy) {
    $date = FALSE;
    $dd   = trim($dd);
    $mm   = trim($mm);
    $yy   = trim($yy);
    if ($dd=='' && $mm=='' && $yy=='') {
      $date = NULL;
    }
    elseif (valid_year($yy)>=$GLOBALS['conf']['lowest_possible_year'] && $mm && $mm<13 && $dd && $dd<32) {
      $yy   = valid_year($yy);
      $mm   = $mm < 10 ? substr("0".$mm,-2) : $mm;
      $dd   = $dd < 10 ? substr("0".$dd,-2) : $dd;
      $date = "$yy-$mm-$dd";
    }
    return $date;
  }

  //
  // return valid 4-digit year
  //
  function valid_year($yyyy) {
    if ($yyyy) {
      $yy = trim($yyyy);
      if ($yy < $GLOBALS['conf']['lowest_possible_year']) {
        if ($yy <= 99) {
          if ($yy >= 90) {
            // year is like "98", meaning "1998"
            $yy = "19$yy";
          }
          else {
            if ($yy <= 9) {
              // year is like "8", meaning "2008"
              $yy = str_replace("0",'',$yy);
              $yy = "200$yy";
            }
            else {
              // year is like "13", meaning "2013"
              $yy = "20$yy";
            }
          }
        }
        $yyyy = $yy;
      }
      return $yyyy;
    }
  }

  //
  // format valid time to hh:mn w/leading 0
  //
  function format_time($hh, $mn) {
    $time = FALSE;
    $hh   = trim($hh);
    $dd   = trim($dd);
    if ($hh=='' && $mn=='') {
      $time = NULL;
    }
    elseif ($hh && $hh<24 && $mn && $mn<60) {
      $hh   = $hh < 10 ? substr("0".$hh,-2) : $hh;
      $mn   = $mn < 10 ? substr("0".$mn,-2) : $mn;
      $time = "$hh:$mn";
    }
    return $time;
  }

  //
  // some nice & useful date/time wrapper methods
  //
  function current_date() {
    return date("Y-m-d");
  }
  function current_date_time() {
    return date("Y-m-d H:i:s");
  }
?>