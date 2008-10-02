<?php
  /*
  +--------------------------------------------------------------------------
  |   Base Model Class
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2007-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  class model {
    protected $db;
    protected $conf;

    //
    // load table object into $data data member if class initialized w/$id param.
    //
    function __construct($id=FALSE) {
      global $db, $conf;
      $this->db   = $db;
      $this->conf = $conf;
      if ($id) {
        // if constructor called w/id param. child class HAS a find() method implementation
        $res = $this->find($id);
        if ($res) {
          $this->id   = $id;
          $this->data = $res;
        }
      }
    }

    //
    // build 'where' sql frament from given array of conditions
    //
    public function build_where($conditions) {
      $where = NULL;
      if (is_array($conditions) && count($conditions)>0) {
        foreach ($conditions as $condition) {
          $where .= " $condition and";
        }
        if (is_string($where)) {
          $where = " where ".substr($where,0,-3)." ";
        }
      }
      return $where;
    }

    //
    // build 'where' sql frament from site params
    //
    public function build_conditions_from($params) {
      // take the params from GET or POST and build an array of 'where' conditions
      // from the params included in the Model $attributes array
      foreach ($params as $key => $param) {
        if ($param && in_array($key,$this->attributes)) {
          if (strlen($param)<=2) {
            // param is most likely a code, not a string, so check for exact match
            $conditions[] = "$key='$param'";
          }
          else {
            // "like" is for mysql, "ilike" is for postgresql
            $conditions[] = "$key ".(EZSQL_DB_NAME == 'mysql' ? "like" : "ilike")." '%$param%'";
          }
        }
      }
      return $conditions;
    }

    //
    // builds _GET-like string from site params
    //
    public function build_params_from($params=FALSE) {
      // NOTE: so far this is only needed for old-style PageNav class. should be deprecated TBS 3.x's pager plugin
      if ($params) {
        foreach ($params as $key => $param) {
          if ($param) {
            $string .= "$key=$param&";
          }
        }
        return substr($string,0,-1);
      }
    }

    //
    // builds an 'or' or 'and' query string from given words
    //
    public function build_word_query($column,$query,$oper='and') {
      $conditions = array();
      $words = explode(" ",trim($query));
      if ($words) {
        foreach($words as $word) {
          $conditions[] = "%".strtolower($word)."%";
        }
      }
      else {
        $conditions[] = "%".strtolower($query)."%";
      }
      $query = array();
      foreach ($conditions as $condition) {
        $query[] = "(lower($column) like '$condition')";
      }
      $where = NULL;
      foreach ($query as $string) {
        $where = $where ? "$where $oper $string" : $string;
      }
      return $where;
    }

    //
    // insert array according to class attributes
    //
    function create($array) {
      // pass #1: add field list to sql command
      $sql = "insert into {$this->table_name} (";
      foreach ($array as $key => $var) {
        if (substr($key,0,2)=="f_" && $var && in_array(substr($key,-(strlen($key)-2)),$this->attributes)) {
          $sql .= " ".substr($key,-(strlen($key)-2)).", ";
        }
      }
      $sql  = substr($sql,0,strlen($sql)-2);
      $sql .= " ) ";
      // pass #2: add field values to sql command
      $sql .= "values (";
      foreach ($array as $key => $var) {
        if (substr($key,0,2)=="f_" && $var && in_array(substr($key,-(strlen($key)-2)),$this->attributes)) {
          $var  = trim($var);
          $sql .= " '$var', ";
        }
      }
      $sql  = substr($sql,0,strlen($sql)-2);
      $sql .= " )";
      // execute sql
      if ($this->db->query($sql)) {
        $this->id = $this->db->insert_id;
      }
      return $this->db->insert_id;
    }

    //
    // update table object according class attributes
    //
    function update($array) {
      array_walk($array,array($this,'nullify'));
      /* WARN: deprecated, only update initialized objects */
      /* $with_attr = is_bool($table) ? TRUE : FALSE; */
      /* $table     = is_bool($table) ? $this->table_name : $table; */
      /* $id        = is_bool($id)    ? $this->id : $id; */
      // pass #1: add field list to sql command
      $sql = "update {$this->table_name} set ";
      foreach ($array as $key => $var) {
        if (TRUE) {
          // called from an instance object, also validate key/var pairs with table's attributes (columns)
          if (substr($key,0,2)=="f_" && in_array(substr($key,-(strlen($key)-2)),$this->attributes)) {
            if (is_null($var)) {
              $sql .= " ".substr($key,-(strlen($key)-2))."=NULL, ";
            }
            else {
              $sql .= " ".substr($key,-(strlen($key)-2))."='".trim($var)."', ";
            }
          }
        }
        else {
          /* WARN: deprecated, only update initialized objects */
          // don't do any validation of table columns if called as a class method, just update according to key names
          if (substr($key,0,2)=="f_") {
            if (is_null($var)) {
              $sql .= " ".substr($key,-(strlen($key)-2))."=NULL, ";
            }
            else {
              $sql .= " ".substr($key,-(strlen($key)-2))."='".trim($var)."', ";
            }
          }
        }
      }
      $sql  = substr($sql,0,strlen($sql)-2);
      $sql .= " where ";
      $sql .= "  id='{$this->id}' ";
      //
      return $this->db->query($sql);
    }
    private function nullify(&$element, $key) {
      // replaces empty elements with NULL value
      $element = $element=='' ? NULL : trim($element);
    }

    //
    // destroy current object from table
    //
    function destroy() {
      return $this->db->query("delete from {$this->table_name} where id={$this->id}");
    }

    //
    // count_all : count all personas w/given conditions
    //
    function count_all($conditions=array()) {
      //
      $sql  = "select ";
      $sql .= " count(*) ";
      $sql .= "from ";
      $sql .= " {$this->table_name} as s ";
      $sql .= $this->build_where($conditions);
      //
      return $this->db->get_var($sql);
    }

    //
    // class method find, will be overwritten by model's most likely
    //
    function find($id) {
      //
      $sql  = "select ";
      $sql .= " * ";
      $sql .= "from ";
      $sql .= " {$this->table_name} ";
      $sql .= "where ";
      $sql .= " id=$id ";
      //
      return $this->db->get_row($sql,ARRAY_A);
    }

    //
    // class method find_all, will be overwritten by model's most likely
    //
    function find_all($conditions=array(), $order=FALSE, $limit=FALSE, $offset=FALSE) {
      //
      $sql  = "select ";
      $sql .= " * ";
      $sql .= "from ";
      $sql .= " {$this->table_name} ";
      $sql .= $this->build_where($conditions);
      if ($order) {
        $sql .= " order by $order ";
      }
      elseif (in_array('nombre',$this->attributes)) {
        // if no order supplied, auto-order using "nombre" column, if exists
        $sql .= " order by nombre ";
      }
      $sql .= $limit  ? "limit    $limit  " : NULL;
      $sql .= $offset ? "offset   $offset " : NULL;
      //
      return $this->db->get_results($sql,ARRAY_A);
    }

    //
    // inserts array into any table
    //
    public function insert($table, $array) {
      global $db;
      $sql  = "insert into $table (";
      //
      // pass #1: add field list to sql command
      //
      foreach ($array as $key => $var) {
        if (substr($key,0,2)=="f_" && $var) {
          $sql .= " ".substr($key,-(strlen($key)-2)).", ";
        }
      }
      $sql  = substr($sql,0,strlen($sql)-2);
      $sql .= " ) ";
      $sql .= "values (";
      //
      // pass #2: add field values to sql command
      //
      foreach ($array as $key => $var) {
        if (substr($key,0,2)=="f_" && $var) {
          $var  = trim($var);
          $sql .= " '$var', ";
        }
      }
      $sql  = substr($sql,0,strlen($sql)-2);
      $sql .= " )";
      //
      $db->query($sql);
      //
      return $db->insert_id;
    }
  }
?>
