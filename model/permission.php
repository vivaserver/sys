<?php
  /*
  +--------------------------------------------------------------------------
  |   User Group Permission Model Class
  |   ========================================
  |   by Cristian R. Arroyo
  |   (c) 2007-2008 VivaServer
  |   http://www.vivaserver.com.ar
  |   Email: cristian.arroyo@vivaserver.com.ar
  +--------------------------------------------------------------------------
  |   THIS IS NOT FREE SOFTWARE!
  +--------------------------------------------------------------------------
  */

  class permission extends model {
    protected $table_name;   // set in $conf['tb_permisos']
    protected $attributes = array(
      'group_id',
      'module',
      'act',
      'grant_access',
      'notes'
    );
    public $data = array();
    public $id = FALSE;

    function __construct($id=FALSE) {
      $this->table_name = $GLOBALS['conf']['tb_permisos'];
      parent::__construct($id);
    }

    function find($id) {
      //
      $sql  = "select ";
      $sql .= " p.*, ";
      $sql .= " g.nombre as grupo ";
      $sql .= "from ";
      $sql .= " {$this->table_name} as p ";
      $sql .= "join ";
      $sql .= " {$GLOBALS['conf']['tb_grupos']} as g ";
      $sql .= "on ";
      $sql .= " p.group_id=g.id ";
      $sql .= "where ";
      $sql .= " p.id=$id ";
      //
      return $this->db->get_row($sql,ARRAY_A);
    }
  }

  class tipo_grupo extends model {
    protected $table_name;   // set in $conf['tb_grupos']
    protected $attributes = array(
      'nombre'
    );
    public $data = array();
    public $id = FALSE;

    function __construct($id=FALSE) {
      $this->table_name = $GLOBALS['conf']['tb_grupos'];
      parent::__construct($id);
    }

    function permiso_por_grupo() {
      $sql  = "select ";
      $sql .= " * ";
      $sql .= "from ";
      $sql .= " {$GLOBALS['conf']['tb_permisos']} ";
      $sql .= "where ";
      $sql .= " group_id=%p1% ";   // current group.id in template
      $sql .= "order by ";
      $sql .= " module, act ";
      return $sql;
    }
  }
?>
